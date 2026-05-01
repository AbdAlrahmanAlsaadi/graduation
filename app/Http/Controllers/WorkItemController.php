<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderWorkItemsRequest;
use App\Http\Requests\StoreWorkItemRequest;
use App\Http\Resources\WorkItemResource;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Models\WorkItem;
use App\Services\WorkItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class WorkItemController extends Controller
{
    public function __construct(private WorkItemService $workItemService)
    {
    }

    public function index(Project $project): JsonResponse
    {
        try {
            $items = $project->workItems()->orderBy('order')->get();

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

    private function handleException(Throwable $throwable): JsonResponse
    {
        if ($throwable instanceof ValidationException) {
            return Response::Validation('Validation failed.', $throwable->errors());
        }

        $status = (int) $throwable->getCode();
        if ($status < 400 || $status >= 600) {
            $status = 500;
        }

        return Response::error($throwable->getMessage(), $status);
    }
}
