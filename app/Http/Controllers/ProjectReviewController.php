<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectReviewStatisticsRequest;
use App\Http\Requests\StoreProjectReviewRequest;
use App\Http\Responses\Response;
use App\Models\Project;
use App\Services\ProjectReviewService;
use Throwable;

class ProjectReviewController extends Controller
{
    public function __construct(
        protected ProjectReviewService $projectReviewService
    ) {}

    public function store(
        StoreProjectReviewRequest $request,
        Project $project
    ) {
        try {

            $data = $this->projectReviewService->store(
                $request,
                $project
            );

            return Response::success(
                $data['message'],
                [
                    'review' => $data['review'],
                ],
                $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode()) &&
                $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error(
                $throwable->getMessage(),
                $code
            );
        }
    }
    public function statistics(
        ProjectReviewStatisticsRequest $request
    ) {
        try {

            $data = $this->projectReviewService
                ->statistics($request);

            return Response::success(
                $data['message'],
                $data['data'],
                $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode()) &&
                $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error(
                $throwable->getMessage(),
                $code
            );
        }
    }
}
