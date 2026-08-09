<?php

namespace App\Services\Project;

use App\Services\Workshop\WorkshopCostCalculationService;

use App\Models\Project;
use App\Models\WorkshopExpense;
use Illuminate\Support\Facades\Auth;

class ProjectWorkshopEstimationService
{
    public function __construct(
        private WorkshopCostCalculationService $workshopCalculationService
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
     * Estimate all workshop labor costs for a project using historical workshop expenses and formulas.
     */
    public function estimateProjectWorkshops(
        int $projectId,
        int $beamsCount = 0,
        float $skirtingFactor = 0.1
    ): array {
        $project = Project::query()->with(['spaces', 'workItems'])->find($projectId);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        $this->validateProjectAccess($project);

        // Find the most recent project (excluding current) that has workshop expenses
        $previousExpense = WorkshopExpense::query()
            ->where('project_id', '!=', $project->id)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $previousExpense) {
            return [
                'status' => 200,
                'message' => 'Estimation unavailable: No previous projects with workshop expense data found.',
                'data' => [
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'estimation_available' => false,
                    'workshops' => [],
                    'grand_total_workshop_cost' => null,
                ],
            ];
        }

        $previousProject = Project::query()
            ->with(['spaces', 'workItems'])
            ->find($previousExpense->project_id);

        if (! $previousProject) {
            return [
                'status' => 200,
                'message' => 'Estimation unavailable: Previous project data could not be loaded.',
                'data' => [
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'estimation_available' => false,
                    'workshops' => [],
                    'grand_total_workshop_cost' => null,
                ],
            ];
        }

        $workshops = [];
        $grandTotal = 0.0;

        // 1. ورشة الصحية - سواد وبياض
        $sanitaryPrevCost = $this->getWorkshopExpenseSum($previousProject->id, ['تمديدات صحية سواد', 'تمديدات صحية بياض']);
        $sanitaryCost = round($sanitaryPrevCost, 2);
        $workshops[] = [
            'workshop_name' => 'ورشة الصحية - سواد وبياض',
            'estimated_cost' => $sanitaryCost,
        ];
        $grandTotal += $sanitaryCost;

        // 2. ورشة الكهرباء - سواد وبياض
        $electricalPrevCost = $this->getWorkshopExpenseSum($previousProject->id, ['تمديدات كهرباء سواد', 'تمديدات كهرباء بياض']);
        $prevAptArea = (float) $previousProject->apartment_area;
        $currAptArea = (float) $project->apartment_area;
        $electricalCost = 0.0;

        if ($prevAptArea > 0) {
            $electricalCost = round(($electricalPrevCost / $prevAptArea) * $currAptArea, 2);
        }
        $workshops[] = [
            'workshop_name' => 'ورشة الكهرباء - سواد وبياض',
            'estimated_cost' => $electricalCost,
        ];
        $grandTotal += $electricalCost;

        // 3. ورشة الطينة / اللياسة
        $plasterPrevCost = $this->getWorkshopExpenseSum($previousProject->id, ['طينة / لياسة']);
        $prevPlasterArea = $this->calculatePlasterArea($previousProject, $beamsCount);
        $currPlasterArea = $this->calculatePlasterArea($project, $beamsCount);
        $plasterCost = 0.0;

        if ($prevPlasterArea > 0) {
            $plasterCost = round(($plasterPrevCost / $prevPlasterArea) * $currPlasterArea, 2);
        }
        $workshops[] = [
            'workshop_name' => 'ورشة الطينة / اللياسة',
            'estimated_cost' => $plasterCost,
        ];
        $grandTotal += $plasterCost;

        // 4. ورشة الدهان
        $paintPrevCost = $this->getWorkshopExpenseSum($previousProject->id, ['دهان']);
        $paintCost = 0.0;

        if ($prevPlasterArea > 0) {
            $paintCost = round(($paintPrevCost / $prevPlasterArea) * $currPlasterArea, 2);
        }
        $workshops[] = [
            'workshop_name' => 'ورشة الدهان',
            'estimated_cost' => $paintCost,
        ];
        $grandTotal += $paintCost;

        // 5. ورشة الجبس
        $gypsumPrevCost = $this->getWorkshopExpenseSum($previousProject->id, ['جبس بورد']);
        $prevGypsumCeiling = (float) $previousProject->spaces->where('ceiling_finish_type', 'gypsum')->sum('ceiling_area');
        $currGypsumCeiling = (float) $project->spaces->where('ceiling_finish_type', 'gypsum')->sum('ceiling_area');
        $gypsumCost = 0.0;

        if ($prevGypsumCeiling > 0) {
            $gypsumCost = round(($gypsumPrevCost / $prevGypsumCeiling) * $currGypsumCeiling, 2);
        }
        $workshops[] = [
            'workshop_name' => 'ورشة الجبس',
            'estimated_cost' => $gypsumCost,
        ];
        $grandTotal += $gypsumCost;

        // 6. ورشة البلاط وسيراميك الجدران والأسقف
        $tilePrevCost = $this->getWorkshopExpenseSum($previousProject->id, ['بلاط أرضيات', 'سيراميك جدران / أسقف']);
        $prevTileArea = $this->calculateTileArea($previousProject, $skirtingFactor);
        $currTileArea = $this->calculateTileArea($project, $skirtingFactor);
        $tileCost = 0.0;

        if ($prevTileArea > 0) {
            $tileCost = round(($tilePrevCost / $prevTileArea) * $currTileArea, 2);
        }
        $workshops[] = [
            'workshop_name' => 'ورشة البلاط وسيراميك الجدران والأسقف',
            'estimated_cost' => $tileCost,
        ];
        $grandTotal += $tileCost;

        return [
            'status' => 200,
            'message' => 'Project workshop costs estimated successfully.',
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'estimation_available' => true,
                'workshops' => $workshops,
                'grand_total_workshop_cost' => round($grandTotal, 2),
            ],
        ];
    }

    /**
     * Reusable helper method to get just the total estimated workshop cost float for a project.
     * Useful for Total Project Cost Estimation API or Comparison APIs.
     */
    public function getGrandTotalWorkshopCost(
        int $projectId,
        int $beamsCount = 0,
        float $skirtingFactor = 0.1
    ): ?float {
        $result = $this->estimateProjectWorkshops($projectId, $beamsCount, $skirtingFactor);
        if (! ($result['data']['estimation_available'] ?? false)) {
            return null;
        }

        return $result['data']['grand_total_workshop_cost'] ?? 0.0;
    }

    private function getWorkshopExpenseSum(int $projectId, array $workItemNames): float
    {
        return (float) WorkshopExpense::query()
            ->where('project_id', $projectId)
            ->whereHas('workItem', fn ($q) => $q->whereIn('name', $workItemNames))
            ->sum('amount');
    }

    private function calculatePlasterArea(Project $project, int $beamsCount): float
    {
        $wallArea = (float) $project->spaces->sum('wall_area');
        $apartmentCeilingArea = (float) $project->spaces->where('type', '!=', 'shed')->sum('ceiling_area');
        $shedCeilingArea = (float) $project->spaces->where('type', 'shed')->sum('ceiling_area');
        $beamsArea = $beamsCount * (float) $project->height;

        return $wallArea + $beamsArea + ($apartmentCeilingArea * 2) + ($shedCeilingArea * 2);
    }

    private function calculateTileArea(Project $project, float $skirtingFactor): float
    {
        $spaces = $project->spaces;
        $apartmentArea = (float) $project->apartment_area;
        $projectHeight = (float) $project->height;

        $shedTiledArea = (float) $spaces->where('type', 'shed')->where('is_shed_floor_tiled', true)->sum('ceiling_area');
        $ceramicWallArea = (float) $spaces->where('wall_finish_type', 'ceramic')->sum('wall_area');
        $ceramicCeilingArea = (float) $spaces->where('ceiling_finish_type', 'ceramic')->sum('ceiling_area');
        $wallAreaWithoutCeramic = (float) $spaces->where('ceiling_finish_type', '!=', 'ceramic')->sum('wall_area');

        $skirtingArea = 0.0;
        if ($projectHeight > 0) {
            $skirtingArea = ($wallAreaWithoutCeramic / $projectHeight) * $skirtingFactor;
        }

        return $apartmentArea + $shedTiledArea + $ceramicWallArea + ($ceramicCeilingArea * 2) + $skirtingArea;
    }
}
