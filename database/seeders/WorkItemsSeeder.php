<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WorkItemDetail;
use App\Models\WorkItem;
use App\Services\Project\ProjectService;
use Illuminate\Database\Seeder;

class WorkItemsSeeder extends Seeder
{
    /**
     * Seed totals for numeric work items.
     *
     * Maps each numeric work-item name to its total-field keys and
     * sample seed values so that progress tracking has something to
     * work against right away.
     */
    private const NUMERIC_TOTALS = [
        'ملابن الأبواب' => [
            'total_wood_doors'    => ['value' => '2', 'unit' => 'count'],
            'total_aluminum_doors'=> ['value' => '1', 'unit' => 'count'],
            'total_windows'       => ['value' => '3', 'unit' => 'count'],
        ],
        'أبواب ونجارة' => [
            'total_doors' => ['value' => '3', 'unit' => 'count'],
        ],
        'ألمنيوم وأبجورات' => [
            'total_aluminum' => ['value' => '2', 'unit' => 'count'],
        ],
    ];

    public function run(): void
    {
        if (! Project::query()->exists() || WorkItem::query()->exists()) {
            return;
        }

        $now = now();

        Project::query()->each(function (Project $project) use ($now) {
            $items = [];

            foreach (WorkItem::DEFAULT_WORK_ITEMS as $index => $item) {
                $items[] = [
                    'project_id'    => $project->id,
                    'name'          => $item['name'],
                    'quality_level' => WorkItem::QUALITY_LEVEL_BASIC,
                    'sort_order'    => $item['sort_order'],
                    'duration_days' => $item['default_duration'],
                    'is_default'    => true,
                    'is_active'     => true,
                    'is_custom'     => false,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                    // 'started_at'    => $item['name'] == 'ملابن الأبواب' ? $now->subDays(100) : null,
                ];
            }

            WorkItem::query()->insert($items);

            // ── Seed totals for numeric work items ──────────────────
            $project->workItems()
                ->whereIn('name', array_keys(self::NUMERIC_TOTALS))
                ->each(function (WorkItem $workItem) {
                    $totals = self::NUMERIC_TOTALS[$workItem->name] ?? [];

                    $details = [];
                    foreach ($totals as $key => $meta) {
                        $details[] = [
                            'key'   => $key,
                            'value' => $meta['value'],
                            'unit'  => $meta['unit'],
                        ];
                    }

                    if ($details) {
                        $workItem->details()->createMany($details);
                    }
                });
        });
    }
}
