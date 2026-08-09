<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ReturnInvoice;
use App\Models\Project;
use App\Models\ReturnInvoiceItem;
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


    public function createReturnInvoice(array $data): array
    {
        $project = Project::find($data['project_id']);
        if (!$project) {
            throw new \Exception('Project not found.', 404);
        }

        // التحقق من رقم الفاتورة (لتجنب التكرار)
        if (ReturnInvoice::where('invoice_number', $data['invoice_number'])->exists()) {
            throw new \Exception('Invoice number already exists.', 422);
        }

        $data['created_by'] = Auth::id();

        // معالجة البنود (items) لتعبئة الحقول الناقصة
        $items = [];
        $totalAmount = 0;

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // جلب المادة من قاعدة البيانات
                $material = Material::find($item['material_id']);
                if (!$material) {
                    throw new \Exception("Material with ID {$item['material_id']} not found.");
                }

                $totalPrice = $item['quantity'] * $item['unit_price'];
                $items[] = [
                    'material_id' => $item['material_id'],
                    'material_name_snapshot' => $material->name, // من قاعدة البيانات
                    'quantity' => $item['quantity'],
                    'unit' => $material->unit ?? 'قطعة', // من قاعدة البيانات أو افتراضي
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'reason' => $item['notes'] ?? null, // ملاحظة البند
                    'item_type' => 'material', // افتراضي
                ];
                $totalAmount += $totalPrice;
            }
        }

        $data['total_amount'] = $totalAmount;

        $invoice = DB::transaction(function () use ($data, $items) {
            // إنشاء رأس الفاتورة
            $invoice = ReturnInvoice::create($data);

            // إنشاء البنود
            foreach ($items as $itemData) {
                $itemData['return_invoice_id'] = $invoice->id;
                $invoice->items()->create($itemData);
            }

            return $invoice;
        });

        return [
            'status' => 201,
            'message' => 'Return invoice created successfully.',
            'data' => $invoice->load('items'),
        ];
    }

    public function getTotalReturnsDeduction(int $projectId): float
    {
        return (float) ReturnInvoiceItem::whereHas('returnInvoice', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })->sum('total_price');
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
