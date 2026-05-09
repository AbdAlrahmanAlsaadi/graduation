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
            $userId = Auth::id();
            if ($userId) {
                $data['created_by'] = $data['created_by'] ?? $userId;
                $data['updated_by'] = $data['updated_by'] ?? $userId;
            }

            $project = Project::query()->create($data);
            $now = now();
            $workItems = [];

            foreach (self::DEFAULT_WORK_ITEMS as $index => $name) {
                $workItems[] = [
                    'project_id' => $project->id,
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'quality_level' => WorkItem::QUALITY_LEVEL_BASIC,
                    'is_default' => true,
                    'is_active' => true,
                    'is_custom' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            WorkItem::query()->insert($workItems);

            $project->load([
                'workItems' => fn ($query) => $query->orderBy('sort_order'),
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
            $projectManagerId = array_key_exists('project_manager_id', $data)
                ? $data['project_manager_id']
                : $project->project_manager_id;
            $assistantEngineerId = array_key_exists('assistant_engineer_id', $data)
                ? $data['assistant_engineer_id']
                : $project->assistant_engineer_id;
            $ownerId = array_key_exists('owner_id', $data)
                ? $data['owner_id']
                : $project->owner_id;

            if (
                (int) $projectManagerId !== (int) $project->project_manager_id
                || (int) $assistantEngineerId !== (int) $project->assistant_engineer_id
                || (int) ($ownerId ?? 0) !== (int) ($project->owner_id ?? 0)
            ) {
                throw new RuntimeException('Only admin can modify project assignments.', 403);
            }
        }

        $data['updated_by'] = $user->id;

        $project->update($data);

        return $project->fresh();
    }
}
