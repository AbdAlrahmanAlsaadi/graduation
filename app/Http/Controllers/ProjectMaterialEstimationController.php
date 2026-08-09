<?php

namespace App\Http\Controllers;

use App\Http\Responses\Response;
use App\Services\Project\ProjectMaterialEstimationService;
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

            return Response::success(
                $result['message'],
                $result['data'],
                $result['status'] ?? 200
            );
        } catch (Throwable $throwable) {
            $status = (int) $throwable->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 500;
            }

            return Response::error($throwable->getMessage(), $status);
        }
    }
}
