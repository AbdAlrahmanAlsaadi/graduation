<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'project' => new ProjectResource($this->resource['project']->withoutRelations()),
            'spaces' => SpaceResource::collection($this->resource['spaces']),
            'work_items' => WorkItemResource::collection($this->resource['work_items']),
            'totals_by_finish_type' => $this->resource['totals_by_finish_type'],
            'total_ceiling_area' => $this->resource['total_ceiling_area'],
        ];
    }
}
