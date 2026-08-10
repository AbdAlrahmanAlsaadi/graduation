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
            // ============================================
            // 1. جلب جميع البيانات من جميع الجداول (نفس ما كان)
            // ============================================
            $stats = [];

            // 1.1 المستخدمين
            $stats['users'] = DB::select("SELECT id, name, email, status, created_at FROM users");

            // 1.2 المشاريع (مع جميع المعرفات)
            $stats['projects'] = DB::select("
                SELECT
                    id, name, location, apartment_area, height, status,
                    project_manager_id, assistant_engineer_id, owner_id,
                    created_at, updated_at
                FROM projects WHERE deleted_at IS NULL
            ");

            // 1.3 بنود العمل
            $stats['work_items'] = DB::select("SELECT id, project_id, name, status, duration_days, weight, created_at, updated_at FROM work_items WHERE deleted_at IS NULL");

            // 1.4 المساحات
            $stats['spaces'] = DB::select("SELECT id, project_id, type, wall_area, ceiling_area, wall_finish_type, ceiling_finish_type FROM spaces WHERE deleted_at IS NULL");

            // 1.5 المواد
            $stats['materials'] = DB::select("SELECT id, name, unit FROM materials");

            // 1.6 ربط المواد
            $stats['work_item_materials'] = DB::select("SELECT work_item_name, material_id FROM work_item_materials");

            // 1.7 تفاصيل بنود العمل (مع backticks)
            $stats['work_item_details'] = DB::select("SELECT work_item_id, `key`, `value`, unit FROM work_item_details");

            // 1.8 الفواتير
            $stats['work_item_invoices'] = DB::select("SELECT id, project_id, work_item_id, supplier_name, invoice_number, invoice_date, total_amount, notes FROM work_item_invoices WHERE deleted_at IS NULL");

            // 1.9 تفاصيل الفواتير
            $stats['work_item_invoice_items'] = DB::select("SELECT invoice_id, material_name_snapshot, quantity, unit, unit_price, total_price FROM work_item_invoice_items WHERE deleted_at IS NULL");

            // 1.10 تكاليف العمالة
            $stats['work_item_labor_costs'] = DB::select("SELECT project_id, work_item_id, workshop_name, amount, notes FROM work_item_labor_costs WHERE deleted_at IS NULL");

            // 1.11 قوالب المواد
            $stats['work_item_material_templates'] = DB::select("SELECT work_item_type, material_name, unit, default_qty, category FROM work_item_material_templates");

            // 1.12 المعدات
            $stats['equipment'] = DB::select("SELECT id, name, type, identifier_no, status FROM equipment");

            // 1.13 حجوزات المعدات
            $stats['equipment_bookings'] = DB::select("SELECT equipment_id, work_item_id, booked_by, start_date, end_date, status, notes FROM equipment_bookings");

            // 1.14 صيانة المعدات
            $stats['equipment_maintenances'] = DB::select("SELECT equipment_id, start_date, end_date, type, description FROM equipment_maintenances");

            // 1.15 العقود
            $stats['contracts'] = DB::select("SELECT project_id, contract_no, title, contract_date, start_date, end_date, contract_value, currency, status FROM contracts");

            // 1.16 المستندات
            $stats['documents'] = DB::select("SELECT project_id, type, title, created_at FROM documents");

            // 1.17 إصدارات المستندات
            $stats['document_versions'] = DB::select("SELECT document_id, version_no, file_path FROM document_versions");

            // 1.18 التعليقات
            $stats['comments'] = DB::select("SELECT work_item_id, user_id, comment, created_at FROM comments");

            // 1.19 الإشعارات
            $stats['notifications'] = DB::select("SELECT user_id, project_id, type, title, is_read, created_at FROM notifications");

            // 1.20 سجل النشاطات
            $stats['activity_logs'] = DB::select("SELECT user_id, action, method, endpoint, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 50");

            // 1.21 صور التقدم
            $stats['photos_progress'] = DB::select("SELECT project_id, work_item_id, original_name, created_at FROM photos_progress");

            // 1.22 طلبات تحديث التقدم
            $stats['progress_update_requests'] = DB::select("SELECT project_id, work_item_id, status, type, created_at FROM progress_update_requests");

            // 1.23 مصاريف الورش
            $stats['workshop_expenses'] = DB::select("SELECT project_id, work_item_id, amount, description, created_at FROM workshop_expenses");

            // 1.24 مهندسي المشاريع (مع الأسماء)
            $stats['project_engineers'] = DB::select("
                SELECT
                    pe.project_id,
                    p.name as project_name,
                    pe.user_id,
                    u.name as user_name,
                    pe.role,
                    pe.assigned_at
                FROM project_engineers pe
                LEFT JOIN projects p ON pe.project_id = p.id
                LEFT JOIN users u ON pe.user_id = u.id
            ");

            // 1.25 صور المشاريع
            $stats['project_images'] = DB::select("SELECT project_id, name, image, created_at FROM project_images");

            // 1.26 تصورات AI
            $stats['ai_visualizations'] = DB::select("SELECT project_image_id, generated_image, created_at FROM ai_visualizations");

            // 1.27 تعليقات التصورات
            $stats['ai_visualization_comments'] = DB::select("SELECT ai_visualization_id, user_id, comment, created_at FROM ai_visualization_comments");

            // 1.28 الصلاحيات
            $stats['permissions'] = DB::select("SELECT id, name FROM permissions");

            // 1.29 الأدوار
            $stats['roles'] = DB::select("SELECT id, name FROM roles");

            // 1.30 صلاحيات الأدوار
            $stats['role_has_permissions'] = DB::select("SELECT permission_id, role_id FROM role_has_permissions");

            // 1.31 ربط الأدوار بالمستخدمين
            $stats['model_has_roles'] = DB::select("SELECT role_id, model_id FROM model_has_roles");

            // 1.32 توكنات الوصول
            $stats['personal_access_tokens'] = DB::select("SELECT tokenable_id, name, last_used_at, created_at FROM personal_access_tokens");

            // 1.33 الجلسات
            $stats['sessions'] = DB::select("SELECT user_id, ip_address, last_activity FROM sessions");

            // 1.34 الكاش
            $stats['cache'] = DB::select("SELECT `key`, `value`, expiration FROM cache");

            // 1.35 أقفال الكاش
            $stats['cache_locks'] = DB::select("SELECT `key`, owner, expiration FROM cache_locks");

            // 1.36 الوظائف
            $stats['jobs'] = DB::select("SELECT queue, attempts, created_at FROM jobs");

            // ============================================
            // 2. استخراج السياق من المحادثة السابقة
            // ============================================
            $contextSummary = "";
            if (!empty($history)) {
                // نأخذ آخر 4 أدوار (آخر سؤالين وجوابين) لفهم السياق
                $recentHistory = array_slice($history, -4);
                $contextSummary = "تاريخ المحادثة الأخير:\n";
                foreach ($recentHistory as $turn) {
                    $role = $turn['role'] === 'user' ? 'المستخدم' : 'المساعد';
                    $contextSummary .= "- {$role}: " . substr($turn['text'], 0, 200) . "\n";
                }
            }

            // ============================================
            // 3. تعليمات النظام (مدمجة داخل السؤال بدلاً من system_instruction)
            // ============================================
            $systemPrompt = "
أنت خبير استراتيجي في إدارة مشاريع الإكساء والبناء، تتحدث العربية الفصحى.

**قاعدة ذهبية رقم 1:** إذا كان سؤال المستخدم يحتوي على ضمائر (هو، هي، هذا، هذه، مرتبط، خاص بـ، تابع لـ، مربوط بـ)، **يجب** أن ترجع إلى تاريخ المحادثة لتعرف المقصود. **لا تسأل المستخدم عن توضيح** إذا كان الكيان المذكور موجوداً بوضوح في الأسئلة أو الأجوبة السابقة.

**قاعدة ذهبية رقم 2:** إذا قال المستخدم سابقاً 'شو اسم المساعد؟' وأجبته بـ 'سارة'، ثم سأل 'بأي مشروع مرتبطة؟'، فأنت تفهم تلقائياً أن المقصود هو 'سارة'. هذا واجب، وليس اختياراً.

**قاعدة ذهبية رقم 3:** استخدم البيانات المقدمة (JSON) لدعم إجاباتك بالأرقام والحقائق، لكن لا تدعها تشتتك عن فهم سياق المحادثة.

**قاعدة التنسيق الإلزامية:**
1. استخدم **النص الغامق** للعناوين (مثل **المقارنة:** أو **التفاصيل:**).
2. استخدم النقاط (* ) للقوائم.
3. استخدم الأرقام (1.، 2.، 3.) للخطوات المتسلسلة.
4. اترك سطراً فارغاً بين الفقرات.
";

            // ============================================
            // 4. بناء السؤال الحالي مع السياق والتعليمات والبيانات
            // ============================================
            $currentPrompt = "
{$systemPrompt}

---

**📝 السياق المستخلص من المحادثة السابقة (الأهم):**
{$contextSummary}

**⚠️ تنبيه:** إذا كان سؤالك الحالي يحتوي على كلمات مثل 'مرتبطة'، 'هو'، 'هي'، 'هذا'، 'هذه'، فراجع السياق أعلاه لتحديد المقصود. لا تطلب توضيحاً إضافياً.

---

**📊 بيانات قاعدة البيانات (آخر تحديث):**
" . json_encode($stats, JSON_PRETTY_PRINT) . "

---

**❓ سؤال المستخدم الحالي:**
{$userQuestion}

---

**التعليمات النهائية:**
1. أجب مباشرة وبدون مقدمات.
2. إذا كان السؤال يحمل ضميراً، استنتج المقصود من تاريخ المحادثة.
3. اذكر الأسماء والأرقام المحددة من البيانات.
4. كن مختصراً، واستخدم النقاط للتوضيح.

**تعليمات التنسيق النهائية:**
- أخرج الإجابة بتنسيق منظم (عناوين غامقة، نقاط، أرقام).
- لا تدمج المعلومات في سطر واحد.
- استخدم فواصل الأسطر لتوضيح كل فكرة.
";

            // ============================================
            // 5. بناء الـ contents (التاريخ + السؤال الحالي)
            // ============================================
            $contents = [];

            // إضافة التاريخ السابق كامل (لـ Gemini)
            foreach ($history as $turn) {
                // نحرص على استخدام الأدوار الصحيحة: user و model (وليس assistant)
                $role = ($turn['role'] === 'user') ? 'user' : 'model';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $turn['text']]]
                ];
            }

            // إضافة السؤال الحالي مع البيانات والتنبيهات
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $currentPrompt]]
            ];

            // ============================================
            // 6. إرسال الطلب إلى Gemini (بدون system_instruction)
            // ============================================
            $payload = [
                'contents' => $contents
            ];

            $response = Http::timeout(120)->post($this->url, $payload);

            if (!$response->successful()) {
                Log::error('Gemini API Error (Chat)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['success' => false, 'message' => 'فشل الاتصال بخدمة الذكاء الاصطناعي.'];
            }

            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$rawText) {
                return ['success' => false, 'message' => 'لم يتم استلام رد من الذكاء الاصطناعي.'];
            }

            // تنظيف الرد
            $cleanText = preg_replace('/\*\*(.*?)\*\*/', '$1', $rawText);
            $cleanText = preg_replace('/\n{3,}/', "\n\n", $cleanText);
            $cleanText = preg_replace('/\s+/', ' ', $cleanText);
            $cleanText = trim($cleanText);

            return ['success' => true, 'answer' => $cleanText];
        } catch (Exception $e) {
            Log::error('AI Chat Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
