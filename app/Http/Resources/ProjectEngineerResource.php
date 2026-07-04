<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectEngineerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        if (! $user) {
            return parent::toArray($request);
        }

        return [
            
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'internal_id' => $user->internal_id,
            'status' => $user->status,
            'role' => $this->role,
            'assigned_at' => $this->assigned_at,
            'assignment_id' => $this->id, // just in case they need to remove it later
        ];
    }
}
