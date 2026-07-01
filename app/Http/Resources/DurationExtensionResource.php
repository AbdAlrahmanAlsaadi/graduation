<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DurationExtensionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'project_id'              => $this->project_id,
            'work_item_id'            => $this->work_item_id,
            'status'                  => $this->status,
            'requested_duration_days' => $this->requested_duration_days,
            'reason'                  => $this->reason,
            'comment'                 => $this->comment,
            'requester'               => $this->whenLoaded('requester', fn() => [
                'id'   => $this->requester->id,
                'name' => $this->requester->name,
            ]),
            'reviewer'                => $this->whenLoaded('reviewer', fn() => $this->reviewer ? [
                'id'   => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'reviewed_at'             => $this->reviewed_at,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }
}
