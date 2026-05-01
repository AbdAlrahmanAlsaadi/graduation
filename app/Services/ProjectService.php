<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public const DEFAULT_WORK_ITEMS = [
        'ملابن الأبواب',
        'تمديدات كهرباء',
        'تمديدات صحية',
        'طينة / لياسة',
        'بلاط أرضيات',
        'سيراميك جدران / أسقف',
        'جبس بورد',
        'دهان',
        'أبواب ونجارة',
        'ألمنيوم وأبجورات',
        'تشطيبات نهائية',
    ];

    public function createProjectWithDefaults(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::query()->create($data);
            $now = now();
            $workItems = [];

            foreach (self::DEFAULT_WORK_ITEMS as $index => $name) {
                $workItems[] = [
                    'project_id' => $project->id,
                    'name' => $name,
                    'order' => $index + 1,
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            WorkItem::query()->insert($workItems);

            $project->load([
                'workItems' => fn ($query) => $query->orderBy('order'),
            ]);

            return $project;
        });
    }
}
