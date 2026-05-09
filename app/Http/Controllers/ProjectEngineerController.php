<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEngineerRequest;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Services\ProjectEngineerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProjectEngineerController extends Controller
{
    public function __construct(private ProjectEngineerService $projectEngineerService)
    {
    }

    public function index(Project $project): JsonResponse
    {
        try {
            $assignments = $this->projectEngineerService->list($project);

            return Response::success('Engineers fetched.', $assignments);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function store(AssignEngineerRequest $request, Project $project): JsonResponse
    {
        try {
            $data = $request->validated();

            $assignment = $this->projectEngineerService->assign(
                $project,
                (int) $data['user_id'],
                $data['role'],
                $data['assigned_at'] ?? now()
            );

            return Response::success('Engineer assigned.', $assignment, 201);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function destroy(Project $project, int $assignment): JsonResponse
    {
        try {
            $removed = $this->projectEngineerService->remove($project, $assignment);

            if (! $removed) {
                return Response::error('Engineer assignment not found.', 404);
            }

            return Response::success('Engineer assignment removed.');
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
