<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkItemService
{
    public function createCustomWorkItem(Project $project, array $data): WorkItem
    {
        $currentMax = $project->workItems()->max('order') ?? 0;

        return $project->workItems()->create([
            'name' => $data['name'],
            'order' => $currentMax + 1,
            'is_default' => false,
        ]);
    }

    /**
     * @param array<int, array{id:int, order:int}> $items
     */
    public function reorder(Project $project, array $items): Collection
    {
        DB::transaction(function () use ($project, $items) {
            foreach ($items as $item) {
                WorkItem::query()
                    ->where('project_id', $project->id)
                    ->where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
        });

        return $project->workItems()->orderBy('order')->get();
    }
}
