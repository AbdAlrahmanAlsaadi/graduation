<?php
namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * سؤال البوت مع إمكانية تمرير تاريخ المحادثة
     */
    public function ask($userQuestion, array $history = [])
    {
        try {
            // ============================================================
            // 1. جمع بيانات قاعدة البيانات
            //
            // ملاحظة مهمة:
            // ما زلنا نعطي الـ AI إمكانية الوصول إلى جميع الجداول الموجودة
            // في هذا الملف، لكننا نستخدم ضغط JSON وبدون PRETTY_PRINT لتقليل
            // حجم الطلب بشكل كبير.
            // ============================================================
            $stats = [];

            $stats['users'] = DB::select(
                "SELECT id, name, email, status, created_at FROM users"
            );

            $stats['projects'] = DB::select("
                SELECT id, name, location, apartment_area, height, status,
                       project_manager_id, assistant_engineer_id, owner_id,
                       created_at, updated_at
                FROM projects
                WHERE deleted_at IS NULL
            ");

            $stats['work_items'] = DB::select("
                SELECT id, project_id, name, status, duration_days, weight,
                       created_at, updated_at
                FROM work_items
                WHERE is_active = 1
            ");

            $stats['spaces'] = DB::select("
                SELECT id, project_id, type, wall_area, ceiling_area,
                       wall_finish_type, ceiling_finish_type
                FROM spaces
                WHERE deleted_at IS NULL
            ");

            $stats['materials'] = DB::select(
                "SELECT id, name, unit FROM materials"
            );

            $stats['work_item_materials'] = DB::select(
                "SELECT work_item_name, material_id FROM work_item_materials"
            );

            $stats['work_item_details'] = DB::select(
                "SELECT work_item_id, `key`, `value`, unit FROM work_item_details"
            );

            $stats['work_item_invoices'] = DB::select("
                SELECT id, project_id, work_item_id, supplier_name,
                       invoice_number, invoice_date, total_amount, notes
                FROM work_item_invoices
                WHERE deleted_at IS NULL
            ");

            $stats['work_item_invoice_items'] = DB::select("
                SELECT invoice_id, material_name_snapshot, quantity,
                       unit, unit_price, total_price
                FROM work_item_invoice_items
                WHERE deleted_at IS NULL
            ");

            $stats['work_item_labor_costs'] = DB::select("
                SELECT project_id, work_item_id, workshop_name, amount, notes
                FROM work_item_labor_costs
                WHERE deleted_at IS NULL
            ");

            $stats['work_item_material_templates'] = DB::select("
                SELECT work_item_type, material_name, unit, default_qty, category
                FROM work_item_material_templates
            ");

            $stats['equipment'] = DB::select(
                "SELECT id, name, type, identifier_no, status FROM equipment"
            );

            $stats['equipment_bookings'] = DB::select("
                SELECT equipment_id, work_item_id, booked_by,
                       start_date, end_date, status, notes
                FROM equipment_bookings
            ");

            $stats['equipment_maintenances'] = DB::select("
                SELECT equipment_id, start_date, end_date, type, description
                FROM equipment_maintenances
            ");

            $stats['contracts'] = DB::select("
                SELECT project_id, contract_no, title, contract_date,
                       start_date, end_date, contract_value, currency, status
                FROM contracts
            ");

            $stats['documents'] = DB::select(
                "SELECT project_id, type, title, created_at FROM documents"
            );

            $stats['document_versions'] = DB::select(
                "SELECT document_id, version_no, file_path FROM document_versions"
            );

            $stats['comments'] = DB::select(
                "SELECT work_item_id, user_id, comment, created_at FROM comments"
            );

            $stats['notifications'] = DB::select("
                SELECT user_id, project_id, type, title, is_read, created_at
                FROM notifications
            ");

            $stats['activity_logs'] = DB::select("
                SELECT user_id, action, method, endpoint, created_at
                FROM activity_logs
                ORDER BY created_at DESC
                LIMIT 50
            ");

            $stats['photos_progress'] = DB::select("
                SELECT project_id, work_item_id, original_name, created_at
                FROM photos_progress
            ");

            $stats['progress_update_requests'] = DB::select("
                SELECT project_id, work_item_id, status, type, created_at
                FROM progress_update_requests
            ");

            $stats['workshop_expenses'] = DB::select("
                SELECT project_id, work_item_id, amount, description, created_at
                FROM workshop_expenses
            ");

            $stats['project_engineers'] = DB::select("
                SELECT
                    pe.project_id,
                    p.name AS project_name,
                    pe.user_id,
                    u.name AS user_name,
                    pe.role,
                    pe.assigned_at
                FROM project_engineers pe
                LEFT JOIN projects p ON pe.project_id = p.id
                LEFT JOIN users u ON pe.user_id = u.id
            ");

            $stats['project_images'] = DB::select("
                SELECT project_id, name, image, created_at
                FROM project_images
            ");

            $stats['ai_visualizations'] = DB::select("
                SELECT project_image_id, generated_image, created_at
                FROM ai_visualizations
            ");

            $stats['ai_visualization_comments'] = DB::select("
                SELECT ai_visualization_id, user_id, comment, created_at
                FROM ai_visualization_comments
            ");

            $stats['permissions'] = DB::select(
                "SELECT id, name FROM permissions"
            );

            $stats['roles'] = DB::select(
                "SELECT id, name FROM roles"
            );

            $stats['role_has_permissions'] = DB::select(
                "SELECT permission_id, role_id FROM role_has_permissions"
            );

            $stats['model_has_roles'] = DB::select(
                "SELECT role_id, model_id FROM model_has_roles"
            );

            // لا نرسل access tokens نفسها إلى Gemini.
            // نرسل metadata المفيدة فقط.
            $stats['personal_access_tokens'] = DB::select("
                SELECT tokenable_id, name, last_used_at, created_at
                FROM personal_access_tokens
            ");

            $stats['sessions'] = DB::select("
                SELECT user_id, ip_address, last_activity
                FROM sessions
            ");

            // الكاش قد يكون ضخمًا جدًا؛ نرسل metadata فقط.
            $stats['cache'] = DB::select("
                SELECT `key`, expiration
                FROM cache
            ");

            $stats['cache_locks'] = DB::select("
                SELECT `key`, expiration
                FROM cache_locks
            ");

            $stats['jobs'] = DB::select("
                SELECT queue, attempts, created_at
                FROM jobs
            ");

            // ============================================================
            // 2. تاريخ المحادثة
            // نرسل فقط آخر 8 رسائل بدل إرسال history كامل.
            // ============================================================
            $contextSummary = "";

            if (!empty($history)) {
                $recentHistory = array_slice($history, -8);

                $contextSummary = "تاريخ المحادثة الأخير:\n";

                foreach ($recentHistory as $turn) {
                    $role = (($turn['role'] ?? '') === 'user')
                        ? 'المستخدم'
                        : 'المساعد';

                    $turnText = (string) ($turn['text'] ?? '');

                    // حماية من رسائل ضخمة جدًا داخل history.
                    $turnText = mb_substr($turnText, 0, 1000);

                    $contextSummary .= "- {$role}: {$turnText}\n";
                }
            }

            // ============================================================
            // 3. تعليمات النظام
            // ============================================================
            $systemPrompt = "
أنت خبير استراتيجي في إدارة مشاريع الإكساء والبناء، وتتحدث العربية الفصحى.

قواعد مهمة:

1. لديك إمكانية الوصول إلى بيانات قاعدة البيانات المرفقة في هذا الطلب، وتشمل
   المشاريع والمستخدمين وبنود العمل والمساحات والمواد والفواتير والعمالة
   والمعدات والعقود والمستندات والتعليقات والإشعارات وسجل النشاطات والتقدم
   والصور والتصورات والصلاحيات والأدوار وغيرها.

2. استخدم البيانات الفعلية المرفقة للإجابة، ولا تخترع أسماء أو أرقامًا أو
   تكاليف أو علاقات غير موجودة في البيانات.

3. إذا احتوى السؤال على ضمير مثل: هو، هي، هذا، هذه، مرتبط، خاص بـ، تابع لـ،
   مربوط بـ، فراجع تاريخ المحادثة السابق لتحديد المقصود.

4. إذا كان المقصود واضحًا من السياق، لا تطلب من المستخدم توضيحًا إضافيًا.

5. إذا لم توجد المعلومة في البيانات المرفقة، قل بوضوح إن المعلومة غير موجودة
   في البيانات المتاحة.

6. عند وجود أرقام مالية، اذكر الرقم والعملة إن كانت موجودة.

7. لا تعرض مفاتيح API أو كلمات المرور أو access tokens أو أي أسرار تقنية.

تنسيق الإجابة:
- استخدم عناوين غامقة.
- استخدم نقاطًا للقوائم.
- استخدم أرقامًا للخطوات.
- اترك سطرًا فارغًا بين الفقرات.
- أجب مباشرة وبدون مقدمات طويلة.
";

            // ============================================================
            // 4. تحويل البيانات إلى JSON مضغوط
            //
            // أهم فرق عن النسخة السابقة:
            // لا نستخدم JSON_PRETTY_PRINT لأنه يزيد حجم الـ payload بلا فائدة.
            // ============================================================
            $statsJson = json_encode(
                $stats,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_INVALID_UTF8_SUBSTITUTE
            );

            if ($statsJson === false) {
                Log::error('Gemini DB JSON Encode Error', [
                    'error' => json_last_error_msg(),
                ]);

                return [
                    'success' => false,
                    'message' => 'تعذر تجهيز بيانات قاعدة البيانات للذكاء الاصطناعي.',
                ];
            }

            // ============================================================
            // 5. بناء السؤال
            // ============================================================
            $currentPrompt = "
{$systemPrompt}

---

السياق المستخلص من المحادثة السابقة:
{$contextSummary}

---

بيانات قاعدة البيانات الحالية بصيغة JSON:
{$statsJson}

---

سؤال المستخدم الحالي:
{$userQuestion}

---

التعليمات النهائية:
1. أجب مباشرة.
2. استخدم بيانات قاعدة البيانات الفعلية.
3. إذا كان السؤال يعتمد على سياق سابق، استخدم السياق السابق.
4. لا تخترع بيانات.
5. لا تعرض أسرارًا أو access tokens.
";

            // ============================================================
            // 6. contents
            //
            // لا نرسل التاريخ القديم كاملًا مرة أخرى.
            // contextSummary يكفي لفهم السياق، ونرسل آخر الرسائل فقط.
            // ============================================================
            $contents = [];

            foreach ($recentHistory ?? [] as $turn) {
                $role = (($turn['role'] ?? '') === 'user') ? 'user' : 'model';
                $turnText = mb_substr((string) ($turn['text'] ?? ''), 0, 1000);

                if ($turnText === '') {
                    continue;
                }

                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $turnText]
                    ],
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $currentPrompt]
                ],
            ];

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 8192,
                ],
            ];

            // ============================================================
            // 7. مراقبة الحجم والأداء
            // ============================================================
            $payloadJson = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            );

            $payloadBytes = strlen($payloadJson ?: '');

            Log::info('Gemini Request Started', [
                'question_length' => mb_strlen((string) $userQuestion),
                'history_count' => count($recentHistory ?? []),
                'payload_bytes' => $payloadBytes,
                'payload_mb' => round($payloadBytes / 1024 / 1024, 2),
            ]);

            // ============================================================
            // 8. الاتصال بـ Gemini
            //
            // retry يساعد مع 429/5xx والانقطاعات المؤقتة.
            // ============================================================
            $startedAt = microtime(true);

            $response = Http::timeout(180)
                ->connectTimeout(60)
                ->retry(
                    3,
                    1500,
                    function ($exception, $request) {
                        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        $status = $exception->response ? $exception->response->status() : null;
                            return in_array($status, [
                                408,
                                425,
                                429,
                                500,
                                502,
                                503,
                                504
                            ], true);
                        }

                        return true;
                    },
                    false
                )
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url, $payload);

            $durationMs = round((microtime(true) - $startedAt) * 1000);

            Log::info('Gemini Request Finished', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'duration_ms' => $durationMs,
                'payload_mb' => round($payloadBytes / 1024 / 1024, 2),
            ]);

            // ============================================================
            // 9. معالجة الأخطاء بشكل واضح
            // ============================================================
            if (!$response->successful()) {
                $body = $response->body();

                Log::error('Gemini API Error (Chat)', [
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
                        'message' => 'خدمة Gemini لم تستجب بشكل مستقر. تم إعادة المحاولة تلقائيًا، حاول مرة أخرى.',
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

            // ============================================================
            // 10. استخراج الرد
            // ============================================================
            $data = $response->json();

            $candidate = $data['candidates'][0] ?? [];

            $rawText = $candidate['content']['parts'][0]['text'] ?? null;

            $finishReason = $candidate['finishReason'] ?? null;

            Log::info('Gemini Finish Reason', [
                'finish_reason' => $finishReason,
                'has_text' => !empty($rawText),
            ]);

            if (!$rawText) {
                Log::error('Gemini Empty Response', [
                    'response' => mb_substr($response->body(), 0, 5000),
                ]);

                return [
                    'success' => false,
                    'message' => 'لم يتم استلام رد من الذكاء الاصطناعي.',
                ];
            }

            // ============================================================
            // 11. تنظيف الرد
            // ============================================================
            $cleanText = preg_replace('/\*\*(.*?)\*\*/s', '$1', $rawText);
            $cleanText = preg_replace('/\n{3,}/', "\n\n", $cleanText);
            $cleanText = preg_replace('/[ \t]+/', ' ', $cleanText);
            $cleanText = trim($cleanText);

            return [
                'success' => true,
                'answer' => $cleanText,
            ];
        } catch (Exception $e) {
            Log::error('AI Chat Exception', [
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
}
