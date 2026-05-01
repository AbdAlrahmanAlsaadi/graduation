<?php

namespace App\Services;

use App\Models\Project;

class ProjectSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function buildSummary(Project $project): array
    {
        $project->load([
            'spaces',
            'workItems' => fn ($query) => $query->orderBy('order'),
        ]);

        $totals = [
            'paint' => 0.0,
            'ceramic' => 0.0,
            'gypsum' => 0.0,
            'none' => 0.0,
        ];

        $totalCeilingCeramicArea = 0.0;

        foreach ($project->spaces as $space) {
            if (array_key_exists($space->finish_type, $totals)) {
                $totals[$space->finish_type] += (float) $space->area;
            }

            $totalCeilingCeramicArea += (float) ($space->ceiling_ceramic_area ?? 0);
        }

        return [
            'project' => $project,
            'spaces' => $project->spaces,
            'work_items' => $project->workItems,
            'totals_by_finish_type' => $totals,
            'total_ceiling_ceramic_area' => $totalCeilingCeramicArea,
        ];
    }
}
