<?php

namespace App\Http\Controllers;

use App\Http\Responses\Response;
use App\Services\Project\ProjectWorkshopEstimationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProjectWorkshopEstimationController extends Controller
{
    public function __construct(
        private ProjectWorkshopEstimationService $estimationService
    ) {}

    public function estimate(Request $request, int $projectId): JsonResponse
    {
        try {
            $beamsCount = (int) $request->query('beams_count', 0);
            $skirtingFactor = (float) $request->query('skirting_factor', 0.1);

            $result = $this->estimationService->estimateProjectWorkshops(
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
