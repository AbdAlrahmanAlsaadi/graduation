<?php

namespace App\Policies;

use App\Models\ProgressUpdateRequest;
use App\Models\Project;
use App\Models\User;

class ProgressUpdateRequestPolicy
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
     * The requester or a project engineer/manager can view a request.
     */
    public function view(User $user, ProgressUpdateRequest $request): bool
    {
        if ($user->id === $request->requested_by) {
            return true;
        }

        return $this->isEngineerOnProject($user, $request->project);
    }

    /**
     * Only project_manager or company_admin assigned to the project can approve.
     */
    public function approve(User $user, ProgressUpdateRequest $request): bool
    {
        return $this->canReview($user, $request);
    }

    /**
     * Only project_manager or company_admin assigned to the project can reject.
     */
    public function reject(User $user, ProgressUpdateRequest $request): bool
    {
        return $this->canReview($user, $request);
    }

    /* ── Private helpers ───────────────────────────────────────── */

    private function canReview(User $user, ProgressUpdateRequest $request): bool
    {
        if (!$user->hasAnyRole(['project_manager', 'company_admin'])) {
            return false;
        }

        return $this->isAssignedToProject($user, $request->project);
    }

    private function isEngineerOnProject(User $user, Project $project): bool
    {
        if ($user->hasRole('company_admin')) {
            return true;
        }

        return $this->isAssignedToProject($user, $project);
    }
    private function isAssignedToProject(User $user, Project $project): bool
    {
        // Company admin can access all projects
        if ($user->hasRole('company_admin')) {
            return true;
        }

        // Direct assignment
        if ((int) $project->project_manager_id === (int) $user->id) {
            return true;
        }

        if ((int) $project->assistant_engineer_id === (int) $user->id) {
            return true;
        }

        // Pivot table assignment
        return $project->projectEngineers()
            ->where('user_id', $user->id)
            ->exists();
    }}
