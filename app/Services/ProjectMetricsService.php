<?php

namespace App\Services;

use App\Models\Project;

class ProjectMetricsService
{
    /**
     * Extract comprehensive area and room metrics for a given project.
     */
    public function extractMetrics(Project $project): array
    {
        $spaces = $project->spaces;
        $aptArea = (float) $project->apartment_area;

        $wallArea = (float) $spaces->sum('wall_area');
        $shedTiledArea = (float) $spaces->where('type', 'shed')->where('is_shed_floor_tiled', true)->sum('ceiling_area');
        $ceramicWallArea = (float) $spaces->where('wall_finish_type', 'ceramic')->sum('wall_area');
        $ceramicCeilingArea = (float) $spaces->where('ceiling_finish_type', 'ceramic')->sum('ceiling_area');
        $totalCeramicArea = $aptArea + $shedTiledArea + $ceramicWallArea + ($ceramicCeilingArea * 2);

        $paintWallArea = (float) $spaces->where('wall_finish_type', 'paint')->sum('wall_area');
        $paintOrGypsumCeilingArea = (float) $spaces->whereIn('ceiling_finish_type', ['paint', 'gypsum'])->sum('ceiling_area');
        $paintSurfaceTotal = $paintWallArea + $paintOrGypsumCeilingArea;

        $gypsumWallArea = (float) $spaces->where('wall_finish_type', 'gypsum')->sum('wall_area');
        $gypsumCeilingArea = (float) $spaces->where('ceiling_finish_type', 'gypsum')->sum('ceiling_area');
        $gypsumSurfaceTotal = $gypsumWallArea + $gypsumCeilingArea;

        $roomsCount = $spaces->where('type', 'room')->count();
        $roomsAndSalonsCount = $spaces->whereIn('type', ['room', 'salon'])->count();
        $bathroomsCount = $spaces->where('type', 'bathroom')->count();
        $toiletsCount = $spaces->where('type', 'toilet')->count();
        $bathroomsAndToiletsCount = $spaces->whereIn('type', ['bathroom', 'toilet'])->count();
        $kitchensCount = $spaces->where('type', 'kitchen')->count();
        $kitchenArea = (float) $spaces->where('type', 'kitchen')->sum('ceiling_area');
        $allSpacesCount = $spaces->count();
        $westernToiletsCount = $spaces->where('toilet_type', 'western')->count();
        $arabicToiletsCount = $spaces->where('toilet_type', 'arabic')->count();

        // Get details from work items if present, or fallbacks
        $woodDoors = $this->getWorkItemDetailValue($project, ['ملابن الأبواب', 'أبواب ونجارة'], ['total_wood_doors', 'total_doors'])
            ?: max(1, $roomsAndSalonsCount + $bathroomsAndToiletsCount + $kitchensCount);

        $aluminumDoors = $this->getWorkItemDetailValue($project, ['ملابن الأبواب'], ['total_aluminum_doors']) ?: 1;

        $windows = $this->getWorkItemDetailValue($project, ['ملابن الأبواب', 'ألمنيوم وأبجورات'], ['total_windows', 'total_aluminum'])
            ?: max(1, $roomsAndSalonsCount + $kitchensCount);

        return [
            'apt_area' => $aptArea,
            'wall_area' => $wallArea,
            'ceramic_wall_area' => $ceramicWallArea,
            'ceramic_ceiling_area' => $ceramicCeilingArea,
            'total_ceramic_area' => $totalCeramicArea,
            'paint_surface_total' => $paintSurfaceTotal,
            'gypsum_surface_total' => $gypsumSurfaceTotal,
            'rooms_count' => $roomsCount,
            'rooms_and_salons_count' => $roomsAndSalonsCount,
            'bathrooms_count' => $bathroomsCount,
            'toilets_count' => $toiletsCount,
            'bathrooms_and_toilets_count' => $bathroomsAndToiletsCount,
            'kitchens_count' => $kitchensCount,
            'kitchen_area' => $kitchenArea,
            'all_spaces_count' => $allSpacesCount,
            'western_toilets_count' => $westernToiletsCount,
            'arabic_toilets_count' => $arabicToiletsCount,
            'wood_doors' => $woodDoors,
            'aluminum_doors' => $aluminumDoors,
            'windows' => $windows,
        ];
    }

    private function getWorkItemDetailValue(Project $project, array $itemNames, array $keys): float
    {
        foreach ($itemNames as $name) {
            $item = $project->workItems->firstWhere('name', $name);
            if ($item) {
                foreach ($keys as $key) {
                    $detail = $item->details->firstWhere('key', $key);
                    if ($detail && is_numeric($detail->value) && (float) $detail->value > 0) {
                        return (float) $detail->value;
                    }
                }
            }
        }

        return 0;
    }
}
