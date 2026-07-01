<?php

namespace App\Policies;

use App\Models\DurationExtensionRequest;
use App\Models\Project;
use App\Models\User;

class DurationExtensionRequestPolicy
{
    /**
     * Assistant engineers assigned to the project can create requests.
     */
    public function create(User $user, Project $project): bool
    {
        if (!$user->hasRole('assistant')) {
            return false;
        }

        return $this->isAssignedToProject($user, $project);
    }

    /**
     * Any user assigned to the project can list requests.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $this->isAssignedToProject($user, $project);
    }

    /**
     * Only project_manager or company_admin assigned to the project can approve.
     */
    public function approve(User $user, DurationExtensionRequest $request): bool
    {
        return $this->canReview($user, $request);
    }

    /**
     * Only project_manager or company_admin assigned to the project can reject.
     */
    public function reject(User $user, DurationExtensionRequest $request): bool
    {
        return $this->canReview($user, $request);
    }

    /* ── Private helpers ───────────────────────────────────────── */

    private function canReview(User $user, DurationExtensionRequest $request): bool
    {
        if (!$user->hasAnyRole(['project_manager', 'company_admin'])) {
            return false;
        }

        return $this->isAssignedToProject($user, $request->project);
    }

    private function isAssignedToProject(User $user, Project $project): bool
    {
        if ($project->project_manager_id === $user->id) {
            return true;
        }

        if ($project->assistant_engineer_id === $user->id) {
            return true;
        }

        return $project->projectEngineers()
            ->where('user_id', $user->id)
            ->exists();
    }
}
