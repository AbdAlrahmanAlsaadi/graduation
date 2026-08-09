<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\SearchProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Services\Project\ProjectService;
use App\Services\Project\ProjectSummaryService;
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
            $projects = Project::with(['projectManager', 'assistantEngineer', 'owner'])->get();

            return Response::success(
                'Projects fetched.',
                ProjectResource::collection($projects)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function listEngineerProjects(): JsonResponse
    {
        try {
            $projects = Auth::user()->assignedProjects;

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
            $project = $this->projectService->updateProject($project, $request->validated());

            return Response::success(
                'Project updated.',
                new ProjectResource($project)
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
                'workItems' => fn($query) => $query->orderBy('sort_order'),
                'projectManager',    // ✅ أضف هذا
                'assistantEngineer', // ✅ أضف هذا
                'owner'              // ✅ أضف هذا
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

    public function start(Project $project): JsonResponse
    {
        try {
            $authResponse = $this->ensureProjectAccess();
            if ($authResponse) {
                return $authResponse;
            }

            $project = $this->projectService->startProject($project);

            return Response::success(
                'Project started.',
                new ProjectResource($project->loadMissing('workItems'))
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function complete(Project $project): JsonResponse
    {
        try {
            $authResponse = $this->ensureProjectAccess();
            if ($authResponse) {
                return $authResponse;
            }

            $project = $this->projectService->completeProject($project);

            return Response::success(
                'Project completed.',
                new ProjectResource($project->loadMissing('workItems'))
            );
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

    private function ensureAdminAssignments(): ?JsonResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('company_admin')) {
            return Response::error('Only admin can assign project roles.', 403);
        }

        return null;
    }

    private function ensureProjectAccess(): ?JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return Response::error('Unauthorized.', 401);
        }

        if (! $user->hasAnyRole(['company_admin', 'project_manager'])) {
            return Response::error('Forbidden.', 403);
        }

        return null;
    }
    public function search(SearchProjectRequest $request)
    {
        try {

            $data = $this->projectService->search($request);

            return Response::success(
                $data['message'],
                [
                    'projects' => $data['projects'],
                    'pagination' => $data['pagination'],
                ],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }
    public function getOngoingProjects(): JsonResponse
    {
        $result = $this->projectService->getOngoingProjects();

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ]);
    }

    public function getDeliveryRate(): JsonResponse
    {
        $result = $this->projectService->calculateOnTimeDeliveryRate();

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status']);
    }


}
