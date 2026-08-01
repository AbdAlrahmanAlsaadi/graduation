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

    // إنشاء فاتورة جديدة
    public function store(Request $request, int $projectId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'work_item_id' => 'nullable|exists:work_items,id',
                'invoice_number' => 'required|string|max:50|unique:return_invoices',
                'invoice_date' => 'required|date',
                'supplier_name' => 'required|string|max:255',
                'return_type' => ['required', Rule::in(['material', 'equipment', 'subcontractor', 'other'])],
                'description' => 'nullable|string',
                
                'attachment_path' => 'nullable|string|max:500',
                'items' => 'nullable|array',
                'items.*.material_id' => 'nullable|exists:materials,id',
                'items.*.material_name_snapshot' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.total_price' => 'required|numeric|min:0',
                'items.*.reason' => 'nullable|string',
            ]);

            $validated['project_id'] = $projectId;

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
