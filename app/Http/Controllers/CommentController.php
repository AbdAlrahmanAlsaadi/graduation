<?php

namespace App\Http\Controllers;

use Throwable;
use App\Http\Responses\Response;
use App\Services\Comment\CommentService;
use App\Http\Requests\Misc\StoreCommentRequest;

class CommentController extends Controller
{
    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function store(StoreCommentRequest $request, $workItemId)
    {
        try {

            $data = $this->commentService->store($request, $workItemId);

            return Response::success(
                $data['message'],
                [
                    'comment' => $data['comment'],
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
    public function index($workItemId)
    {
        try {

            $data = $this->commentService->index($workItemId);

            return Response::success(
                $data['message'],
                [
                    'comments' => $data['comments'],
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
}
