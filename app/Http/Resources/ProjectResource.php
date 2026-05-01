<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_name' => $this->owner_name,
            'location' => $this->location,
            'total_area' => $this->total_area,
            'height' => $this->height,
            'project_manager_id' => $this->project_manager_id,
            'assistant_engineer_id' => $this->assistant_engineer_id,
            'owner_id' => $this->owner_id,
            'spaces' => SpaceResource::collection($this->whenLoaded('spaces')),
            'work_items' => WorkItemResource::collection($this->whenLoaded('workItems')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
