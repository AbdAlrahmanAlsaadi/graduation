<?php

namespace App\Http\Controllers;

use App\Http\Responses\Response;
use App\Services\Project\ProjectCostEstimationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProjectCostEstimationController extends Controller
{
    public function __construct(
        private ProjectCostEstimationService $estimationService
    ) {}

    /**
     * API 4: Estimate total project cost (Materials + Workshops).
     */
    public function estimateTotal(Request $request, int $projectId): JsonResponse
    {
        try {
            $beamsCount = (int) $request->query('beams_count', 0);
            $skirtingFactor = (float) $request->query('skirting_factor', 0.1);

            $result = $this->estimationService->estimateTotalProjectCost(
                $projectId,
                $beamsCount,
                $skirtingFactor
            );

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

    /**
     * API 5: Compare actual current project costs against total estimated cost.
     */
    public function compareCost(Request $request, int $projectId): JsonResponse
    {
        try {
            $beamsCount = (int) $request->query('beams_count', 0);
            $skirtingFactor = (float) $request->query('skirting_factor', 0.1);

            $result = $this->estimationService->compareProjectCosts(
                $projectId,
                $beamsCount,
                $skirtingFactor
            );

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
