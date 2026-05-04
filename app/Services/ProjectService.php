<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    public function updateProject(Project $project, array $data): Project
    {
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException('Unauthorized.', 401);
        }

        if (! $user->hasRole('company_admin')) {
            $ownerId = array_key_exists('owner_id', $data)
                ? $data['owner_id']
                : $project->owner_id;

            if (
                (int) $data['project_manager_id'] !== (int) $project->project_manager_id
                || (int) $data['assistant_engineer_id'] !== (int) $project->assistant_engineer_id
                || (int) ($ownerId ?? 0) !== (int) ($project->owner_id ?? 0)
            ) {
                throw new RuntimeException('Only admin can modify project assignments.', 403);
            }
        }

        $project->update($data);

        return $project->fresh();
    }
}
