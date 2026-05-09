<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WorkItemDetail;
use App\Models\WorkItem;
use App\Services\ProjectService;
use Illuminate\Database\Seeder;

class WorkItemsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Project::query()->exists() || WorkItem::query()->exists()) {
            return;
        }

        $now = now();

        Project::query()->each(function (Project $project) use ($now) {
            $items = [];

            foreach (ProjectService::DEFAULT_WORK_ITEMS as $index => $name) {
                $items[] = [
                    'project_id' => $project->id,
                    'name' => $name,
                    'quality_level' => WorkItem::QUALITY_LEVEL_BASIC,
                    'sort_order' => $index + 1,
                    'is_default' => true,
                    'is_active' => true,
                    'is_custom' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            WorkItem::query()->insert($items);

            if (! WorkItemDetail::query()->exists()) {
                $sample = $project->workItems()->create([
                    'name' => 'Tile Installation',
                    'quality_level' => WorkItem::QUALITY_LEVEL_GOOD,
                    'duration_days' => 4,
                    'sort_order' => count($items) + 1,
                    'is_default' => false,
                    'is_active' => true,
                    'is_custom' => true,
                ]);

                $sample->details()->createMany([
                    [
                        'key' => 'tile_length',
                        'value' => '30',
                        'unit' => 'cm',
                    ],
                    [
                        'key' => 'tile_width',
                        'value' => '30',
                        'unit' => 'cm',
                    ],
                ]);
            }
        });
    }
}
