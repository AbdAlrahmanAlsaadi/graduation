<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'user_id' => $this->user_id,

            'project_id' => $this->project_id,

            'project_work_item_id' => $this->project_work_item_id,

            'type' => $this->type,

            'title' => $this->title,

            'body' => $this->body,

            'is_read' => (bool) $this->is_read,

            'read_at' => $this->read_at,

            'data' => $this->data,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}
