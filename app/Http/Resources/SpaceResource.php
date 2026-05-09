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
            'wall_area' => $this->wall_area,
            'floor_area' => $this->floor_area,
            'wall_finish_type' => $this->wall_finish_type,
            'ceiling_finish_type' => $this->ceiling_finish_type,
            'toilet_type' => $this->toilet_type,
            'ceiling_ceramic_area' => $this->ceiling_ceramic_area,
            'is_balcony_floor_tiled' => (bool) $this->is_balcony_floor_tiled,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
