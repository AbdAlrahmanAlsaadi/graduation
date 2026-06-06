<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Project;
use App\Http\Responses\Response;
use App\Services\OwnerProjectService;
use App\Http\Requests\FilterProjectsRequest;

class OwnerProjectController extends Controller
{
    public function __construct(
        protected OwnerProjectService $service
    ) {}

    public function myProjects(FilterProjectsRequest $request)
    {
        try {

            $data = $this->service->myProjects(
                $request->validated()['status'] ?? 'all'
            );

            return Response::success(
                $data['message'],
                [
                    'projects' => $data['projects']
                ],
                $data['status']
            );
        } catch (Throwable $throwable) {

            return Response::error(
                $throwable->getMessage(),
                $throwable->getCode() ?: 500
            );
        }
    }

    public function show(Project $project)
    {
        try {

            $data = $this->service->show($project);

            return Response::success(
                $data['message'],
                $data['data'],
                $data['status']
            );
        } catch (Throwable $throwable) {

            return Response::error(
                $throwable->getMessage(),
                $throwable->getCode() ?: 500
            );
        }
    }

    public function spaces(Project $project)
    {
        try {

            $data = $this->service->spaces($project);

            return Response::success(
                $data['message'],
                [
                    'spaces' => $data['spaces']
                ],
                $data['status']
            );
        } catch (Throwable $throwable) {

            return Response::error(
                $throwable->getMessage(),
                $throwable->getCode() ?: 500
            );
        }
    }

    public function workItems(Project $project)
    {
        try {

            $data = $this->service->workItems($project);

            return Response::success(
                $data['message'],
                [
                    'work_items' => $data['work_items']
                ],
                $data['status']
            );
        } catch (Throwable $throwable) {

            return Response::error(
                $throwable->getMessage(),
                $throwable->getCode() ?: 500
            );
        }
    }
}
