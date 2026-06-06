<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkItemMaterialRequest;
use App\Http\Requests\SyncWorkItemMaterialsRequest;
use App\Http\Requests\UpdateWorkItemMaterialRequest;
use App\Http\Responses\Response;
use App\Models\Material;
use App\Models\WorkItem;
use App\Services\WorkItemMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class WorkItemMaterialController extends Controller
{
    public function __construct(private WorkItemMaterialService $workItemMaterialService)
    {
    }

    /**
     * List materials attached to the given work item.
     */
    public function index(WorkItem $workItem): JsonResponse
    {
        try {
            $materials = $this->workItemMaterialService->getMaterialsForWorkItem($workItem->name);

            return Response::success('Work item materials fetched.', $materials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Attach one material to a work item with pivot data.
     */
    public function store(WorkItem $workItem, StoreWorkItemMaterialRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $materials = $this->workItemMaterialService->attachMaterial(
                $workItem->name,
                (int) $data['material_id'],
                (int) $data['sort_order'],
                (bool) $data['is_required']
            );

            return Response::success('Material attached to work item.', $materials, 201);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Update pivot data for one attached material.
     */
    public function update(WorkItem $workItem, Material $material, UpdateWorkItemMaterialRequest $request): JsonResponse
    {
        try {
            $materials = $this->workItemMaterialService->updatePivotData(
                $workItem->name,
                (int) $material->id,
                $request->validated()
            );

            return Response::success('Work item material updated.', $materials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Detach a material from a work item.
     */
    public function destroy(WorkItem $workItem, Material $material): JsonResponse
    {
        try {
            $materials = $this->workItemMaterialService->detachMaterial($workItem->name, (int) $material->id);

            return Response::success('Material detached from work item.', $materials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Replace all work item materials in one operation.
     */
    public function sync(WorkItem $workItem, SyncWorkItemMaterialsRequest $request): JsonResponse
    {
        try {
            $materials = $this->workItemMaterialService->syncMaterials(
                $workItem->name,
                $request->validated()['materials']
            );

            return Response::success('Work item materials synced.', $materials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
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
}
