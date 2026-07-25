<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItemInvoice;
use App\Models\WorkItemInvoiceItem;

class HistoricalMaterialService
{
    /**
     * Find the most recent project (excluding current) that has invoice data.
     */
    public function findPreviousProjectWithInvoices(int $currentProjectId): ?Project
    {
        $previousInvoice = WorkItemInvoice::query()
            ->where('project_id', '!=', $currentProjectId)
            ->whereHas('items')
            ->latest('invoice_date')
            ->latest('id')
            ->first();

        if (! $previousInvoice) {
            return null;
        }

        return Project::query()
            ->with(['spaces', 'workItems.details'])
            ->find($previousInvoice->project_id);
    }

    /**
     * Get the total historical quantity of a material purchased in a specific project.
     */
    public function getPreviousMaterialQty(string $materialName, int $previousProjectId): float
    {
        return (float) WorkItemInvoiceItem::query()
            ->whereHas('invoice', fn ($q) => $q->where('project_id', $previousProjectId))
            ->where(function ($q) use ($materialName) {
                $q->where('material_name_snapshot', $materialName)
                  ->orWhereHas('material', fn ($m) => $m->where('name', $materialName));
            })
            ->sum('quantity');
    }

    /**
     * Look up the unit price of a material searching back up to 3 previous projects.
     */
    public function getMaterialUnitPrice(int $materialId, string $materialName, int $currentProjectId): ?float
    {
        $item = WorkItemInvoiceItem::query()
            ->whereHas('invoice', fn ($q) => $q->where('project_id', '!=', $currentProjectId))
            ->where(function ($q) use ($materialId, $materialName) {
                $q->where('material_id', $materialId)
                  ->orWhere('material_name_snapshot', $materialName);
            })
            ->whereNotNull('unit_price')
            ->where('unit_price', '>', 0)
            ->latest('created_at')
            ->first();

        return $item ? (float) $item->unit_price : null;
    }
}
