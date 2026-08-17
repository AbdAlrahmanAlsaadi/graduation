<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkItemProgressPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            'photos' => $this->progressPhotos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'file_path' => $photo->file_path,
                    'original_name' => $photo->original_name,

                    'space' => $photo->space ? [
                        'id' => $photo->space->id,
                        'name' => $photo->space->name,
                    ] : null,

                    'created_at' => $photo->created_at?->toISOString(),
                ];
            }),
        ];
    }
}
