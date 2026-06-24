<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectProgressUpdateRequest;
use App\Http\Requests\StoreProgressUpdateRequest;
use App\Http\Requests\StoreRoomProgressUpdateRequest;
use App\Http\Resources\ProgressUpdateRequestResource;
use App\Models\ProgressUpdateRequest;
use App\Models\Project;
use App\Models\WorkItem;
use App\Http\Responses\Response;
use App\Services\ProgressApprovalService;
use Illuminate\Http\Request;
use Throwable;

class ProgressUpdateRequestController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        private ProgressApprovalService $service
    ) {}

    /* ============================================================
       SUBMIT — Full Progress Update Request
       ============================================================ */

    public function store(StoreProgressUpdateRequest $request, Project $project, WorkItem $workItem)
    {
        try {
            if ($workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $this->authorize('create', [ProgressUpdateRequest::class, $project]);

            $progressRequest = $this->service->submitUpdate(
                $project,
                $workItem,
                $request->validated(),
                $request->user()
            );

            return Response::success(
                'Progress update request submitted successfully',
                new ProgressUpdateRequestResource($progressRequest),
                201
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to submit progress request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       SUBMIT — Room Status Update Request
       ============================================================ */

    public function storeRoom(StoreRoomProgressUpdateRequest $request, Project $project, WorkItem $workItem, int $spaceId)
    {
        try {
            if ($workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $this->authorize('create', [ProgressUpdateRequest::class, $project]);

            $photos = $this->extractPhotos($request);

            $progressRequest = $this->service->submitRoomUpdate(
                $project,
                $workItem,
                $spaceId,
                $request->validated()['completed'],
                $photos,
                $request->user()
            );

            return Response::success(
                'Room progress update request submitted successfully',
                new ProgressUpdateRequestResource($progressRequest),
                201
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to submit room progress request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       INDEX — List Requests for a Work Item
       ============================================================ */

    public function index(Request $request, Project $project, WorkItem $workItem)
    {
        try {
            if ($workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $this->authorize('viewAny', [ProgressUpdateRequest::class, $project]);

            $requests = $workItem->progressUpdateRequests()
                ->with(['requester', 'reviewer'])
                ->latest()
                ->paginate($request->input('per_page', 15));

            return Response::success(
                'Progress update requests fetched',
                ProgressUpdateRequestResource::collection($requests)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to fetch progress requests. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       SHOW — View a Single Request
       ============================================================ */

    public function show(ProgressUpdateRequest $progressUpdateRequest)
    {
        try {
            $this->authorize('view', $progressUpdateRequest);

            $progressUpdateRequest->load(['requester', 'reviewer']);

            return Response::success(
                'Progress update request fetched',
                new ProgressUpdateRequestResource($progressUpdateRequest)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to fetch progress request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       APPROVE
       ============================================================ */

    public function approve(ProgressUpdateRequest $progressUpdateRequest)
    {
        try {
            $this->authorize('approve', $progressUpdateRequest);

            $result = $this->service->approveUpdate(
                $progressUpdateRequest,
                auth()->user()
            );

            return Response::success(
                'Progress update request approved successfully',
                new ProgressUpdateRequestResource($result)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to approve progress request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       REJECT
       ============================================================ */

    public function reject(RejectProgressUpdateRequest $request, ProgressUpdateRequest $progressUpdateRequest)
    {
        try {
            $this->authorize('reject', $progressUpdateRequest);

            $result = $this->service->rejectUpdate(
                $progressUpdateRequest,
                auth()->user(),
                $request->validated()['comment'] ?? null
            );

            return Response::success(
                'Progress update request rejected successfully',
                new ProgressUpdateRequestResource($result)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to reject progress request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function extractPhotos(Request $request): array
    {
        $photos = $request->file('photos');

        if ($photos === null) {
            $files = $request->allFiles();
            if (array_key_exists('photos', $files)) {
                $photos = $files['photos'];
            }
        }

        if ($photos instanceof \Illuminate\Http\UploadedFile) {
            return [$photos];
        }

        return is_array($photos) ? $photos : [];
    }
}
