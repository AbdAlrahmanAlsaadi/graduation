<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProjectService
{
    public const DEFAULT_WORK_ITEMS = [
        'ملابن الأبواب',
        'تمديدات كهرباء بياض',
        'تمديدات كهرباء سواد',
        'تمديدات صحية بياض',
        'تمديدات صحية سواد',
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
            $data['status'] = Project::STATUS_PLANNED;
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

    /**
     * Start a project if it is planned.
     */
    public function startProject(Project $project): Project
    {
        if ($project->isCompleted()) {
            throw new RuntimeException('Project already completed.', 400);
        }

        if ($project->isOngoing()) {
            return $project;
        }

        return DB::transaction(function () use ($project) {
            if ($project->started_at === null) {
                $project->started_at = now();
            }
            $project->status = Project::STATUS_ONGOING;
            $project->save();

            return $project->fresh();
        });
    }

    /**
     * Complete a project if it has been started.
     */
    public function completeProject(Project $project): Project
    {
        if ($project->isCompleted()) {
            return $project;
        }

        if ($project->isPlanned()) {
            throw ValidationException::withMessages([
                'status' => 'Project must be started before completing.',
            ])->status(400);
        }

        return DB::transaction(function () use ($project) {
            $project->status = Project::STATUS_COMPLETED;
            if ($project->completed_at === null) {
                $project->completed_at = now();
            }
            $project->save();

            return $project->fresh();
        });
    }
    public function search($request): array
    {
        $request->validated();

        $projects = Project::query()

            ->with([
                'projectManager',
                'assistantEngineer',
                'owner',
                'workItems',
                'workItems.equipmentBookings.equipment',
            ])

            ->where(function ($query) use ($request) {

                $query->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('location', 'like', '%' . $request->keyword . '%');
            })

            ->paginate(10);

        if ($projects->total() === 0) {
            throw new \Exception('No projects found.', 404);
        }

        $projects->getCollection()->transform(function ($project) {

            $equipment = collect();

            foreach ($project->workItems as $workItem) {

                foreach ($workItem->equipmentBookings as $booking) {

                    if ($booking->equipment) {

                        $equipment->push([
                            'id' => $booking->equipment->id,
                            'name' => $booking->equipment->name,
                            'type' => $booking->equipment->type,
                            'identifier_no' => $booking->equipment->identifier_no,
                            'status' => $booking->equipment->status,
                        ]);
                    }
                }
            }

            return [

                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'status' => $project->status,

                'manager' => $project->projectManager ? [
                    'id' => $project->projectManager->id,
                    'name' => $project->projectManager->name,
                    'internal_id' => $project->projectManager->internal_id,
                ] : null,

                'assistant_engineer' => $project->assistantEngineer ? [
                    'id' => $project->assistantEngineer->id,
                    'name' => $project->assistantEngineer->name,
                    'internal_id' => $project->assistantEngineer->internal_id,
                ] : null,

                'owner' => $project->owner ? [
                    'id' => $project->owner->id,
                    'name' => $project->owner->name,
                    'internal_id' => $project->owner->internal_id,
                ] : null,

                'work_items_count' => $project->workItems->count(),

                'equipment_count' => $equipment->unique('id')->count(),

                'work_items' => $project->workItems->map(function ($item) {

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quality_level' => $item->quality_level,
                        'duration_days' => $item->duration_days,
                        'is_active' => $item->is_active,
                    ];
                })->values(),

                'equipment' => $equipment
                    ->unique('id')
                    ->values(),
            ];
        });

        return [
            'message' => 'Projects fetched successfully.',

            'projects' => $projects->items(),

            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],

            'status' => 200,
        ];
    }}
