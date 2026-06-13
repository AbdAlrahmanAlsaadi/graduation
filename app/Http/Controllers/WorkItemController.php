<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderWorkItemsRequest;
use App\Http\Requests\WorkItemDetailsRequest;
use App\Http\Requests\StoreWorkItemRequest;
use App\Http\Requests\UpdateWorkItemRequest;
use App\Http\Resources\WorkItemResource;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkItemDetail;
use App\Services\EquipmentService;
use App\Services\WorkItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class WorkItemController extends Controller
{
    public function __construct(
        private WorkItemService $workItemService,
        private EquipmentService $equipmentService
    ) {}

    public function index(Project $project): JsonResponse
    {
        try {
            $items = $project->workItems()->with('details')->orderBy('sort_order')->get();

            return Response::success(
                'Work items fetched.',
                WorkItemResource::collection($items)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function store(Project $project, StoreWorkItemRequest $request): JsonResponse
    {
        try {
            $workItem = $this->workItemService->createCustomWorkItem($project, $request->validated());

            return Response::success(
                'Work item created.',
                new WorkItemResource($workItem),
                201
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function update(Project $project, WorkItem $workItem, UpdateWorkItemRequest $request): JsonResponse
    {
        try {
            $workItem = $this->workItemService->updateWorkItem($project, $workItem, $request->validated());

            return Response::success(
                'Work item updated.',
                new WorkItemResource($workItem)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function updateDetails(Project $project, WorkItem $workItem, WorkItemDetailsRequest $request): JsonResponse
    {
        try {
            if ((int) $workItem->project_id !== (int) $project->id) {
                return Response::error('Work item not found.', 404);
            }

            $this->workItemService->updateDetails($project, $workItem, $request->validated());

            return Response::success(
                'Work item details updated.',
                new WorkItemResource($workItem->load('details'))
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function destroy(WorkItem $workItem): JsonResponse
    {
        try {
            $workItem->delete();

            return Response::success('Work item deleted.');
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function reorder(Project $project, ReorderWorkItemsRequest $request): JsonResponse
    {
        try {
            $items = $this->workItemService->reorder($project, $request->validated()['items']);

            return Response::success(
                'Work items reordered.',
                WorkItemResource::collection($items)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function start(Project $project, WorkItem $workItem): JsonResponse
    {
        try {
            $authResponse = $this->ensureWorkItemAccess();
            if ($authResponse) {
                return $authResponse;
            }

            $workItem = $this->workItemService->startWorkItem($project, $workItem);

            return Response::success(
                'Work item started.',
                new WorkItemResource($workItem->loadMissing('details'))
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function complete(
        Project $project,
        WorkItem $workItem
    ): JsonResponse {

        try {

            $authResponse = $this->ensureWorkItemAccess();

            if ($authResponse) {
                return $authResponse;
            }

            $workItem = $this->workItemService
                ->completeWorkItem(
                    $project,
                    $workItem
                );

            $this->equipmentService
                ->completeBookingsForWorkItem(
                    $workItem
                );

            return Response::success(
                'Work item completed.',
                new WorkItemResource(
                    $workItem->loadMissing('details')
                )
            );
        } catch (Throwable $throwable) {

            return $this->handleException(
                $throwable
            );
        }
    }
    private function handleException(Throwable $throwable): JsonResponse
    {
        if ($throwable instanceof ValidationException) {
            $status = $throwable->status ?? 422;

            return Response::Validation('Validation failed.', $throwable->errors(), $status);
        }

        $status = (int) $throwable->getCode();
        if ($status < 400 || $status >= 600) {
            $status = 500;
        }

        return Response::error($throwable->getMessage(), $status);
    }

    private function ensureWorkItemAccess(): ?JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return Response::error('Unauthorized.', 401);
        }

        if (! $user->hasAnyRole(['company_admin', 'project_manager'])) {
            return Response::error('Forbidden.', 403);
        }

        return null;
    }

    public function approveDetail(
        WorkItemDetail $detail
    ): JsonResponse {

        try {

            $user = auth()->user();

            if (
                ! $user->hasRole('company_admin') &&
                ! $user->hasRole('project_manager')
            ) {
                return Response::error(
                    'Unauthorized.',
                    403
                );
            }

            $detail =
                $this->workItemService
                ->approveDetail($detail);

            return Response::success(
                'Work item detail approved successfully.',
                $detail
            );
        } catch (Throwable $throwable) {

            return $this->handleException(
                $throwable
            );
        }
    }


    public function pendingUpdates()
    {
        try {

            $data = $this->workItemService
                ->pendingDetails();

            return Response::success(
                $data['message'],
                $data['data'],
                $data['status']
            );
        } catch (Throwable $throwable) {

            return $this->handleException(
                $throwable
            );
        }
    }
    public function rejectWorkItem(
        WorkItem $workItem,
        Request $request
    ): JsonResponse {

        $data = $this->workItemService
            ->rejectWorkItem(
                $workItem,
                $request->reason
            );


        return Response::success(
            $data['message'],
            $data['data'],
            $data['status']
        );
    }
    public function approveWorkItem(
        WorkItem $workItem
    ): JsonResponse {

        $data = $this->workItemService
            ->approveWorkItem($workItem);

        return Response::success(
            $data['message'],
            $data['data'],
            $data['status']
        );

    }
}
