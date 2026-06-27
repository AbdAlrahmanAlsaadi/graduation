<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProgressUpdateRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->payload ?? [];

        // Extract photo data and build accessible URLs
        $photos = collect($payload['_temp_photos'] ?? [])->map(fn($photo) => [
            'original_name' => $photo['original_name'] ?? basename($photo['path']),
            'url'           => asset('storage/' . $photo['path']),
        ])->values()->toArray();

        // Remove internal metadata from the visible payload
        unset($payload['_temp_photos']);

        return [
            'id'           => $this->id,
            'project_id'   => $this->project_id,
            'work_item_id' => $this->work_item_id,
            'type'         => $this->type,
            'status'       => $this->status,
            'payload'      => $payload,
            'photos'       => $photos,
            'comment'      => $this->comment,
            'requester'    => $this->whenLoaded('requester', fn() => [
                'id'   => $this->requester->id,
                'name' => $this->requester->name,
            ]),
            'reviewer'     => $this->whenLoaded('reviewer', fn() => $this->reviewer ? [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'reviewed_at'  => $this->reviewed_at,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
