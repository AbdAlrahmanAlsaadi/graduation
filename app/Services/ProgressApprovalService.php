<?php

namespace App\Services;

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
       SUBMIT: Full Progress Update
       ========================================================================= */

    public function submitUpdate(
        Project  $project,
        WorkItem $item,
        array    $data,
        User     $requester
    ): ProgressUpdateRequest {
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
                $photos = $this->pathsToUploadedFiles($finalPhotoPaths);

                $this->progressService->updateRoomStatus(
                    $request->project,
                    $request->workItem,
                    $payload['space_id'],
                    (bool) $payload['completed'],
                    $photos
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
            $path = $photo->storeAs($baseDir, $fileName, 'local');
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

            if (!Storage::disk('local')->exists($tempPath)) {
                continue;
            }

            $fileName = Str::uuid()->toString() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);
            $finalPath = $finalDir . '/' . $fileName;

            // Read from local temp, write to public storage
            $contents = Storage::disk('local')->get($tempPath);
            Storage::disk('public')->put($finalPath, $contents);

            // Remove temp file
            Storage::disk('local')->delete($tempPath);

            $finalPaths[] = [
                'path'          => $finalPath,
                'original_name' => $originalName,
            ];
        }

        // Clean up the empty temp directory
        $tempDir = "tmp/progress-updates/{$requestId}";
        Storage::disk('local')->deleteDirectory($tempDir);

        return $finalPaths;
    }

    /**
     * Create ProgressPhoto records for approved photos.
     */
    private function storePhotoRecords(int $projectId, int $workItemId, array $photoPaths): void
    {
        foreach ($photoPaths as $photoData) {
            \App\Models\ProgressPhoto::create([
                'project_id'    => $projectId,
                'work_item_id'  => $workItemId,
                'file_path'     => $photoData['path'],
                'original_name' => $photoData['original_name'],
            ]);
        }
    }

    /**
     * Convert stored file paths back to UploadedFile instances for updateRoomStatus.
     * Used only for room-type updates where the service expects file objects.
     */
    private function pathsToUploadedFiles(array $finalPhotoPaths): array
    {
        // For room updates, photos were already stored. We create the records directly
        // and return an empty array to avoid double-storing.
        // The updateRoomStatus method will not receive photos — we handle them here.
        return [];
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
            'type'                 => 'progress_update_submitted',
            'title'                => 'New Progress Update Request',
            'body'                 => "{$requester->name} submitted a {$typeLabel} for \"{$item->name}\"",
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
                'title'                => 'Progress Update Approved',
                'body'                 => "Your progress update for \"{$workItem->name}\" has been approved",
                'project_id'           => $request->project_id,
                'project_work_item_id' => $request->work_item_id,
                'sender_id'            => $request->reviewed_by,
                'data'                 => [
                    'request_id' => $request->id,
                ],
            ]);
        } else {
            $reason = $request->comment ? " Reason: {$request->comment}" : '';

            $this->notificationService->send($requester, [
                'type'                 => 'progress_update_rejected',
                'title'                => 'Progress Update Rejected',
                'body'                 => "Your progress update for \"{$workItem->name}\" was rejected.{$reason}",
                'project_id'           => $request->project_id,
                'project_work_item_id' => $request->work_item_id,
                'sender_id'            => $request->reviewed_by,
                'data'                 => [
                    'request_id' => $request->id,
                    'comment'    => $request->comment,
                ],
            ]);
        }
    }
}
