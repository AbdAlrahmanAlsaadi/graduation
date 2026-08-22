<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,

            'work_item_name' => $this->whenLoaded('workItems', function () {
                return $this->workItems->pluck('name')->toArray();
            }),1
        ];
    }
}
