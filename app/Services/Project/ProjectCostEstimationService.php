<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\WorkItemInvoice;
use App\Models\WorkshopExpense;
use Illuminate\Support\Facades\Auth;

class ProjectCostEstimationService
{
    public function __construct(
        private ProjectMaterialEstimationService $materialEstimationService,
        private ProjectWorkshopEstimationService $workshopEstimationService
    ) {}

    private function validateProjectAccess(Project $project): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Unauthenticated.', 401);
        }

        $isCompanyAdmin = $user->hasRole('company_admin');
        $isProjectManager = $user->hasRole('project_manager') && $project->project_manager_id == $user->id;
        $isAssistant = $user->hasRole('assistant') && $project->assistant_engineer_id == $user->id;
        $isOwner = $user->hasRole('project_owner') && $project->owner_id == $user->id;

        if (! $isCompanyAdmin && ! $isProjectManager && ! $isAssistant && ! $isOwner) {
            throw new \Exception('You are not allowed to access this project.', 403);
        }
    }

    /**
     * API 4: Calculate total estimated cost of project (Estimated Materials + Estimated Workshops).
     */
    public function estimateTotalProjectCost(
        int $projectId,
        int $beamsCount = 0,
        float $skirtingFactor = 0.1
    ): array {
        $project = Project::query()->find($projectId);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        $this->validateProjectAccess($project);

        $materialRes = $this->materialEstimationService->estimateProjectMaterials($projectId);
        $workshopRes = $this->workshopEstimationService->estimateProjectWorkshops($projectId, $beamsCount, $skirtingFactor);

        $matAvailable = $materialRes['data']['estimation_available'] ?? false;
        $workAvailable = $workshopRes['data']['estimation_available'] ?? false;

        $matCost = $matAvailable ? ($materialRes['data']['grand_total_price'] ?? 0.0) : null;
        $workCost = $workAvailable ? ($workshopRes['data']['grand_total_workshop_cost'] ?? 0.0) : null;

        $estimationAvailable = $matAvailable || $workAvailable;
        $grandTotal = null;

        if ($estimationAvailable) {
            $grandTotal = round(($matCost ?? 0.0) + ($workCost ?? 0.0), 2);
        }

        return [
            'status' => 200,
            'message' => 'Total project estimated cost calculated successfully.',
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'estimation_available' => $estimationAvailable,
                'estimated_materials_cost' => $matCost !== null ? round($matCost, 2) : null,
                'estimated_workshops_cost' => $workCost !== null ? round($workCost, 2) : null,
                'grand_total_estimated_cost' => $grandTotal,
            ],
        ];
    }

    /**
     * API 5: Compare actual current project cost with total estimated cost.
     */
    public function compareProjectCosts(
        int $projectId,
        int $beamsCount = 0,
        float $skirtingFactor = 0.1
    ): array {
        $project = Project::query()->find($projectId);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        $this->validateProjectAccess($project);

        // 1. Calculate actual current costs
        $actualMaterialsCost = (float) WorkItemInvoice::query()
            ->where('project_id', $projectId)
            ->sum('total_amount');

        $actualWorkshopsCost = (float) WorkshopExpense::query()
            ->where('project_id', $projectId)
            ->sum('amount');

        $returnsDeduction = 0.0; // Reserved for returns module deduction

        $netActualCost = round(($actualMaterialsCost + $actualWorkshopsCost) - $returnsDeduction, 2);

        // 2. Get total estimated cost using API 4 logic
        $totalEstimation = $this->estimateTotalProjectCost($projectId, $beamsCount, $skirtingFactor);

        $estData = $totalEstimation['data'];
        $grandEstimatedCost = $estData['grand_total_estimated_cost'];

        $variance = null;
        $variancePercentage = null;
        $statusLabel = 'no_estimation';

        if ($grandEstimatedCost !== null && $grandEstimatedCost > 0) {
            $variance = round($netActualCost - $grandEstimatedCost, 2);
            $variancePercentage = round(($variance / $grandEstimatedCost) * 100, 2);
            $statusLabel = $variance <= 0 ? 'under_budget' : 'over_budget';
        } elseif ($grandEstimatedCost === 0.0) {
            $variance = $netActualCost;
            $statusLabel = $netActualCost > 0 ? 'over_budget' : 'on_budget';
        }

        return [
            'status' => 200,
            'message' => 'Project cost comparison generated successfully.',
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'actual_cost' => [
                    'invoices_materials_cost' => round($actualMaterialsCost, 2),
                    'workshops_expenses_cost' => round($actualWorkshopsCost, 2),
                    'returns_deduction' => round($returnsDeduction, 2),
                    'net_actual_cost' => $netActualCost,
                ],
                'estimated_cost' => [
                    'estimation_available' => $estData['estimation_available'],
                    'estimated_materials_cost' => $estData['estimated_materials_cost'],
                    'estimated_workshops_cost' => $estData['estimated_workshops_cost'],
                    'grand_total_estimated_cost' => $grandEstimatedCost,
                ],
                'comparison' => [
                    'variance' => $variance,
                    'variance_percentage' => $variancePercentage,
                    'status_label' => $statusLabel,
                ],
            ],
        ];
    }
}
