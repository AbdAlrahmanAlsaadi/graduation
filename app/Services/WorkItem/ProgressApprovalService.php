<?php

namespace App\Services\WorkItem;

use App\Services\Notification\NotificationService;

use App\Models\ProgressUpdateRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProgressApprovalService
{
    public function __construct(
        protected WorkItemProgressService $progressService,
        protected NotificationService $notificationService,
    ) {}

    /* =========================================================================
       INDEX: All Progress Updates with Scoping & Filtering
       ========================================================================= */

    public function getAllRequests(
        \Illuminate\Http\Request $request,
        User $user
    ) {
        $query = ProgressUpdateRequest::query()
            ->pending();

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

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('work_item_id')) {
            $query->where('work_item_id', $request->input('work_item_id'));
        }

        return $query
            ->with([
                'requester',
                'reviewer',
                'project',
                'workItem',
            ])
            ->latest()
            ->paginate($request->input('per_page', 15));
    }

    /* =========================================================================
       SUBMIT: Full Progress Update
       ========================================================================= */

    public function submitUpdate(
        Project  $project,
        WorkItem $item,
        array    $data,
        User     $requester
    ): ProgressUpdateRequest {

        if($item->status === 'completed'){
            abort(400,'Work item already completed');
        }

        // 1) Create the request without photos first (to get the ID)
        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $item->id,
            'requested_by' => $requester->id,
            'status'       => ProgressUpdateRequest::STATUS_PENDING,
            'type'         => ProgressUpdateRequest::TYPE_PROGRESS,
            'payload'      => [],
        ]);

        // 2) Handle photos — store temporarily
        $photoPaths = [];
        if (isset($data['photos'])) {
            $photoPaths = $this->storeTempPhotos($progressRequest->id, $data['photos']);
            unset($data['photos']);
        }

        // 3) Save full payload (data + temp photo paths)
        $payload = $data;
        if (!empty($photoPaths)) {
            $payload['_temp_photos'] = $photoPaths;
        }

        $progressRequest->update(['payload' => $payload]);

        // 4) Notify the project's engineer
        $this->notifyEngineer($project, $item, $requester, 'progress');

        return $progressRequest->load('requester');
    }

    /* =========================================================================
       SUBMIT: Room Status Update
       ========================================================================= */

    public function submitRoomUpdate(
        Project  $project,
        WorkItem $item,
        int      $spaceId,
        bool     $completed,
        array    $photos,
        User     $requester
    ): ProgressUpdateRequest {
        // Validate space and work item logic before saving the request
        $this->progressService->validateSpaceForWorkItem($project, $item, $spaceId);

        if($item->status === 'completed'){
            abort(400,'Work item already completed');
        }

        // Prevent duplicate pending room update for the same space on this work item
        $existingPending = ProgressUpdateRequest::where('work_item_id', $item->id)
            ->where('project_id', $project->id)
            ->where('type', ProgressUpdateRequest::TYPE_ROOM)
            ->where('status', '!=', ProgressUpdateRequest::STATUS_REJECTED)
            ->where('payload->space_id', $spaceId)
            ->exists();

        if ($existingPending) {
            abort(400, 'يوجد طلب تحديث فراغ معلق لهذا البند في نفس الفراغ');
        }

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $item->id,
            'requested_by' => $requester->id,
            'status'       => ProgressUpdateRequest::STATUS_PENDING,
            'type'         => ProgressUpdateRequest::TYPE_ROOM,
            'payload'      => [],
        ]);

        // Handle photos
        $photoPaths = [];
        if (!empty($photos)) {
            $photoPaths = $this->storeTempPhotos($progressRequest->id, $photos);
        }

        $payload = [
            'space_id'  => $spaceId,
            'completed' => $completed,
        ];

        if (!empty($photoPaths)) {
            $payload['_temp_photos'] = $photoPaths;
        }

        $progressRequest->update(['payload' => $payload]);

        // Notify the project's engineer
        $this->notifyEngineer($project, $item, $requester, 'room');

        return $progressRequest->load('requester');
    }

    /* =========================================================================
       APPROVE
       ========================================================================= */

    public function approveUpdate(
        ProgressUpdateRequest $request,
        User                  $reviewer
    ): ProgressUpdateRequest {
        if (!$request->isPending()) {
            abort(422, 'Only pending requests can be approved.');
        }

        DB::transaction(function () use ($request, $reviewer) {
            $payload = $request->payload;

            // Move temp photos to final location
            $finalPhotoPaths = $this->moveTempPhotosToFinal(
                $request->id,
                $request->project_id,
                $request->work_item_id,
                $payload['_temp_photos'] ?? []
            );

            // Remove internal photo metadata from payload
            unset($payload['_temp_photos']);

            // Apply the actual progress update
            if ($request->type === ProgressUpdateRequest::TYPE_ROOM) {
                // Store photo records in photos_progress table
                if (!empty($finalPhotoPaths)) {
                    $this->storePhotoRecords(
                        $request->project_id,
                        $request->work_item_id,
                        $finalPhotoPaths,
                        $payload['space_id']
                    );
                }

                $this->progressService->updateRoomStatus(
                    $request->project,
                    $request->workItem,
                    $payload['space_id'],
                    (bool) $payload['completed'],
                );
            } else {
                // For full progress updates, inject photos as file paths
                if (!empty($finalPhotoPaths)) {
                    $this->storePhotoRecords(
                        $request->project_id,
                        $request->work_item_id,
                        $finalPhotoPaths
                    );
                }

                $this->progressService->updateProgress(
                    $request->project,
                    $request->workItem,
                    $payload
                );
            }

            // Mark as approved
            $request->update([
                'status'      => ProgressUpdateRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });

        // Notify the requester
        $this->notifyRequester($request->fresh(), 'approved');

        return $request->fresh()->load(['requester', 'reviewer']);
    }

    /* =========================================================================
       REJECT
       ========================================================================= */

    public function rejectUpdate(
        ProgressUpdateRequest $request,
        User                  $reviewer,
        ?string               $reason = null
    ): ProgressUpdateRequest {
        if (!$request->isPending()) {
            abort(422, 'Only pending requests can be rejected.');
        }

        $request->update([
            'status'      => ProgressUpdateRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'comment'     => $reason,
        ]);

        // Notify the requester
        $this->notifyRequester($request->fresh(), 'rejected');

        return $request->fresh()->load(['requester', 'reviewer']);
    }

    /* =========================================================================
       PHOTO HELPERS
       ========================================================================= */

    /**
     * Store uploaded photos to a temporary directory.
     *
     * @param  int   $requestId
     * @param  array $photos  Array of UploadedFile instances
     * @return array  Array of stored temp paths
     */
    private function storeTempPhotos(int $requestId, array $photos): array
    {
        $paths = [];
        $baseDir = "tmp/progress-updates/{$requestId}";

        foreach ($photos as $photo) {
            if (!($photo instanceof \Illuminate\Http\UploadedFile)) {
                continue;
            }

            $fileName = Str::uuid()->toString() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs($baseDir, $fileName, 'public');
            $paths[] = [
                'path'          => $path,
                'original_name' => $photo->getClientOriginalName(),
            ];
        }

        return $paths;
    }

    /**
     * Move temp photos to the final public storage location.
     *
     * @return array  Array of final paths
     */
    private function moveTempPhotosToFinal(
        int   $requestId,
        int   $projectId,
        int   $workItemId,
        array $tempPhotos
    ): array {
        if (empty($tempPhotos)) {
            return [];
        }

        $finalPaths = [];
        $finalDir = "progress-photos/{$projectId}/{$workItemId}";

        foreach ($tempPhotos as $tempPhoto) {
            $tempPath = $tempPhoto['path'];
            $originalName = $tempPhoto['original_name'] ?? basename($tempPath);

            if (!Storage::disk('public')->exists($tempPath)) {
                continue;
            }

            $fileName = Str::uuid()->toString() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);
            $finalPath = $finalDir . '/' . $fileName;

            // Move within public storage (temp → final)
            Storage::disk('public')->move($tempPath, $finalPath);


            $finalPaths[] = [
                'path'          => $finalPath,
                'original_name' => $originalName,
            ];
        }

        // Clean up the empty temp directory
        $tempDir = "tmp/progress-updates/{$requestId}";
        Storage::disk('public')->deleteDirectory($tempDir);

        return $finalPaths;
    }

    /**
     * Create ProgressPhoto records for approved photos.
     */
    private function storePhotoRecords(int $projectId, int $workItemId, array $photoPaths, $spaceId = null): void
    {
        foreach ($photoPaths as $photoData) {
            \App\Models\ProgressPhoto::create([
                'project_id'    => $projectId,
                'work_item_id'  => $workItemId,
                'file_path'     => $photoData['path'],
                'original_name' => $photoData['original_name'],
                'space_id'      => $spaceId
            ]);
        }
    }

    /* =========================================================================
       NOTIFICATION HELPERS
       ========================================================================= */

    private function notifyEngineer(Project $project, WorkItem $item, User $requester, string $type): void
    {
        $engineer = $project->projectManager;

        if (!$engineer) {
            return;
        }

        $typeLabel = $type === 'room' ? 'room status update' : 'progress update';

        $this->notificationService->send($engineer, [
            'type'                 => 'طلب تحديث الإنجاز',
            'title'                => 'طلب تحديث الإنجاز',
            'body'                 => "{$requester->name} قدم طلب تحديث للإنجاز لـ \"{$item->name}\"",
            'project_id'           => $project->id,
            'project_work_item_id' => $item->id,
            'sender_id'            => $requester->id,
            'data'                 => [
                'request_type' => $type,
                'work_item_id' => $item->id,
                'project_id'   => $project->id,
            ],
        ]);
    }

    private function notifyRequester(ProgressUpdateRequest $request, string $action): void
    {
        $requester = $request->requester;

        if (!$requester) {
            return;
        }

        $workItem = $request->workItem;

        if ($action === 'approved') {
            $this->notificationService->send($requester, [
                'type'                 => 'progress_update_approved',
                'title'                => 'تمت الموافقة على تحديث الإنجاز',
                'body'                 => "تمت الموافقة على تحديث الإنجاز للبند \"{$workItem->name}\".",
                'project_id'           => $request->project_id,
                'project_work_item_id' => $request->work_item_id,
                'sender_id'             => $request->reviewed_by,
                'data'                 => [
                    'request_id' => $request->id,
                ],
            ]);
        } else {
            $reason = $request->comment
                ? " السبب: {$request->comment}"
                : '';

            $this->notificationService->send($requester, [
                'type'                 => 'progress_update_rejected',
                'title'                => 'تم رفض تحديث الإنجاز',
                'body'                 => "تم رفض تحديث الإنجاز للبند \"{$workItem->name}\".{$reason}",
                'project_id'           => $request->project_id,
                'project_work_item_id' => $request->work_item_id,
                'sender_id'             => $request->reviewed_by,
                'data'                 => [
                    'request_id' => $request->id,
                    'comment'    => $request->comment,
                ],
            ]);
        }}}
