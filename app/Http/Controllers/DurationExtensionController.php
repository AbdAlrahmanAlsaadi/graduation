<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkItem\RejectDurationExtensionFormRequest;
use App\Http\Requests\WorkItem\StoreDurationExtensionRequest;
use App\Http\Resources\DurationExtensionResource;
use App\Models\DurationExtensionRequest;
use App\Models\Project;
use App\Models\WorkItem;
use App\Http\Responses\Response;
use App\Services\DurationExtensionService;
use Illuminate\Http\Request;
use Throwable;

class DurationExtensionController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        private DurationExtensionService $service
    ) {}

    /* ============================================================
       SUBMIT — Duration Extension Request
       ============================================================ */

    public function store(StoreDurationExtensionRequest $request, Project $project, WorkItem $workItem)
    {
        try {
            if ($workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $this->authorize('create', [DurationExtensionRequest::class, $project]);

            $extensionRequest = $this->service->submitRequest(
                $project,
                $workItem,
                $request->user(),
                $request->validated()['requested_duration_days'],
                $request->validated()['reason']
            );

            return Response::success(
                'Duration extension request submitted successfully',
                new DurationExtensionResource($extensionRequest),
                201
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to submit duration extension request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       INDEX — List Requests for a Work Item
       ============================================================ */

    public function index(Request $request, Project $project, WorkItem $workItem = null)
    {
        try {
            if ($workItem && $workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $this->authorize('viewAny', [DurationExtensionRequest::class, $project]);
            $requests = $this->service->index($request, $project, $workItem);
            return Response::success(
                'Duration extension requests fetched',
                DurationExtensionResource::collection($requests)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to fetch duration extension requests. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       APPROVE
       ============================================================ */

    public function approve(DurationExtensionRequest $durationExtensionRequest)
    {
        try {
            $this->authorize('approve', $durationExtensionRequest);

            $result = $this->service->approve(
                $durationExtensionRequest,
                auth()->user()
            );

            return Response::success(
                'Duration extension request approved successfully',
                new DurationExtensionResource($result)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to approve duration extension request. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       REJECT
       ============================================================ */

    public function reject(RejectDurationExtensionFormRequest $request, DurationExtensionRequest $durationExtensionRequest)
    {
        try {
            $this->authorize('reject', $durationExtensionRequest);

            $result = $this->service->reject(
                $durationExtensionRequest,
                auth()->user(),
                $request->validated()['comment'] ?? null
            );

            return Response::success(
                'Duration extension request rejected successfully',
                new DurationExtensionResource($result)
            );
        } catch (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return Response::error('You are not authorized to perform this action.', 403);
            }
            return Response::error('Failed to reject duration extension request. ' . $e->getMessage(), 500);
        }
    }
}
