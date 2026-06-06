<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Responses\Response;
use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class MaterialController extends Controller
{
    public function __construct(private MaterialService $materialService)
    {
    }

    public function index(): JsonResponse
    {
        try {
            $materials = $this->materialService->getAll();

            return Response::success('Materials fetched.', $materials);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function store(StoreMaterialRequest $request): JsonResponse
    {
        try {
            $material = $this->materialService->create($request->validated());

            return Response::success('Material created.', $material, 201);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function show(Material $material): JsonResponse
    {
        try {
            $material = $this->materialService->findById((int) $material->id);

            return Response::success('Material fetched.', $material);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function update(UpdateMaterialRequest $request, Material $material): JsonResponse
    {
        try {
            $updated = $this->materialService->update((int) $material->id, $request->validated());

            return Response::success('Material updated.', $updated);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function destroy(Material $material): JsonResponse
    {
        try {
            $this->materialService->delete((int) $material->id);

            return Response::success('Material deleted.');
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
