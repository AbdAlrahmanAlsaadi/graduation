<?php

namespace App\Services\AI;

use App\Models\Project;

class AIContextBuilderService
{
    public function build(int $projectId): array
    {
        $project = Project::with([
            'owner',
            'projectManager',
            'assistantEngineer',
            'spaces',
            'workItems.details',
            'workItems.materials',
            'workItems.progressPhotos',
            'documents',
            'invoices',
            'laborCosts',
            'workshopExpenses',
            'progressUpdateRequests',
        ])->findOrFail($projectId);

        return [
            'project' => $this->project($project),
            'team' => $this->team($project),
            'spaces' => $this->spaces($project),
            'work_items' => $this->workItems($project),
            'financials' => $this->financials($project),
            'progress' => $this->progress($project),
            'metadata' => $this->metadata($project),
        ];
    }

    private function project($project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'location' => $project->location,
            'area' => $project->apartment_area,
            'height' => $project->height,
            'status' => $project->status,
        ];
    }

    private function user($user): ?array
    {
        if (!$user) return null;

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    private function team($project): array
    {
        return [
            'owner' => $this->user($project->owner),
            'project_manager' => $this->user($project->projectManager),
            'assistant_engineer' => $this->user($project->assistantEngineer),
        ];
    }

    private function spaces($project): array
    {
        return $project->spaces->map(function ($space) {
            return [
                'id' => $space->id,
                'type' => $space->type,
                'wall_area' => $space->wall_area,
                'ceiling_area' => $space->ceiling_area,
                'wall_finish_type' => $space->wall_finish_type,
                'ceiling_finish_type' => $space->ceiling_finish_type,
                'toilet_type' => $space->toilet_type,
                'is_shed_floor_tiled' => $space->is_shed_floor_tiled,
            ];
        })->toArray();
    }

    private function workItems($project): array
    {
        return $project->workItems->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status ?? 'planned',
                'duration_days' => $item->duration_days,
                'quality_level' => $item->quality_level,

                'details' => $item->details->map(fn($d) => [
                    'key' => $d->key,
                    'value' => $d->value,
                    'unit' => $d->unit,
                ])->toArray(),

                'materials' => $item->materials
                    ->pluck('name')
                    ->values()
                    ->toArray(),
            ];
        })->toArray();
    }

    private function financials($project): array
    {
        return [
            'invoices' => $project->invoices->take(20)->map(fn($i) => [
                'amount' => $i->amount ?? null,
                'status' => $i->status ?? null,
            ])->toArray(),

            'labor_costs' => $project->laborCosts->take(20)->map(fn($l) => [
                'amount' => $l->amount ?? null,
            ])->toArray(),

            'expenses_count' => $project->workshopExpenses()->count(),
        ];
    }

    private function progress($project): array
    {
        return [
            'progress_requests_count' => $project->progressUpdateRequests()->count(),
        ];
    }

    private function metadata($project): array
    {
        return [
            'generated_at' => now()->toDateTimeString(),
            'ai_version' => 'v2-context-clean',
            'total_work_items' => $project->workItems()->count(),
            'total_spaces' => $project->spaces()->count(),
        ];
    }
}
