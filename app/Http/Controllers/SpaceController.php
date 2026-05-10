<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpaceRequest;
use App\Http\Requests\UpdateSpaceRequest;
use App\Http\Resources\SpaceResource;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class SpaceController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        try {
            $spaces = $project->spaces()->get();
            return Response::success(
                'Spaces fetched.',
                SpaceResource::collection($spaces)
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function store(Project $project, StoreSpaceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            if ($request->has('is_shed_floor_tiled')) {
                $data['is_shed_floor_tiled'] = $request->boolean('is_shed_floor_tiled');
            }

            $space = $project->spaces()->create($data);

            return Response::success(
                'Space created.',
                new SpaceResource($space),
                201
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function update(Space $space, UpdateSpaceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->has('is_shed_floor_tiled')) {
                $data['is_shed_floor_tiled'] = $request->boolean('is_shed_floor_tiled');
            }

            $space->update($data);

            return Response::success(
                'Space updated.',
                new SpaceResource($space->fresh())
            );
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function destroy(Space $space): JsonResponse
    {
        try {
            $space->delete();

            return Response::success('Space deleted.');
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
