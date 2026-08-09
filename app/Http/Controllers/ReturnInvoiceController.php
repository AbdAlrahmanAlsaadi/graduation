<?php

namespace App\Http\Controllers;

use App\Services\ReturnInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ReturnInvoiceController extends Controller
{
    protected ReturnInvoiceService $returnInvoiceService;

    public function __construct(ReturnInvoiceService $returnInvoiceService)
    {
        $this->returnInvoiceService = $returnInvoiceService;
    }

    // جلب كل الفواتير
    public function index(int $projectId): JsonResponse
    {
        try {
            $result = $this->returnInvoiceService->getProjectReturnInvoices($projectId);
            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
    public function store(Request $request, int $projectId): JsonResponse
    {
        try {
            // ✅ التحقق من البيانات المبسطة (مثل فاتورة الإنشاء)
            $validated = $request->validate([
                'work_item_id' => 'nullable|exists:work_items,id',
                'supplier_name' => 'required|string|max:255',
                'notes' => 'nullable|string', // ستستخدم كـ description
                'items' => 'required|array|min:1',
                'items.*.material_id' => 'required|exists:materials,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.notes' => 'nullable|string', // ملاحظة لكل بند (ستستخدم كـ reason)
            ]);

            // إضافة البيانات التلقائية
            $validated['project_id'] = $projectId;
            $validated['invoice_number'] = 'RET-' . now()->format('YmdHis') . '-' . rand(100, 999);
            $validated['invoice_date'] = now()->toDateString();
            $validated['return_type'] = 'material';
            $validated['description'] = $validated['notes'] ?? 'مرتجع مواد';
            $validated['attachment_path'] = null;
            unset($validated['notes']); // لا نحتاجها بعد الآن

            $result = $this->returnInvoiceService->createReturnInvoice($validated);
            return response()->json($result, $result['status']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
    // حذف فاتورة
    public function destroy(int $projectId, int $id): JsonResponse
    {
        try {
            // التأكد من أن الفاتورة تابعة للمشروع
            $invoice = \App\Models\ReturnInvoice::where('project_id', $projectId)->find($id);
            if (!$invoice) {
                throw new \Exception('Return invoice not found.', 404);
            }

            $result = $this->returnInvoiceService->deleteReturnInvoice($id);
            return response()->json($result, $result['status']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
