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
            'progress_photos' => $this->whenLoaded('progressPhotos', function () {
                return $this->progressPhotos->map(fn($photo) => [
                    'id' => $photo->id,
                    'file_path' => $photo->file_path,
                    'file_url' => asset('storage/' . $photo->file_path),
                    'original_name' => $photo->original_name,
                    'created_at' => $photo->created_at,
                ])->values();
            }),
            
        ];
    }
}