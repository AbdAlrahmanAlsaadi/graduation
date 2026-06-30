<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
            $workItem->save();

            return $workItem->load('details');
        });
    }

    public function updateDetails(
        Project $project,
        WorkItem $workItem,
        array $data
    ): WorkItem {

        $template = config("work_item_templates.{$workItem->name}");

        if (! $template) {
            abort(
                400,
                "No template defined for work item name: {$workItem->name}"
            );
        }

        $details = [];

        foreach ($template as $key => $meta) {

            if (! array_key_exists($key, $data)) {
                abort(
                    422,
                    "Missing required field: {$key}"
                );
            }

            $details[] = [
                'key' => $key,
                'value' => $data[$key],
                'unit' => $meta['unit'] ?? null,
            ];
        }

        $user = Auth::user();
        $workItem->syncDetails(
            $details,
        );

        return $workItem->fresh('details');
    }

    /**
     * @param array<int, array{id:int, sort_order:int}> $items
     */
    public function reorder(Project $project, array $items): Collection
    {
        // جلب جميع البنود الحالية مع أسمائها وترتيبها
        $workItems = $project->workItems()->select('id', 'name', 'sort_order')->get();

        $existingOrders = $workItems->pluck('sort_order', 'id')->all();
        $idToName = $workItems->pluck('name', 'id')->all();

        // دمج التغييرات المطلوبة مع الحالي
        $finalOrders = $existingOrders;
        foreach ($items as $item) {
            if (!array_key_exists($item['id'], $existingOrders)) {
                throw new \InvalidArgumentException("البند رقم {$item['id']} غير موجود في المشروع.");
            }
            $finalOrders[$item['id']] = $item['sort_order'];
        }

        foreach (WorkItem::DEPENDENCIES as [$prerequisiteName, $dependentName]) {
            $prerequisiteId = array_search($prerequisiteName, $idToName);
            $dependentId = array_search($dependentName, $idToName);

            if ($prerequisiteId === false || $dependentId === false) {
                continue;
            }

            if ($finalOrders[$prerequisiteId] >= $finalOrders[$dependentId]) {
                throw new \InvalidArgumentException(
                    "لا يمكن أن يكون \"{$dependentName}\" قبل أو في نفس ترتيب \"{$prerequisiteName}\". " .
                    "الترتيب الهندسي الصحيح يقتضي أن يسبق \"{$prerequisiteName}\" البند \"{$dependentName}\"."
                );
            }
        }

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

    /**
     * Start a work item if it is planned.
     */
    public function startWorkItem(Project $project, WorkItem $workItem): WorkItem
    {
        if ((int) $workItem->project_id !== (int) $project->id) {
            throw new RuntimeException('Work item not found.', 404);
        }

        if ($workItem->isCompleted()) {
            throw new RuntimeException('Work item already completed.', 400);
        }

        if ($workItem->isOngoing()) {
            return $workItem;
        }

        return DB::transaction(function () use ($workItem) {
            if ($workItem->started_at === null) {
                $workItem->started_at = now();
            }
            $workItem->status = WorkItem::STATUS_ONGOING;
            $workItem->save();

            return $workItem->fresh();
        });
    }

    /**
     * Complete a work item if it has been started.
     */
    public function completeWorkItem(Project $project, WorkItem $workItem): WorkItem
    {
        if ((int) $workItem->project_id !== (int) $project->id) {
            throw new RuntimeException('Work item not found.', 404);
        }

        if ($workItem->isPlanned()) {
            throw new RuntimeException('Work item must be started before completing.', 400);
        }

        if ($workItem->isCompleted()) {
            return $workItem;
        }

        if (app(WorkItemProgressService::class)->computeWorkItemPercent($workItem) < 100) {
            throw new RuntimeException('Work item not completed.', 400);
        }

        return DB::transaction(function () use ($workItem) {
            $workItem->status = WorkItem::STATUS_COMPLETED;
            if ($workItem->completed_at === null) {
                $workItem->completed_at = now();
            }
            $workItem->save();

            return $workItem->fresh();
        });
    }







    public function pendingDetails(): array
    {
        $user = auth()->user();

        $query = WorkItemDetail::query()
            ->with([
                'workItem.project',
                'workItem.progressPhotos',
            ])
            ->where('approval_status', 'pending');

        if ($user->hasRole('project_manager')) {

            $query->whereHas(
                'workItem.project',
                function ($q) use ($user) {

                    $q->where(
                        'project_manager_id',
                        $user->id
                    );
                }
            );
        }

        $details = $query
            ->latest()
            ->get();

        $grouped = $details->groupBy('work_item_id');

        return [

            'message' => 'Pending updates fetched successfully.',

            'data' => $grouped->map(function ($items) {

                $first = $items->first();

                return [

                    'work_item_id' => $first->workItem->id,

                    'work_item_name' => $first->workItem->name,

                    'project' => [

                        'id' => $first->workItem->project->id,

                        'name' => $first->workItem->project->name,
                    ],

                    'requested_at' => $first->created_at,

                    'photos' => $first->workItem
                        ->progressPhotos
                        ->map(function ($photo) {

                            return [

                                'id' => $photo->id,

                                'url' => asset(
                                    'storage/' . $photo->file_path
                                ),

                                'original_name' => $photo->original_name,

                                'created_at' => $photo->created_at,
                            ];
                        })
                        ->values(),

                    'updates' => $items->map(function ($detail) {

                        return [

                            'detail_id' => $detail->id,

                            'field' => $detail->key,

                            'current_value' => $detail->value,

                            'requested_value' => $detail->pending_value,

                            'approval_status' => $detail->approval_status,
                        ];
                    })->values(),
                ];
            })->values(),

            'status' => 200,
        ];
    }
    public function approveWorkItem(
        WorkItem $workItem
    ): array {

        $user = auth()->user();

        $project = $workItem->project;

        if (
            $user->hasRole('project_manager')
            && $project->project_manager_id != $user->id
        ) {

            throw new RuntimeException(
                'You are not assigned to this project.',
                403
            );
        }

        $pendingDetails = $workItem->details()
            ->where('approval_status', 'pending')
            ->get();

        if ($pendingDetails->isEmpty()) {

            throw new RuntimeException(
                'No pending updates found for this work item.',
                404
            );
        }

        $notification = Notification::query()
            ->where('project_work_item_id', $workItem->id)
            ->where('type', 'work_item_progress_updated')
            ->latest()
            ->first();

        DB::transaction(function () use (
            $pendingDetails,
            $user
        ) {

            foreach ($pendingDetails as $detail) {

                $detail->update([

                    'value' => $detail->pending_value,

                    'pending_value' => null,

                    'approval_status' => 'approved',

                    'approved_by' => $user->id,

                    'approved_at' => now(),
                ]);
            }
        });

        /*
    |--------------------------------------------------------------------------
    | Refresh Details After Approval
    |--------------------------------------------------------------------------
    */

        $approvedDetails = $workItem->details()
            ->where('approval_status', 'approved')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | Notification to Assistant
    |--------------------------------------------------------------------------
    */

        if ($notification) {

            $assistant = User::find(
                $notification->data['assistant_id'] ?? null
            );

            if ($assistant) {

                app(NotificationService::class)->send(
                    $assistant,
                    [

                        'project_id' => $project->id,

                        'project_work_item_id' => $workItem->id,

                        'type' => 'work_item_progress_approved',

                        'title' => 'تم قبول تحديث الإنجاز',

                        'body' => "تم قبول تحديث نسبة الإنجاز للبند {$workItem->name}",

                        'data' => [

                            'project_id' => $project->id,

                            'work_item_id' => $workItem->id,

                        ],
                    ]
                );
            }
        }

        return [

            'message' => 'Work item update approved successfully.',

            'data' => [

                'work_item' => [

                    'id' => $workItem->id,

                    'name' => $workItem->name,

                ],

                'approved_details' => $approvedDetails->map(function ($detail) {

                    return [

                        'id' => $detail->id,

                        'field' => $detail->key,

                        'value' => $detail->value,

                        'approved_by' => $detail->approved_by,

                        'approved_at' => $detail->approved_at,
                    ];
                }),

            ],

            'status' => 200,
        ];
    }
    public function rejectWorkItem(
        WorkItem $workItem,
        string $reason
    ): array {

        $user = auth()->user();

        $project = $workItem->project;

        if (
            $user->hasRole('project_manager')
            && $project->project_manager_id != $user->id
        ) {

            throw new RuntimeException(
                'You are not assigned to this project.',
                403
            );
        }

        $pendingDetails = $workItem->details()
            ->where('approval_status', 'pending')
            ->get();

        if ($pendingDetails->isEmpty()) {

            throw new RuntimeException(
                'No pending updates found for this work item.',
                404
            );
        }

        $notification = Notification::query()
            ->where('project_work_item_id', $workItem->id)
            ->where('type', 'work_item_progress_updated')
            ->latest()
            ->first();

        DB::transaction(function () use (
            $pendingDetails,
            $user
        ) {

            foreach ($pendingDetails as $detail) {

                $detail->update([

                    // لا نلمس القيمة المعتمدة الحالية
                    'pending_value' => null,

                    'approval_status' => 'rejected',

                    'approved_by' => $user->id,

                    'approved_at' => now(),
                ]);
            }
        });

        $pendingDetails->fresh();

        /*
    |--------------------------------------------------------------------------
    | Notify Assistant
    |--------------------------------------------------------------------------
    */

        if ($notification) {

            $assistant = User::find(
                $notification->data['assistant_id'] ?? null
            );

            if ($assistant) {

                app(NotificationService::class)->send(
                    $assistant,
                    [

                        'project_id' => $project->id,

                        'project_work_item_id' => $workItem->id,

                        'type' => 'work_item_progress_rejected',

                        'title' => 'تم رفض تحديث الإنجاز',

                        'body' =>
                        "تم رفض تحديث نسبة الإنجاز للبند {$workItem->name}",

                        'data' => [

                            'project_id' => $project->id,

                            'work_item_id' => $workItem->id,

                            'reason' => $reason,

                        ],

                    ]
                );
            }
        }

        return [

            'message' => 'Work item update rejected successfully.',

            'data' => [

                'work_item' => [

                    'id' => $workItem->id,

                    'name' => $workItem->name,

                ],

                'rejected_details' => $pendingDetails->map(function ($detail) {

                    return [

                        'id' => $detail->id,

                        'field' => $detail->key,

                        'current_value' => $detail->value,

                        'status' => 'rejected',

                    ];
                }),

                'reason' => $reason,

            ],

            'status' => 200,
        ];
    }

    public function getProjectWorkItems(Project $project)
    {
        return $project->workItems()
            ->select('id', 'name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getSystemWorkItems()
    {
        return WorkItem::query()
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->get();
    }
    }
