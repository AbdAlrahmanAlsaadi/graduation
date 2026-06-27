<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkItemDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            $this->key => $this->value,
            'unit' => $this->unit,
            'progress_photos' => $this->progressPhotos->map(fn($photo) => [
                'id' => $photo->id,
                'file_path' => $photo->file_path,
                'file_url' => asset('storage/' . $photo->file_path),
                'original_name' => $photo->original_name,
                'created_at' => $photo->created_at,
            ])->values(),
        ];
    }
}
