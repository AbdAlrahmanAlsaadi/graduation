<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\ProjectSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService,
        private ProjectSummaryService $summaryService
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $projects = Project::query()->latest()->get();

            return Response::success(
                'Projects fetched.',
                ProjectResource::collection($projects)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        try {
            $authResponse = $this->ensureAdminAssignments();
            if ($authResponse) {
                return $authResponse;
            }

            $project = $this->projectService->createProjectWithDefaults($request->validated());

            return Response::success(
                'Project created.',
                new ProjectResource($project),
                201
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = Auth::user();

            if (! $user) {
                return Response::error('Unauthorized.', 401);
            }

            if (! $user->hasRole('company_admin')) {
                $ownerId = array_key_exists('owner_id', $validated)
                    ? $validated['owner_id']
                    : $project->owner_id;

                if (
                    (int) $validated['project_manager_id'] !== (int) $project->project_manager_id
                    || (int) $validated['assistant_engineer_id'] !== (int) $project->assistant_engineer_id
                    || (int) ($ownerId ?? 0) !== (int) ($project->owner_id ?? 0)
                ) {
                    return Response::error('Only admin can modify project assignments.', 403);
                }
            }

            $project->update($validated);

            return Response::success(
                'Project updated.',
                new ProjectResource($project->fresh())
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function show(Project $project): JsonResponse
    {
        try {
            $project->load([
                'spaces',
                'workItems' => fn ($query) => $query->orderBy('order'),
            ]);

            return Response::success(
                'Project fetched.',
                new ProjectResource($project)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function summary(Project $project): JsonResponse
    {
        try {
            $summary = $this->summaryService->buildSummary($project);

            return Response::success(
                'Project summary fetched.',
                new ProjectSummaryResource($summary)
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

    private function ensureAdminAssignments(): ?JsonResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('company_admin')) {
            return Response::error('Only admin can assign project roles.', 403);
        }

        return null;
    }
}
