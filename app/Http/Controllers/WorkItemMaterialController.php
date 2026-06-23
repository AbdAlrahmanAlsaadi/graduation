<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkItemMaterialRequest;
use App\Http\Requests\SyncWorkItemMaterialsRequest;
use App\Http\Requests\UpdateWorkItemMaterialRequest;
use App\Http\Responses\Response;
use App\Models\Material;
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
    public function index(string $workItemName): JsonResponse
    {
        try {
            $workItemMaterials = $this->workItemMaterialService->getMaterialsForWorkItem($workItemName);

            return Response::success('Work item materials fetched.', $workItemMaterials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Attach one material to a work item with pivot data.
     */
    public function store(StoreWorkItemMaterialRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $workItemMaterials = $this->workItemMaterialService->attachMaterial($data);

            return Response::success('Material attached to work item.', $workItemMaterials, 201);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Update pivot data for one attached material.
     */
    public function update(string $workItemName, Material $material, UpdateWorkItemMaterialRequest $request): JsonResponse
    {
        try {
            $workItemMaterial = $this->workItemMaterialService->updatePivotData(
                $workItemName,
                (int) $material->id,
                $request->validated()
            );

            return Response::success('Work item material updated.', $workItemMaterial);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Detach a material from a work item.
     */
    public function destroy(string $workItemName, Material $material): JsonResponse
    {
        try {
            $workItemMaterials = $this->workItemMaterialService->detachMaterial($workItemName, (int) $material->id);

            return Response::success('Material detached from work item.', $workItemMaterials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    /**
     * Replace all work item materials in one operation.
     */
    public function sync(string $workItemName, SyncWorkItemMaterialsRequest $request): JsonResponse
    {
        try {
            $workItemMaterials = $this->workItemMaterialService->syncMaterials(
                $workItemName,
                $request->validated()['materials']
            );

            return Response::success('Work item materials synced.', $workItemMaterials);
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
