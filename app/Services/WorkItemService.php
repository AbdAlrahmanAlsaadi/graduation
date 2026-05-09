<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkItemService
{
    public function createCustomWorkItem(Project $project, array $data): WorkItem
    {
        return DB::transaction(function () use ($project, $data) {
            $currentMax = $project->workItems()->max('sort_order') ?? 0;
            $details = $data['details'] ?? null;

            $workItem = $project->workItems()->create([
                'name' => $data['name'],
                'quality_level' => $data['quality_level'] ?? WorkItem::QUALITY_LEVEL_CUSTOM,
                'duration_days' => $data['duration_days'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $currentMax + 1,
                'is_default' => false,
                'is_active' => $data['is_active'] ?? true,
                'is_custom' => true,
            ]);

            if ($details !== null) {
                $workItem->syncDetails($details);
            }

            return $workItem->load('details');
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateWorkItem(Project $project, WorkItem $workItem, array $data): WorkItem
    {
        if ((int) $workItem->project_id !== (int) $project->id) {
            throw new RuntimeException('Work item not found.', 404);
        }

        return DB::transaction(function () use ($workItem, $data) {
            $detailsProvided = array_key_exists('details', $data);
            $details = $data['details'] ?? [];
            unset($data['details']);

            if ($data !== []) {
                $workItem->update($data);
            }

            if ($detailsProvided) {
                $workItem->syncDetails($details ?? []);
            }

            return $workItem->load('details');
        });
    }

    /**
     * @param array<int, array{id:int, sort_order:int}> $items
     */
    public function reorder(Project $project, array $items): Collection
    {
        DB::transaction(function () use ($project, $items) {
            foreach ($items as $item) {
                WorkItem::query()
                    ->where('project_id', $project->id)
                    ->where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return $project->workItems()->orderBy('sort_order')->get();
    }
}
