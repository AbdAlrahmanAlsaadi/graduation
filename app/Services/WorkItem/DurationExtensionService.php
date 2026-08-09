<?php

namespace App\Services\WorkItem;

use App\Services\Notification\NotificationService;

use App\Models\DurationExtensionRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DurationExtensionService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected WorkItemService $workItemService,
    ) {}

    public function index(Request $request, Project $project, WorkItem $workItem = null) {
        if($workItem)
            $requests = $workItem->durationExtensionRequests()
                ->with(['requester', 'reviewer'])
                ->latest()
                ->paginate($request->input('per_page', 15));
        else $requests = $project->durationExtensionRequests()
                ->with(['requester', 'reviewer'])
                ->latest()
                ->paginate($request->input('per_page', 15));

        return $requests;
    }

    public function getAllRequests(Request $request, User $user)
    {
        $query = DurationExtensionRequest::query();

        if (!$user->hasRole('company_admin')) {
            $assignedProjectIds = Project::where('project_manager_id', $user->id)
                ->orWhere('assistant_engineer_id', $user->id)
                ->orWhereHas('projectEngineers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->pluck('id');

            $query->where(function ($q) use ($assignedProjectIds, $user) {
                $q->whereIn('project_id', $assignedProjectIds)
                  ->orWhere('requested_by', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('work_item_id')) {
            $query->where('work_item_id', $request->input('work_item_id'));
        }

        return $query->with(['requester', 'reviewer', 'project', 'workItem'])
            ->latest()
            ->paginate($request->input('per_page', 15));
    }

    /* =========================================================================
       SUBMIT
       ========================================================================= */

    public function submitRequest(
        Project  $project,
        WorkItem $item,
        User     $requester,
        int      $requestedDays,
        string   $reason
    ): DurationExtensionRequest {

        // Prevent duplicate pending requests for the same work item
        $existing = DurationExtensionRequest::where('work_item_id', $item->id)
            ->where('status', DurationExtensionRequest::STATUS_PENDING)
            ->exists();

        if ($existing) {
            abort(400, 'A pending duration extension request already exists for this work item.');
        }

        $request = DurationExtensionRequest::create([
            'project_id'             => $project->id,
            'work_item_id'           => $item->id,
            'requested_by'           => $requester->id,
            'status'                 => DurationExtensionRequest::STATUS_PENDING,
            'requested_duration_days' => $requestedDays,
            'reason'                 => $reason,
        ]);

        // Notify the project engineer (project manager)
        $this->notifyEngineer($project, $item, $request);

        return $request->load('requester');
    }

    /* =========================================================================
       APPROVE
       ========================================================================= */

    public function approve(
        DurationExtensionRequest $request,
        User                     $reviewer
    ): DurationExtensionRequest {

        if (!$request->isPending()) {
            abort(422, 'Only pending requests can be approved.');
        }

        DB::transaction(function () use ($request, $reviewer) {
            $request->update([
                'status'      => DurationExtensionRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            // Update the work item duration via existing WorkItemService
            $this->workItemService->updateWorkItem(
                $request->project,
                $request->workItem,
                ['duration_days' => ($request->workItem->duration_days + $request->requested_duration_days)]
            );
        });

        // Notify the assistant
        $this->notifyRequester($request->fresh(), 'approved');

        return $request->fresh()->load(['requester', 'reviewer']);
    }

    /* =========================================================================
       REJECT
       ========================================================================= */

    public function reject(
        DurationExtensionRequest $request,
        User                     $reviewer,
        ?string                  $comment = null
    ): DurationExtensionRequest {

        if (!$request->isPending()) {
            abort(422, 'Only pending requests can be rejected.');
        }

        $request->update([
            'status'      => DurationExtensionRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'comment'     => $comment,
        ]);

        // Notify the assistant
        $this->notifyRequester($request->fresh(), 'rejected');

        return $request->fresh()->load(['requester', 'reviewer']);
    }

    /* =========================================================================
       NOTIFICATION HELPERS
       ========================================================================= */

    private function notifyEngineer(Project $project, WorkItem $item, DurationExtensionRequest $request): void
    {
        $engineer = $project->projectManager;

        if (!$engineer) {
            return;
        }

        $this->notificationService->send($engineer, [
            'type'                 => 'duration_extension_submitted',
            'title'                => 'New Duration Extension Request',
            'body'                 => "A duration extension was requested for \"{$item->name}\"",
            'project_id'           => $project->id,
            'project_work_item_id' => $item->id,
            'data'                 => [
                'request_id'   => $request->id,
                'work_item_id' => $item->id,
            ],
        ]);
    }

    private function notifyRequester(DurationExtensionRequest $request, string $action): void
    {
        $requester = $request->requester;

        if (!$requester) {
            return;
        }

        $workItem = $request->workItem;

        if ($action === 'approved') {
            $this->notificationService->send($requester, [
                'type'                 => 'duration_extension_approved',
                'title'                => 'Duration Extension Approved',
                'body'                 => "Your duration extension request for \"{$workItem->name}\" has been approved",
                'project_id'           => $request->project_id,
                'project_work_item_id' => $request->work_item_id,
                'sender_id'            => $request->reviewed_by,
                'data'                 => [
                    'request_id'   => $request->id,
                    'work_item_id' => $request->work_item_id,
                ],
            ]);
        } else {
            $this->notificationService->send($requester, [
                'type'                 => 'duration_extension_rejected',
                'title'                => 'Duration Extension Rejected',
                'body'                 => "Your duration extension request for \"{$workItem->name}\" was rejected",
                'project_id'           => $request->project_id,
                'project_work_item_id' => $request->work_item_id,
                'sender_id'            => $request->reviewed_by,
                'data'                 => [
                    'request_id'   => $request->id,
                    'work_item_id' => $request->work_item_id,
                ],
            ]);
        }
    }
}
