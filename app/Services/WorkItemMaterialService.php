<?php

namespace App\Services;

use App\Models\Material;
use App\Models\WorkItemMaterial;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class WorkItemMaterialService
{
    /**
     * Replace all materials attached to a work item type using pivot payload data.
    *
     * @param array<int, array{material_id:int, sort_order:int, is_required:bool}> $materials
     */
    public function syncMaterials(string $workItemName, array $materials): Collection
    {
        return WorkItemMaterial::query()->getConnection()->transaction(function () use ($workItemName, $materials) {
            WorkItemMaterial::query()
                ->where('work_item_name', $workItemName)
                ->delete();

            foreach ($materials as $item) {
                $material = Material::query()->find($item['material_id']);

                if (! $material) {
                    throw new RuntimeException('Material not found.', 404);
                }

                WorkItemMaterial::query()->create([
                    'work_item_name' => $workItemName,
                    'material_id' => $item['material_id'],
                    'sort_order' => $item['sort_order'],
                    'is_required' => $item['is_required'],
                ]);
            }

            return $this->getMaterialsForWorkItem($workItemName);
        });
    }

    /**
     * Get all materials attached to a work item type sorted by pivot sort_order.
     */
    public function getMaterialsForWorkItem(string $workItemName): Collection
    {
        return WorkItemMaterial::query()
            ->with('material')
            ->where('work_item_name', $workItemName)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Attach one material to a work item type.
     */
    public function attachMaterial(string $workItemName, int $materialId, int $sortOrder = 0, bool $isRequired = true): Collection
    {
        $material = Material::query()->find($materialId);

        if (! $material) {
            throw new RuntimeException('Material not found.', 404);
        }

        if (WorkItemMaterial::query()
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->exists()) {
            throw new RuntimeException('Material already attached to this work item.', 409);
        }

        WorkItemMaterial::query()->create([
            'work_item_name' => $workItemName,
            'material_id' => $materialId,
            'sort_order' => $sortOrder,
            'is_required' => $isRequired,
        ]);

        return $this->getMaterialsForWorkItem($workItemName);
    }

    /**
     * Detach one material from a work item type.
     */
    public function detachMaterial(string $workItemName, int $materialId): Collection
    {
        if (! WorkItemMaterial::query()
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->exists()) {
            throw new RuntimeException('Material is not attached to this work item.', 404);
        }

        WorkItemMaterial::query()
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->delete();

        return $this->getMaterialsForWorkItem($workItemName);
    }

    /**
     * Update pivot data for an already attached material on a work item type.
     *
     * @param array{sort_order?:int, is_required?:bool} $pivotData
     */
    public function updatePivotData(string $workItemName, int $materialId, array $pivotData)
    {
        if (! Material::query()->find($materialId)) {
            throw new RuntimeException('Material not found.', 404);
        }

        $workItemMaterial = WorkItemMaterial::query()
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->first();

        if (! $workItemMaterial) {
            throw new RuntimeException('Material is not attached to this work item.', 404);
        }

        $workItemMaterial->fill($pivotData);
        $workItemMaterial->save();

        $data = WorkItemMaterial::query()
            ->with('material')
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->firstOrFail();

        return $data;
    }
}
