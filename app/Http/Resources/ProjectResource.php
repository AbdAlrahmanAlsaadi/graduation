<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\WorkItemProgressService;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = new WorkItemProgressService();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'apartment_area' => $this->apartment_area,
            'height' => $this->height,
            'status' => $this->status,
            'project_manager_id' => $this->project_manager_id,
            'assistant_engineer_id' => $this->assistant_engineer_id,
            'owner_id' => $this->owner_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'spaces' => SpaceResource::collection($this->whenLoaded('spaces')),
            'work_items' => WorkItemResource::collection($this->whenLoaded('workItems')),
            'progress_percent' => $service->computeProjectPercent($this->resource),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'owner' => $this->owner ? new UserResource($this->owner) : null,
        ];
    }
}
