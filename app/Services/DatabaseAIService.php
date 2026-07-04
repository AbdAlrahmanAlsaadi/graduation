<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DatabaseAIService
{
    protected $apiKey;
    protected $url;

    // وصف كامل لهيكل قاعدة البيانات (جميع الجداول)
    protected $schema = "
    قاعدة البيانات تحتوي على الجداول التالية:

    1. users: id, name, email, email_verified_at, internal_id, status (active/inactive), password, remember_token, fcm_token, created_at, updated_at

    2. projects: id, name, location, latitude, longitude, apartment_area, height, status (planned/ongoing/completed), project_manager_id, assistant_engineer_id, owner_id, created_by, updated_by, started_at, completed_at, created_at, updated_at, deleted_at

    3. work_items: id, project_id, parent_id, name, quality_level (basic/good/premium/custom), sort_order, duration_days, is_default, is_active, is_custom, status (planned/ongoing/completed), weight, started_at, completed_at, created_at, updated_at, deleted_at

    4. spaces: id, project_id, type (room/salon/kitchen/bathroom/toilet/corridor/entrance/shed/storage), wall_area, wall_finish_type (paint/ceramic/gypsum/none/custom), ceiling_finish_type (paint/ceramic/gypsum/none/custom), toilet_type (none/arabic/western), ceiling_area, is_shed_floor_tiled, created_at, updated_at, deleted_at

    5. materials: id, name, unit, created_at, updated_at

    6. work_item_materials: id, work_item_name, material_id, created_at, updated_at

    7. work_item_details: id, work_item_id, key, value, unit, created_at, updated_at

    8. work_item_invoices: id, project_id, work_item_id, supplier_name, invoice_number, invoice_date, invoice_image, total_amount, notes, created_by, created_at, updated_at, deleted_at

    9. work_item_invoice_items: id, invoice_id, material_id, material_name_snapshot, quantity, unit, unit_price, total_price, notes, created_at, updated_at, deleted_at

    10. work_item_labor_costs: id, project_id, work_item_id, workshop_name, amount, notes, created_by, created_at, updated_at, deleted_at

    11. work_item_material_templates: id, work_item_type, material_name, unit, default_qty, category, created_at, updated_at

    12. equipment: id, name, type, identifier_no, status (Available/Maintenance/Booked), created_at, updated_at

    13. equipment_bookings: id, equipment_id, work_item_id, booked_by, start_date, end_date, status (active/completed/cancelled), notes, created_at, updated_at

    14. equipment_maintenances: id, equipment_id, start_date, end_date, type (Breakdown/Preventive), description, created_at, updated_at

    15. contracts: id, project_id, owner_id, contract_no, title, contract_date, start_date, end_date, contract_value, currency, status (Draft/Active/Completed/Cancelled), company_signature, owner_signature, description, created_at, updated_at

    16. documents: id, project_id, type (document/contract), title, created_at, updated_at

    17. document_versions: id, document_id, version_no, file_path, created_at, updated_at

    18. comments: id, work_item_id, user_id, comment, created_at, updated_at

    19. notifications: id, user_id, project_id, project_work_item_id, type, title, body, is_read, read_at, data, created_at, updated_at

    20. activity_logs: id, user_id, action, method, endpoint, entity_type, entity_id, description, ip_address, created_at, updated_at

    21. photos_progress: id, project_id, work_item_id, file_path, original_name, created_at, updated_at

    22. progress_update_requests: id, project_id, work_item_id, requested_by, reviewed_by, status (pending/approved/rejected), type, payload, comment, reviewed_at, created_at, updated_at

    23. workshop_expenses: id, project_id, work_item_id, created_by, amount, description, created_at, updated_at

    24. project_engineers: id, project_id, user_id, role, assigned_at, created_at, updated_at

    25. project_images: id, project_id, created_by, name, image, created_at, updated_at

    26. ai_visualizations: id, project_image_id, reference_images, generated_image, created_at, updated_at

    27. ai_visualization_comments: id, ai_visualization_id, user_id, comment, created_at, updated_at

    28. permissions: id, name, guard_name, created_at, updated_at

    29. roles: id, name, guard_name, created_at, updated_at

    30. model_has_roles: role_id, model_type, model_id

    31. model_has_permissions: permission_id, model_type, model_id

    32. role_has_permissions: permission_id, role_id

    33. personal_access_tokens: id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at

    34. sessions: id, user_id, ip_address, user_agent, payload, last_activity

    35. cache: key, value, expiration

    36. cache_locks: key, owner, expiration

    37. jobs: id, queue, payload, attempts, reserved_at, available_at, created_at
    ";





    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}";
    }

    public function ask($userQuestion)
    {
        try {
            $stats = [];

            // 1. users
            $stats['users'] = DB::select("SELECT id, name, email, status, created_at FROM users");

            // 2. projects
            // استعلام المشاريع مع جميع المعرفات (project_manager_id, assistant_engineer_id, owner_id)
            $stats['projects'] = DB::select("
    SELECT
        id,
        name,
        location,
        apartment_area,
        height,
        status,
        project_manager_id,
        assistant_engineer_id,
        owner_id,
        created_at,
        updated_at
    FROM projects
    WHERE deleted_at IS NULL
");
            // 3. work_items
            $stats['work_items'] = DB::select("SELECT id, project_id, name, status, duration_days, weight, created_at, updated_at FROM work_items WHERE deleted_at IS NULL");

            // 4. spaces
            $stats['spaces'] = DB::select("SELECT id, project_id, type, wall_area, ceiling_area, wall_finish_type, ceiling_finish_type FROM spaces WHERE deleted_at IS NULL");

            // 5. materials
            $stats['materials'] = DB::select("SELECT id, name, unit FROM materials");

            // 6. work_item_materials
            $stats['work_item_materials'] = DB::select("SELECT work_item_name, material_id FROM work_item_materials");

            // 7. work_item_details (تم إضافة backticks حول key و value)
            $stats['work_item_details'] = DB::select("SELECT work_item_id, `key`, `value`, unit FROM work_item_details");

            // 8. work_item_invoices
            $stats['work_item_invoices'] = DB::select("SELECT id, project_id, work_item_id, supplier_name, invoice_number, invoice_date, total_amount, notes FROM work_item_invoices WHERE deleted_at IS NULL");

            // 9. work_item_invoice_items
            $stats['work_item_invoice_items'] = DB::select("SELECT invoice_id, material_name_snapshot, quantity, unit, unit_price, total_price FROM work_item_invoice_items WHERE deleted_at IS NULL");

            // 10. work_item_labor_costs
            $stats['work_item_labor_costs'] = DB::select("SELECT project_id, work_item_id, workshop_name, amount, notes FROM work_item_labor_costs WHERE deleted_at IS NULL");

            // 11. work_item_material_templates
            $stats['work_item_material_templates'] = DB::select("SELECT work_item_type, material_name, unit, default_qty, category FROM work_item_material_templates");

            // 12. equipment
            $stats['equipment'] = DB::select("SELECT id, name, type, identifier_no, status FROM equipment");

            // 13. equipment_bookings
            $stats['equipment_bookings'] = DB::select("SELECT equipment_id, work_item_id, booked_by, start_date, end_date, status, notes FROM equipment_bookings");

            // 14. equipment_maintenances
            $stats['equipment_maintenances'] = DB::select("SELECT equipment_id, start_date, end_date, type, description FROM equipment_maintenances");

            // 15. contracts
            $stats['contracts'] = DB::select("SELECT project_id, contract_no, title, contract_date, start_date, end_date, contract_value, currency, status FROM contracts");

            // 16. documents
            $stats['documents'] = DB::select("SELECT project_id, type, title, created_at FROM documents");

            // 17. document_versions
            $stats['document_versions'] = DB::select("SELECT document_id, version_no, file_path FROM document_versions");

            // 18. comments
            $stats['comments'] = DB::select("SELECT work_item_id, user_id, comment, created_at FROM comments");

            // 19. notifications
            $stats['notifications'] = DB::select("SELECT user_id, project_id, type, title, is_read, created_at FROM notifications");

            // 20. activity_logs
            $stats['activity_logs'] = DB::select("SELECT user_id, action, method, endpoint, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 50");

            // 21. photos_progress
            $stats['photos_progress'] = DB::select("SELECT project_id, work_item_id, original_name, created_at FROM photos_progress");

            // 22. progress_update_requests
            $stats['progress_update_requests'] = DB::select("SELECT project_id, work_item_id, status, type, created_at FROM progress_update_requests");

            // 23. workshop_expenses
            $stats['workshop_expenses'] = DB::select("SELECT project_id, work_item_id, amount, description, created_at FROM workshop_expenses");

            // 24. project_engineers
            $stats['project_engineers'] = DB::select("SELECT project_id, user_id, role, assigned_at FROM project_engineers");

            // 25. project_images
            $stats['project_images'] = DB::select("SELECT project_id, name, image, created_at FROM project_images");

            // 26. ai_visualizations
            $stats['ai_visualizations'] = DB::select("SELECT project_image_id, generated_image, created_at FROM ai_visualizations");

            // 27. ai_visualization_comments
            $stats['ai_visualization_comments'] = DB::select("SELECT ai_visualization_id, user_id, comment, created_at FROM ai_visualization_comments");

            // 28. permissions
            $stats['permissions'] = DB::select("SELECT id, name FROM permissions");

            // 29. roles
            $stats['roles'] = DB::select("SELECT id, name FROM roles");

            // 30. role_has_permissions
            $stats['role_has_permissions'] = DB::select("SELECT permission_id, role_id FROM role_has_permissions");

            // 31. model_has_roles
            $stats['model_has_roles'] = DB::select("SELECT role_id, model_id FROM model_has_roles");

            // 32. personal_access_tokens
            $stats['personal_access_tokens'] = DB::select("SELECT tokenable_id, name, last_used_at, created_at FROM personal_access_tokens");

            // 33. sessions
            $stats['sessions'] = DB::select("SELECT user_id, ip_address, last_activity FROM sessions");

            // 34. cache (key محجوزة)
            $stats['cache'] = DB::select("SELECT `key`, `value`, expiration FROM cache");

            // 35. cache_locks (key محجوزة)
            $stats['cache_locks'] = DB::select("SELECT `key`, owner, expiration FROM cache_locks");

            // 36. jobs
            $stats['jobs'] = DB::select("SELECT queue, attempts, created_at FROM jobs");

            // ====== بناء الـ Prompt ======
            $prompt = "
            أنت خبير استراتيجي في إدارة مشاريع الإكساء والبناء.

            هذه هي بيانات نظام إدارة المشاريع بالكامل (على شكل JSON):
            " . json_encode($stats, JSON_PRETTY_PRINT) . "

            وهيكل قاعدة البيانات كالتالي:
            {$this->schema}

            سؤال المستخدم: {$userQuestion}

            **مطلوب:** أجب على سؤال المستخدم مباشرة وبشكل واضح ومفصل، باستخدام الأرقام والحقائق المستخلصة من البيانات المقدمة.
            - كن عملياً ومباشراً.
            - استخدم اللغة العربية الفصحى المبسطة.
            - لا تخرج عن موضوع السؤال.
            - إذا كان السؤال عاماً (مثل: 'حلل النظام')، أعطِ تحليلاً شاملاً مع توقعات وتوصيات.
            - أخرج إجابتك على شكل **نص عادي** (وليس JSON). فقط اكتب النص الذي تريد إيصاله للمستخدم.
            ";

            // ====== إرسال الطلب إلى Gemini ======
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            $response = Http::timeout(300)->post($this->url, $payload);

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

            // ====== تنظيف الرد ======
            $cleanText = preg_replace('/\*\*(.*?)\*\*/', '$1', $rawText); // إزالة **bold**
            $cleanText = preg_replace('/\n{3,}/', "\n\n", $cleanText);   // تقليل الأسطر الفارغة
            $cleanText = preg_replace('/\s+/', ' ', $cleanText);         // إزالة المسافات الزائدة
            $cleanText = trim($cleanText);

            return ['success' => true, 'answer' => $cleanText];
        } catch (Exception $e) {
            Log::error('AI Chat Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
