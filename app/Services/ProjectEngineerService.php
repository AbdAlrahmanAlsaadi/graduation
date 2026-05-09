<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectEngineer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProjectEngineerService
{
    public function list(Project $project): Collection
    {
        return $project->projectEngineers()->with('user')->get();
    }

    public function assign(Project $project, int $userId, string $role, ?string $assignedAt): ProjectEngineer
    {
        return DB::transaction(function () use ($project, $userId, $role, $assignedAt) {
            $assignment = $project->projectEngineers()->firstOrCreate(
                [
                    'user_id' => $userId,
                    'role' => $role,
                ],
                [
                    'assigned_at' => $assignedAt,
                ]
            );

            return $assignment->load('user');
        });
    }

    public function remove(Project $project, int $assignmentId): bool
    {
        return DB::transaction(function () use ($project, $assignmentId) {
            $assignment = $project->projectEngineers()
                ->where('id', $assignmentId)
                ->first();

            if (! $assignment) {
                return false;
            }

            $assignment->delete();

            return true;
        });
    }
}
