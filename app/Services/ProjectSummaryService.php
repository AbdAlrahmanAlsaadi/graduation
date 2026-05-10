<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Space;

class ProjectSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function buildSummary(Project $project): array
    {
        $project->load([
            'spaces',
            'workItems' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        $totals = array_fill_keys(Space::FINISH_TYPES, 0.0);

        $totalCeilingCeramicArea = 0.0;

        foreach ($project->spaces as $space) {
            if (array_key_exists($space->wall_finish_type, $totals)) {
                $totals[$space->wall_finish_type] += (float) $space->wall_area;
            }

            $totalCeilingCeramicArea += (float) ($space->ceiling_area ?? 0);
        }

        return [
            'project' => $project,
            'spaces' => $project->spaces,
            'work_items' => $project->workItems,
            'totals_by_finish_type' => $totals,
            'total_ceiling_area' => $totalCeilingCeramicArea,
        ];
    }
}
