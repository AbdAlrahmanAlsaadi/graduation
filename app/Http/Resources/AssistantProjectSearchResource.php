<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistantProjectSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'status' => $this->status,

            'project_manager' => $this->whenLoaded(
                'projectManager',
                fn() => $this->projectManager
                    ? [
                        'id' => $this->projectManager->id,
                        'name' => $this->projectManager->name,
                    ]
                    : null
            ),

            'assistant_engineer' => $this->whenLoaded(
                'assistantEngineer',
                fn() => $this->assistantEngineer
                    ? [
                        'id' => $this->assistantEngineer->id,
                        'name' => $this->assistantEngineer->name,
                    ]
                    : null
            ),

            'owner' => $this->whenLoaded(
                'owner',
                fn() => $this->owner
                    ? [
                        'id' => $this->owner->id,
                        'name' => $this->owner->name,
                    ]
                    : null
            ),

            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
