<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkItemProgressResource extends JsonResource
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
            'percent' => $this->percent,
            'weight' => $this->weight ?? 1,
            'details' => $this->details->map(fn($d) => [
                $d->key => $d->value,
                'unit' => $d->unit,
                'meta' => $d->meta ?? null,
            ])->values(),
            
        ];
    }
}
