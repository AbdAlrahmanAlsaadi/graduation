<?php

namespace App\Services\Comment;

use App\Models\Comment;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    public function store($request, $workItemId): array
    {
        $request->validated();

        $user = Auth::user();

        $workItem = WorkItem::query()
            ->with('project')
            ->find($workItemId);

        if (! $workItem) {
            throw new \Exception('Work item not found.', 404);
        }

        $project = $workItem->project;

        $isCompanyAdmin = $user->hasRole('company_admin');

        $isProjectManager =
            $user->hasRole('project_manager') &&
            $project->project_manager_id == $user->id;

        $isAssistant =
            $user->hasRole('assistant') &&
            $project->assistant_engineer_id == $user->id;

        if (! $isCompanyAdmin && ! $isProjectManager && ! $isAssistant) {
            throw new \Exception(
                'You are not allowed to comment on this work item.',
                403
            );
        }

        $comment = Comment::query()->create([
            'work_item_id' => $workItem->id,
            'user_id' => $user->id,
            'comment' => $request->comment,
        ]);

        $comment->load([
            'user',
            'workItem',
        ]);

        return [
            'message' => 'Comment added successfully.',
            'comment' => $comment,
            'status' => 201,
        ];
    }
    public function index($workItemId): array
    {
        $user = auth::user();

        $workItem = WorkItem::query()
            ->with('project')
            ->find($workItemId);

        if (! $workItem) {
            throw new \Exception('Work item not found.', 404);
        }

        $project = $workItem->project;

        $isCompanyAdmin = $user->hasRole('company_admin');

        $isProjectManager =
            $user->hasRole('project_manager') &&
            $project->project_manager_id == $user->id;

        $isAssistant =
            $user->hasRole('assistant') &&
            $project->assistant_engineer_id == $user->id;

        if (! $isCompanyAdmin && ! $isProjectManager && ! $isAssistant) {
            throw new \Exception(
                'You are not allowed to view comments for this work item.',
                403
            );
        }

        $comments = $workItem->comments()
            ->with(['user', 'workItem'])
            ->latest()
            ->get()
            ->map(function ($comment) {

                return [
                    'id' => $comment->id,

                    'comment' => $comment->comment,

                    'created_at' => $comment->created_at,

                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'internal_id' => $comment->user->internal_id,
                    ],

                    'work_item' => [
                        'id' => $comment->workItem->id,
                        'name' => $comment->workItem->name,
                    ],
                ];
            });

        return [
            'message' => 'Comments fetched successfully.',
            'comments' => $comments,
            'status' => 200,
        ];
    }
}
