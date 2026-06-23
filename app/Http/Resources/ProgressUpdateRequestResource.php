<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressUpdateRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'project_id'   => $this->project_id,
            'work_item_id' => $this->work_item_id,
            'type'         => $this->type,
            'status'       => $this->status,
            'payload'      => $this->payload,
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
