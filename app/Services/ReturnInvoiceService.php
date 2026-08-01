<?php

namespace App\Services;

use App\Models\ReturnInvoice;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnInvoiceService
{
    // جلب كل فواتير المرتجعات لمشروع
    public function getProjectReturnInvoices(int $projectId): array
    {
        $project = Project::find($projectId);
        if (!$project) {
            throw new \Exception('Project not found.', 404);
        }

        $invoices = ReturnInvoice::where('project_id', $projectId)
            ->with(['items', 'creator'])
            ->orderBy('invoice_date', 'desc')
            ->get();

        return [
            'status' => 200,
            'message' => 'Return invoices retrieved successfully.',
            'data' => $invoices,
        ];
    }

    // إنشاء فاتورة مرتجعات جديدة (مع تفاصيلها)
    public function createReturnInvoice(array $data): array
    {
        $project = Project::find($data['project_id']);
        if (!$project) {
            throw new \Exception('Project not found.', 404);
        }

        // التحقق من رقم الفاتورة
        if (ReturnInvoice::where('invoice_number', $data['invoice_number'])->exists()) {
            throw new \Exception('Invoice number already exists.', 422);
        }

        $data['created_by'] = Auth::id();

        // ✅ حساب total_amount من items
        $totalAmount = 0;
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $totalAmount += (float) ($item['total_price'] ?? 0);
            }
        }
        $data['total_amount'] = $totalAmount; // نجبر القيمة على المجموع

        $invoice = DB::transaction(function () use ($data) {
            $invoice = ReturnInvoice::create($data);

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $invoice->items()->create($item);
                }
            }

            return $invoice;
        });

        return [
            'status' => 201,
            'message' => 'Return invoice created successfully.',
            'data' => $invoice->load('items'),
        ];
    }

    // حساب إجمالي خصم المرتجعات لمشروع (القيمة اللي رح تخصم من التكلفة)
    public function getTotalReturnsDeduction(int $projectId): float
    {
        return (float) ReturnInvoice::where('project_id', $projectId)->sum('total_amount');
    }

    // حذف فاتورة مرتجعات
    public function deleteReturnInvoice(int $id): array
    {
        $invoice = ReturnInvoice::find($id);
        if (!$invoice) {
            throw new \Exception('Return invoice not found.', 404);
        }

        $invoice->delete();

        return [
            'status' => 200,
            'message' => 'Return invoice deleted successfully.',
            'data' => null,
        ];
    }
}
