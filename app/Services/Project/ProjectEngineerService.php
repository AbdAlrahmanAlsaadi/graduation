<?php

namespace App\Services\Project;

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

            if($role == 'project_manager')
                $project->update([
                    'project_manager_id' => $assignment->user_id,
                ]);
            else if($role == 'assistant') $project->update([
                'assistant_engineer_id' => $assignment->user_id,
            ]);
            else $project->update([
                'owner_id' => $assignment->user_id,
            ]);
            $project->save();

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

            if($assignment->role == 'project_manager'){
                $project->update([
                    'project_manager_id' => null
                ]);
            } else if($assignment->role == 'assistant') {
                $project->update([
                    'assistant_engineer_id' => null
                ]);
            } else {
                $project->update([
                    'owner_id' => null
                ]);
            }
            $project->save();

            $assignment->delete();

            return true;
        });
    }
}
