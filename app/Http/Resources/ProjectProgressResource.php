<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_percent' => $this->project_percent,//$this->whenLoaded('project_percent', $this->project_percent ?? null),
            'items' => WorkItemProgressResource::collection($this->whenLoaded('workItems', $this->workItems ?? collect())),
        ];
    }
}
