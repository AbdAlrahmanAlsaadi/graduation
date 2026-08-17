<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class DatabaseAIService
{
    protected $apiKey;
    protected $url;

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key');

        $this->url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}";
    }


    public function ask($userQuestion, array $history = [])
    {
        $startedTotalAt = microtime(true);

        try {
            $userQuestion = trim((string) $userQuestion);

            if ($userQuestion === '') {
                return [
                    'success' => false,
                    'message' => 'السؤال فارغ.',
                ];
            }

            // ============================================================
            // 1) Schema فقط - بدون تحميل بيانات الجداول.
            // ============================================================
            $schema = $this->getDatabaseSchema();

            if (empty($schema)) {
                return [
                    'success' => false,
                    'message' => 'تعذر قراءة مخطط قاعدة البيانات.',
                ];
            }

            // ============================================================
            // 2) السياق السابق
            // ============================================================
            $recentHistory = array_slice($history, -8);
            $contextSummary = $this->buildHistoryText($recentHistory);

            // ============================================================
            // 3) Gemini يحدد الاستعلام المطلوب فقط.
            // ============================================================
            $plannerPrompt = $this->buildPlannerPrompt(
                $userQuestion,
                $contextSummary,
                $schema
            );

            $planner = $this->callGemini(
                $plannerPrompt,
                2500,
                0.0
            );

            if (!$planner['success']) {
                return $planner;
            }

            $plan = $this->extractJson($planner['text']);

            if (!is_array($plan) || empty($plan['sql'])) {
                Log::error('Gemini DB Planner Invalid Response', [
                    'response' => mb_substr($planner['text'], 0, 5000),
                ]);

                return [
                    'success' => false,
                    'message' => 'تعذر تحديد البيانات المطلوبة من قاعدة البيانات.',
                ];
            }

            $sql = $this->sanitizeReadOnlySql(
                (string) $plan['sql'],
                $schema
            );

            if ($sql === null) {
                Log::warning('Gemini DB Planner Rejected SQL', [
                    'sql' => mb_substr((string) ($plan['sql'] ?? ''), 0, 5000),
                ]);

                return [
                    'success' => false,
                    'message' => 'تم رفض استعلام قاعدة البيانات لأنه غير آمن أو غير صالح.',
                ];
            }

            // ============================================================
            // 4) تنفيذ الاستعلام المطلوب فقط.
            // ============================================================
            $queryStartedAt = microtime(true);

            $rows = DB::select($sql);

            $queryDurationMs = round(
                (microtime(true) - $queryStartedAt) * 1000
            );

            // حماية إضافية إذا أعاد الاستعلام عددًا كبيرًا من الصفوف.
            $rows = array_slice($rows, 0, 500);

            $rowsArray = array_map(
                static function ($row) {
                    return (array) $row;
                },
                $rows
            );

            // ============================================================
            // 5) Gemini يصيغ الإجابة من النتائج الفعلية فقط.
            // ============================================================
            $answerPrompt = $this->buildAnswerPrompt(
                $userQuestion,
                $contextSummary,
                $sql,
                $rowsArray
            );

            $answer = $this->callGemini(
                $answerPrompt,
                8192,
                0.2
            );

            if (!$answer['success']) {
                return $answer;
            }

            $cleanText = $this->cleanAnswer($answer['text']);

            Log::info('Gemini Database AI Finished', [
                'query_ms' => $queryDurationMs,
                'total_ms' => round((microtime(true) - $startedTotalAt) * 1000),
                'rows' => count($rowsArray),
                'sql_length' => strlen($sql),
            ]);

            return [
                'success' => true,
                'answer' => $cleanText,
            ];
        } catch (Exception $e) {
            Log::error('AI Database Exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    
    protected function getDatabaseSchema()
    {
        $databaseName = DB::getDatabaseName();

        $cacheKey = 'database_ai_schema_' . md5((string) $databaseName);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($databaseName) {
                $columns = DB::select(
                    "
                    SELECT
                        TABLE_NAME,
                        COLUMN_NAME,
                        DATA_TYPE,
                        IS_NULLABLE,
                        COLUMN_KEY
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = ?
                    ORDER BY TABLE_NAME, ORDINAL_POSITION
                    ",
                    [$databaseName]
                );

                $schema = [];

                foreach ($columns as $column) {
                    $tableName = (string) $column->TABLE_NAME;
                    $columnName = (string) $column->COLUMN_NAME;

                    // لا نرسل أعمدة الأسرار إلى Gemini.
                    if ($this->isSensitiveColumn($columnName)) {
                        continue;
                    }

                    // لا نحتاج جداول Laravel الداخلية التي لا تفيد أسئلة المشاريع.
                    if ($this->isInternalTable($tableName)) {
                        continue;
                    }

                    if (!isset($schema[$tableName])) {
                        $schema[$tableName] = [];
                    }

                    $schema[$tableName][] = [
                        'name' => $columnName,
                        'type' => $column->DATA_TYPE,
                        'nullable' => $column->IS_NULLABLE,
                        'key' => $column->COLUMN_KEY,
                    ];
                }

                // العلاقات الخارجية تساعد Gemini على اختيار JOIN صحيح.
                $foreignKeys = DB::select(
                    "
                    SELECT
                        TABLE_NAME,
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ?
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    ORDER BY TABLE_NAME, COLUMN_NAME
                    ",
                    [$databaseName]
                );

                $relations = [];

                foreach ($foreignKeys as $foreignKey) {
                    $tableName = (string) $foreignKey->TABLE_NAME;

                    if ($this->isInternalTable($tableName)) {
                        continue;
                    }

                    $relations[] = [
                        'table' => $tableName,
                        'column' => $foreignKey->COLUMN_NAME,
                        'references_table' => $foreignKey->REFERENCED_TABLE_NAME,
                        'references_column' => $foreignKey->REFERENCED_COLUMN_NAME,
                    ];
                }

                return [
                    'tables' => $schema,
                    'relations' => $relations,
                ];
            }
        );
    }

    protected function buildPlannerPrompt(
        $question,
        $contextSummary,
        array $schema
    ) {
        $schemaJson = json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_INVALID_UTF8_SUBSTITUTE
        );

        return <<<PROMPT
أنت Database Query Planner داخل نظام إدارة مشاريع الإكساء والبناء.

هدفك هو تحديد البيانات التي يحتاجها السؤال من قاعدة البيانات، وليس الإجابة من معلومات عامة.

لديك مخطط قاعدة البيانات أدناه. المخطط يحتوي على أسماء الجداول والأعمدة والعلاقات،
لكن لا يحتوي على صفوف البيانات.

أرجع JSON فقط بهذا الشكل:
{"sql":"SELECT ...","reason":"سبب مختصر"}

قواعد SQL:
1. SQL يجب أن يكون SELECT واحدًا فقط، أو WITH ... SELECT واحدًا.
2. ممنوع INSERT أو UPDATE أو DELETE أو DROP أو ALTER أو TRUNCATE أو CREATE أو
   GRANT أو REVOKE أو CALL أو SET أو أي أمر يغير البيانات.
3. استخدم فقط الجداول والأعمدة الموجودة في المخطط.
4. لا تستخدم SELECT *.
5. اختر الأعمدة المطلوبة فقط.
6. إذا كان السؤال عن مشروع أو مستخدم أو بند أو مادة محددة، استخدم WHERE.
7. استخدم العلاقات الموجودة في المخطط عند الحاجة إلى JOIN.
8. للأسئلة الحسابية استخدم SUM/COUNT/AVG/MIN/MAX/GROUP BY بدل جلب آلاف الصفوف.
9. إذا كان الاستعلام قد يرجع صفوفًا كثيرة، استخدم LIMIT 500.
10. لا تحاول قراءة كلمات المرور أو tokens أو secrets.
11. لا تخترع جدولًا أو عمودًا غير موجود.
12. إذا كان السؤال يعتمد على "هو/هي/هذا/هذه/المشروع المذكور/البند السابق"،
    استخدم السياق السابق لتحديد المقصود.
13. إذا كان السؤال لا يحتاج قاعدة البيانات، يمكن أن ترجع:
    {"sql":"SELECT 1 AS result","reason":"لا يحتاج بيانات أعمال"}
14. لا تكتب أي Markdown حول JSON.

السؤال الحالي:
{$question}

السياق السابق:
{$contextSummary}

مخطط قاعدة البيانات:
{$schemaJson}
PROMPT;
    }

    protected function buildAnswerPrompt(
        $question,
        $contextSummary,
        $sql,
        array $rows
    ) {
        $rowsJson = json_encode(
            $rows,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_INVALID_UTF8_SUBSTITUTE
        );

        return <<<PROMPT
أنت المساعد الذكي لنظام إدارة مشاريع الإكساء والبناء.

أجب المستخدم بالعربية بشكل مباشر وواضح.

قواعد مهمة:
1. استخدم فقط نتائج قاعدة البيانات الموجودة أدناه.
2. لا تخترع أي اسم أو رقم أو تكلفة أو علاقة غير موجودة في النتائج.
3. إذا كانت النتائج فارغة، قل إن البيانات المطلوبة غير موجودة في قاعدة البيانات.
4. إذا كان السؤال حسابيًا، احسب من النتائج بدقة.
5. استخدم السياق السابق لفهم المقصود والضمائر.
6. لا تعرض SQL للمستخدم إلا إذا طلبه صراحة.
7. لا تعرض كلمات مرور أو tokens أو secrets.
8. لا تقل إنك لا تستطيع الوصول إلى قاعدة البيانات؛ تم تنفيذ استعلام فعلي.
9. إذا كان السؤال لا يحتاج بيانات قاعدة البيانات، أجب بشكل طبيعي من المعرفة العامة،
   لكن لا تنسب معلومة عامة إلى قاعدة البيانات.
10. لا تذكر للمستخدم تفاصيل داخلية عن آلية اختيار الاستعلام.

السؤال:
{$question}

السياق السابق:
{$contextSummary}

الاستعلام الذي تم تنفيذه:
{$sql}

نتائج قاعدة البيانات:
{$rowsJson}
PROMPT;
    }

    /**
     * اتصال Gemini مع retry.
     *
     * ملاحظة: retry قبل post()، وليس بعده.
     */
    protected function callGemini(
        $prompt,
        $maxOutputTokens = 4096,
        $temperature = 0.2
    ) {
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxOutputTokens,
            ],
        ];

        $payloadJson = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
        );

        $payloadBytes = strlen($payloadJson ?: '');

        Log::info('Gemini Request Started', [
            'payload_mb' => round($payloadBytes / 1024 / 1024, 2),
        ]);

        $startedAt = microtime(true);

        $response = Http::timeout(180)
            ->connectTimeout(60)
            ->retry(
                3,
                1500,
                function ($exception, $request) {
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        $status = $exception->response
                            ? $exception->response->status()
                            : null;

                        return in_array($status, [
                            408,
                            425,
                            429,
                            500,
                            502,
                            503,
                            504,
                        ], true);
                    }

                    // مهم لـ cURL error 28 وأخطاء الاتصال.
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                },
                false
            )
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($this->url, $payload);

        $durationMs = round(
            (microtime(true) - $startedAt) * 1000
        );

        Log::info('Gemini Request Finished', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'duration_ms' => $durationMs,
            'payload_mb' => round($payloadBytes / 1024 / 1024, 2),
        ]);

        if (!$response->successful()) {
            $body = $response->body();

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'duration_ms' => $durationMs,
                'payload_bytes' => $payloadBytes,
                'body' => mb_substr($body, 0, 5000),
            ]);

            $status = $response->status();

            if ($status === 429) {
                return [
                    'success' => false,
                    'message' => 'تم تجاوز حد الطلبات في Gemini. حاول مرة أخرى بعد قليل.',
                ];
            }

            if (in_array($status, [408, 500, 502, 503, 504], true)) {
                return [
                    'success' => false,
                    'message' => 'خدمة Gemini لم تستجب بشكل مستقر. تمت إعادة المحاولة تلقائيًا.',
                ];
            }

            if ($status === 413) {
                return [
                    'success' => false,
                    'message' => 'حجم البيانات المرسلة إلى Gemini كبير جدًا.',
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل الاتصال بخدمة الذكاء الاصطناعي.',
            ];
        }

        $data = $response->json();

        $candidate = $data['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $rawText = '';

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $rawText .= $part['text'];
            }
        }

        $finishReason = $candidate['finishReason'] ?? null;

        Log::info('Gemini Finish Reason', [
            'finish_reason' => $finishReason,
            'has_text' => $rawText !== '',
        ]);

        if ($rawText === '') {
            Log::error('Gemini Empty Response', [
                'response' => mb_substr($response->body(), 0, 5000),
            ]);

            return [
                'success' => false,
                'message' => 'لم يتم استلام رد من الذكاء الاصطناعي.',
            ];
        }

        return [
            'success' => true,
            'text' => $rawText,
            'finish_reason' => $finishReason,
        ];
    }

    /**
     * يتحقق أن SQL قراءة فقط ويمنع الأوامر الخطرة.
     * كما يتحقق أن أسماء الجداول والأعمدة الموجودة في FROM/JOIN
     * موجودة في الـSchema قبل التنفيذ.
     */
    protected function sanitizeReadOnlySql($sql, array $schema)
    {
        $sql = trim((string) $sql);

        $sql = preg_replace('/^```(?:sql)?\s*/i', '', $sql);
        $sql = preg_replace('/\s*```$/', '', $sql);
        $sql = trim($sql);

        // نسمح بفاصلة منقوطة واحدة في النهاية فقط.
        $sql = rtrim($sql, " \t\n\r;");

        if ($sql === '') {
            return null;
        }

        // ممنوع أكثر من statement.
        if (strpos($sql, ';') !== false) {
            return null;
        }

        if (!preg_match('/^(SELECT|WITH)\b/i', $sql)) {
            return null;
        }

        $forbidden = [
            'INSERT',
            'UPDATE',
            'DELETE',
            'DROP',
            'ALTER',
            'TRUNCATE',
            'CREATE',
            'RENAME',
            'GRANT',
            'REVOKE',
            'CALL',
            'SET',
            'LOAD_FILE',
            'INTO OUTFILE',
            'INTO DUMPFILE',
            'SLEEP(',
            'BENCHMARK(',
        ];

        foreach ($forbidden as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $sql)) {
                return null;
            }
        }

        /*
         * لا نسمح باستعلامات على قاعدة/Schema مختلفة.
         * يسمح فقط بجداول قاعدة Laravel الحالية.
         */
        if (preg_match_all(
            '/\b(?:FROM|JOIN)\s+`?([a-zA-Z0-9_]+)`?/i',
            $sql,
            $matches
        )) {
            $knownTables = array_keys($schema['tables'] ?? []);

            foreach ($matches[1] as $table) {
                if (!in_array($table, $knownTables, true)) {
                    return null;
                }

                if ($this->isInternalTable($table)) {
                    return null;
                }
            }
        }

        // لا نسمح بـ database.table أو `database`.`table`.
        if (preg_match('/\b[a-zA-Z0-9_]+\s*\.\s*[a-zA-Z0-9_]+/', $sql)) {
            return null;
        }

        // LIMIT احتياطي.
        if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql .= ' LIMIT 500';
        }

        return $sql;
    }

    protected function extractJson($text)
    {
        $text = trim((string) $text);

        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function buildHistoryText(array $history)
    {
        if (empty($history)) {
            return 'لا يوجد سياق سابق.';
        }

        $text = "تاريخ المحادثة الأخير:\n";

        foreach ($history as $turn) {
            $role = (($turn['role'] ?? '') === 'user')
                ? 'المستخدم'
                : 'المساعد';

            $turnText = mb_substr(
                (string) ($turn['text'] ?? ''),
                0,
                1200
            );

            if ($turnText !== '') {
                $text .= "- {$role}: {$turnText}\n";
            }
        }

        return $text;
    }

    protected function cleanAnswer($text)
    {
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    protected function isSensitiveColumn($column)
    {
        $column = strtolower((string) $column);

        $sensitiveWords = [
            'password',
            'password_hash',
            'remember_token',
            'access_token',
            'refresh_token',
            'api_token',
            'client_secret',
            'private_key',
        ];

        foreach ($sensitiveWords as $word) {
            if ($column === $word || strpos($column, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function isInternalTable($table)
    {
        $table = strtolower((string) $table);

        /*
         * هذه الجداول لا نحتاجها لأسئلة المشروع، وبعضها قد يحتوي
         * بيانات جلسات/كاش/طوابير أو أسرار.
         */
        $internalTables = [
            'migrations',
            'password_reset_tokens',
            'password_resets',
            'personal_access_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];

        return in_array($table, $internalTables, true);
    }
}
