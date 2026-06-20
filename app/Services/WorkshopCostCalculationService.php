<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class WorkshopCostCalculationService
{
    private function validateProjectAccess(Project $project): void
    {
        $user = Auth::user();

        $isCompanyAdmin =
            $user->hasRole('company_admin');

        $isProjectManager =
            $user->hasRole('project_manager')
            && $project->project_manager_id == $user->id;

        $isAssistant =
            $user->hasRole('assistant')
            && $project->assistant_engineer_id == $user->id;

        if (
            ! $isCompanyAdmin
            && ! $isProjectManager
            && ! $isAssistant
        ) {
            throw new \Exception(
                'You are not allowed to access this project.',
                403
            );
        }
    }

    public function calculatePlasterCost(
        int $projectId,
        float $pricePerMeter,
        int $beamsCount
    ): array {

        $project = Project::query()
            ->with('spaces')
            ->find($projectId);

        if (! $project) {
            throw new \Exception(
                'Project not found.',
                404
            );
        }

        $this->validateProjectAccess($project);

        $wallArea = $project->spaces
            ->sum('wall_area');

        $apartmentCeilingArea = $project->spaces
            ->where('type', '!=', 'shed')
            ->sum('ceiling_area');

        $shedCeilingArea = $project->spaces
            ->where('type', 'shed')
            ->sum('ceiling_area');

        $beamsArea =
            $beamsCount * $project->height;

        $totalArea =
            $wallArea
            + $beamsArea
            + ($apartmentCeilingArea * 2)
            + ($shedCeilingArea * 2);

        $totalCost =
            $totalArea * $pricePerMeter;

        return [

            'status' => 200,

            'message' =>
            'Plaster workshop cost calculated successfully.',

            'data' => [

                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],

                'formula' => [

                    'wall_area' =>
                    round($wallArea, 2),

                    'beams_count' =>
                    $beamsCount,

                    'beams_area' =>
                    round($beamsArea, 2),

                    'apartment_ceiling_area_x2' =>
                    round($apartmentCeilingArea * 2, 2),

                    'shed_ceiling_area_x2' =>
                    round($shedCeilingArea * 2, 2),

                    'total_area' =>
                    round($totalArea, 2),
                ],

                'pricing' => [

                    'price_per_meter' =>
                    $pricePerMeter,

                    'final_cost' =>
                    round($totalCost, 2),
                ],
            ],
        ];
    }

    public function calculatePaintingCost(
        int $projectId,
        float $pricePerMeter,
        int $beamsCount
    ): array {

        $result = $this->calculatePlasterCost(
            $projectId,
            $pricePerMeter,
            $beamsCount
        );

        $result['message'] =
            'Painting workshop cost calculated successfully.';

        return $result;
    }


        public function calculateTileCost(
    int $projectId,
    float $pricePerMeter,
    float $skirtingFactor,
    float $sinkInstallationCost
): array {

        $project = Project::query()
            ->with('spaces')
            ->find($projectId);

        if (! $project) {
            throw new \Exception(
                'Project not found.',
                404
            );
        }

        $this->validateProjectAccess($project);

        $spaces = $project->spaces;

        $apartmentArea =
            (float) $project->apartment_area;

        $projectHeight =
            (float) $project->height;

        $shedTiledArea = $spaces
            ->where('type', 'shed')
            ->where('is_shed_floor_tiled', true)
            ->sum('ceiling_area');

        $ceramicWallArea = $spaces
            ->where('wall_finish_type', 'ceramic')
            ->sum('wall_area');

        $ceramicCeilingArea = $spaces
            ->where('ceiling_finish_type', 'ceramic')
            ->sum('ceiling_area');

        $WallAreaeithoutCeramic = $spaces
            ->where('ceiling_finish_type','!=', 'ceramic')
            ->sum('wall_area');
        $skirtingArea = 0;

        if ($projectHeight > 0) {

            $skirtingArea =
                ($WallAreaeithoutCeramic / $projectHeight)
                * $skirtingFactor;
        }

        $totalArea =

            $apartmentArea

            + $shedTiledArea

            + $ceramicWallArea

            + ($ceramicCeilingArea * 2)

            + $skirtingArea;

        $workshopCost =

            ($totalArea * $pricePerMeter)

            + $sinkInstallationCost;

        return [

            'status' => 200,

            'message' =>
            'Tile workshop cost calculated successfully.',

            'data' => [

                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],

                'formula' => [

                    'apartment_area' =>
                    round($apartmentArea, 2),

                    'shed_tiled_area' =>
                    round($shedTiledArea, 2),

                    'ceramic_wall_area' =>
                    round($ceramicWallArea, 2),

                    'ceramic_ceiling_area_x2' =>
                    round($ceramicCeilingArea * 2, 2),

                    'skirting_factor' =>
                    $skirtingFactor,

                    'skirting_area' =>
                    round($skirtingArea, 2),

                    'total_area' =>
                    round($totalArea, 2),
                ],

                'pricing' => [

                    'price_per_meter' =>
                    $pricePerMeter,

                    'sink_installation_cost' =>
                    $sinkInstallationCost,

                    'final_cost' =>
                    round($workshopCost, 2),
                ],
            ],
        ];
    }
}
