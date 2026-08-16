<?php

namespace App\Services\Project;


use App\Models\Project;
    use App\Services\WorkItem\WorkItemProgressService;

class OwnerProjectService
{

    public function myProjects(?string $status = 'all'): array
    {
        $query = Project::query()
            ->with('workItems')
            ->where('owner_id', auth()->id());

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            throw new \Exception(
                'No projects found.',
                404
            );
        }

        $progressService = new WorkItemProgressService();

        return [
            'message' => 'Projects fetched successfully.',

            'projects' => $projects->map(function ($project) use ($progressService) {

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'location' => $project->location,

                    'progress_percent' =>
                    $progressService->computeProjectPercent($project),

                    'status' => $project->status,

                    'started_at' => $project->started_at?->format('Y-m-d'),
                    'completed_at' => $project->status === 'completed' ?
                    $project->completed_at?->format('Y-m-d') : null,

                                        ];
            }),

            'status' => 200,
        ];
    }
    private function calculateProjectProgress(Project $project): float
    {
        $items = $project->workItems;

        if ($items->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($items as $item) {

            $total += match ($item->status) {
                'completed' => 100,
                'ongoing'   => 50,
                default     => 0,
            };
        }

        return round($total / $items->count(), 2);
    }


    public function show(Project $project): array
    {
        if ($project->owner_id !== auth()->id()) {
            throw new \Exception('Unauthorized.', 403);
        }

        $project->load('workItems');

        $progressService = new WorkItemProgressService();

        $progress = $progressService->computeProjectPercent($project);

        return [
            'message' => 'Project details fetched successfully.',

            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'apartment_area' => $project->apartment_area,
                'height' => $project->height,
                'progress_percent' => $progress,
                'status' => $project->status,
            ],

            'status' => 200,
        ];
    }
    public function spaces(Project $project): array
    {
        if ($project->owner_id !== auth()->id()) {
            throw new \Exception('Unauthorized.', 403);
        }

        return [

            'message' => 'Project spaces fetched successfully.',

            'spaces' => $project->spaces->map(function ($space) {

                return [

                    'id' => $space->id,

                    'type' => $space->type,

                    'wall_area' => $space->wall_area,

                    'wall_finish_type' => $space->wall_finish_type,

                    'ceiling_area' => $space->ceiling_area,

                    'ceiling_finish_type' => $space->ceiling_finish_type,

                    'toilet_type' => $space->toilet_type,

                    'is_floor_tiled' => $space->is_floor_tiled,
                ];
            }),

            'status' => 200,
        ];
    }

    public function workItems(Project $project): array
    {
        if ($project->owner_id !== auth()->id()) {
            throw new \Exception('Unauthorized.', 403);
        }

        $progressService = new WorkItemProgressService();

        return [

            'message' => 'Project work items fetched successfully.',

            'work_items' => $project->workItems()
                ->with('details')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($item) use ($progressService) {

                    return [

                        'id' => $item->id,

                        'order' => $item->sort_order,

                        'name' => $item->name,

                        'status' => $item->status,

                        'percent' => $progressService
                            ->computeWorkItemPercent($item),
                    ];
                }),

            'status' => 200,
        ];
    }
}
