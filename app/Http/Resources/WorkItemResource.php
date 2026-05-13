<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'quality_level' => $this->quality_level,
            'duration_days' => $this->duration_days,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'is_custom' => (bool) $this->is_custom,
            'details' => WorkItemDetailResource::collection($this->whenLoaded('details')),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
