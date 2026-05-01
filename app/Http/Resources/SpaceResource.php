<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'type' => $this->type,
            'area' => $this->area,
            'finish_type' => $this->finish_type,
            'toilet_type' => $this->toilet_type,
            'has_ceiling_ceramic' => (bool) $this->has_ceiling_ceramic,
            'ceiling_ceramic_area' => $this->ceiling_ceramic_area,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
