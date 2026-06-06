<?php

namespace App\Services;

use App\Models\Material;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($workItemType, $materials) {
            $syncData = [];

            foreach ($materials as $item) {
                $material = Material::query()->find($item['material_id']);
                if (! $material) {
                    throw new RuntimeException('Material not found.', 404);
                }

                $syncData[$item['material_id']] = [
                    'work_item_name' => $workItemName,
                    'sort_order' => $item['sort_order'],
                    'is_required' => $item['is_required'],
                ];
            }

            DB::table('work_item_materials')->where('work_item_name', $workItemName)->delete();

            if ($syncData !== []) {
                $now = now();
                $rows = [];

                foreach ($syncData as $materialId => $pivotData) {
                    $rows[] = [
                        'work_item_name' => $pivotData['work_item_name'],
                        'material_id' => $materialId,
                        'sort_order' => $pivotData['sort_order'],
                        'is_required' => $pivotData['is_required'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('work_item_materials')->insert($rows);
            }

            return $this->getMaterialsForWorkItem($workItemName);
        });
    }

    /**
     * Get all materials attached to a work item type sorted by pivot sort_order.
     */
    public function getMaterialsForWorkItem(string $workItemName): Collection
    {
        return Material::query()
            ->select('materials.*')
            ->join('work_item_materials', 'materials.id', '=', 'work_item_materials.material_id')
            ->where('work_item_materials.work_item_name', $workItemName)
            ->orderBy('work_item_materials.sort_order')
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

        if (DB::table('work_item_materials')
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->exists()) {
            throw new RuntimeException('Material already attached to this work item.', 409);
        }

        DB::table('work_item_materials')->insert([
            'work_item_name' => $workItemName,
            'material_id' => $materialId,
            'sort_order' => $sortOrder,
            'is_required' => $isRequired,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->getMaterialsForWorkItem($workItemName);
    }

    /**
     * Detach one material from a work item type.
     */
    public function detachMaterial(string $workItemName, int $materialId): Collection
    {
        if (! DB::table('work_item_materials')
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->exists()) {
            throw new RuntimeException('Material is not attached to this work item.', 404);
        }

        DB::table('work_item_materials')
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
    public function updatePivotData(string $workItemName, int $materialId, array $pivotData): Collection
    {
        if (! Material::query()->find($materialId)) {
            throw new RuntimeException('Material not found.', 404);
        }

        if (! DB::table('work_item_materials')
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->exists()) {
            throw new RuntimeException('Material is not attached to this work item.', 404);
        }

        DB::table('work_item_materials')
            ->where('work_item_name', $workItemName)
            ->where('material_id', $materialId)
            ->update(array_merge($pivotData, ['updated_at' => now()]));

        return $this->getMaterialsForWorkItem($workItemName);
    }
}
