<?php

namespace App\Services\Project;

use App\Services\Notification\NotificationService;

use App\Models\Project;
use App\Models\ProjectReview;
use App\Models\User;

class ProjectReviewService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function store($request, Project $project): array
    {
        $request->validated();

        $user = auth()->user();

        if (! $user) {
            throw new \Exception('Unauthenticated.', 401);
        }

        if ($project->status !== 'completed') {
            throw new \Exception(
                'Project must be completed before rating.',
                400
            );
        }

        if ((int) $project->owner_id !== (int) $user->id) {
            throw new \Exception(
                'Only the project owner can rate this project.',
                403
            );
        }

        $exists = ProjectReview::query()
            ->where('project_id', $project->id)
            ->where('owner_id', $user->id)
            ->exists();

        if ($exists) {
            throw new \Exception(
                'You have already reviewed this project.',
                409
            );
        }

        $review = ProjectReview::query()->create([
            'project_id' => $project->id,
            'owner_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $admins = User::role('company_admin')->get();

        foreach ($admins as $admin) {

            $this->notificationService->send(
                $admin,
                [
                    'sender_id' => $user->id,

                    'project_id' => $project->id,

                    'type' => 'project_review',

                    'title' => 'تقييم جديد للمشروع',

                    'body' => "قام {$user->name} بتقييم المشروع {$project->name} بتقييم {$review->rating}/5.",

                    'data' => [
                        'review_id' => $review->id,
                        'project_id' => $project->id,
                        'owner_id' => $user->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                    ],
                ]
            );
        }

        $review->load([
            'project:id,name',
            'owner:id,name',
        ]);

        return [
            'message' => 'Project reviewed successfully.',
            'review' => [
                'id' => $review->id,
                'project' => [
                    'id' => $review->project->id,
                    'name' => $review->project->name,
                ],
                'owner' => [
                    'id' => $review->owner->id,
                    'name' => $review->owner->name,
                ],
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            ],
            'status' => 201,
        ];
    }
    public function statistics($request): array
    {
        $request->validated();

        if ($request->type === 'all') {

            $reviews = ProjectReview::query()
                ->with([
                    'project:id,name',
                    'owner:id,name',
                ])
                ->latest()
                ->get();

            return [
                'message' => 'Project reviews fetched successfully.',
                'data' => [
                    'total_reviews' => $reviews->count(),
                    'reviews' => $reviews->map(function ($review) {
                        return [
                            'id' => $review->id,
                            'project' => [
                                'id' => $review->project->id,
                                'name' => $review->project->name,
                            ],
                            'owner' => [
                                'id' => $review->owner->id,
                                'name' => $review->owner->name,
                            ],
                            'rating' => $review->rating,
                            'comment' => $review->comment,
                            'reviewed_at' => $review->created_at,
                        ];
                    }),
                ],
                'status' => 200,
            ];
        }

        if ($request->type === 'project') {

            $review = ProjectReview::query()
                ->with([
                    'project:id,name',
                    'owner:id,name',
                ])
                ->where('project_id', $request->project_id)
                ->first();

            if (! $review) {
                throw new \Exception(
                    'This project has not been reviewed yet.',
                    404
                );
            }

            return [
                'message' => 'Project review fetched successfully.',
                'data' => [
                    'review' => [
                        'id' => $review->id,
                        'project' => [
                            'id' => $review->project->id,
                            'name' => $review->project->name,
                        ],
                        'owner' => [
                            'id' => $review->owner->id,
                            'name' => $review->owner->name,
                        ],
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'reviewed_at' => $review->created_at,
                    ],
                ],
                'status' => 200,
            ];
        }

        if ($request->type === 'average') {

            $average = ProjectReview::query()->avg('rating');
            $count = ProjectReview::query()->count();

            return [
                'message' => 'Average rating fetched successfully.',
                'data' => [
                    'average_rating' => round($average, 2),
                    'total_reviews' => $count,
                ],
                'status' => 200,
            ];
        }

        if ($request->type === 'ranking') {

            $ranking = ProjectReview::query()
                ->with([
                    'project:id,name',
                    'owner:id,name',
                ])
                ->orderByDesc('rating')
                ->orderBy('created_at')
                ->get()
                ->values()
                ->map(function ($review, $index) {

                    return [
                        'rank' => $index + 1,
                        'project' => [
                            'id' => $review->project->id,
                            'name' => $review->project->name,
                        ],
                        'owner' => [
                            'id' => $review->owner->id,
                            'name' => $review->owner->name,
                        ],
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'reviewed_at' => $review->created_at,
                    ];
                });

            return [
                'message' => 'Projects ranking fetched successfully.',
                'data' => [
                    'ranking' => $ranking,
                ],
                'status' => 200,
            ];
        }

        throw new \Exception('Invalid type.', 400);
    }}
