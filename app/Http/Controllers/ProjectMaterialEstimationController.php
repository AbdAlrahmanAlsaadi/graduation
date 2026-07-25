<?php

namespace App\Http\Controllers;

use App\Services\ProjectMaterialEstimationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProjectMaterialEstimationController extends Controller
{
    public function __construct(
        private ProjectMaterialEstimationService $estimationService
    ) {}

    public function estimate(int $projectId): JsonResponse
    {
        try {
            $result = $this->estimationService->estimateProjectMaterials($projectId);

            return response()->json($result, $result['status'] ?? 200);
        } catch (Throwable $throwable) {
            $status = (int) $throwable->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 500;
            }

            return response()->json([
                'status' => $status,
                'message' => $throwable->getMessage(),
            ], $status);
        }
    }
}
