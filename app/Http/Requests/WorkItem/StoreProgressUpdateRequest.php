<?php

namespace App\Http\Requests\WorkItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy in controller
    }

    public function rules(): array
    {
        return [
            'completed_wood_doors'     => 'sometimes|integer|min:0',
            'completed_aluminum_doors' => 'sometimes|integer|min:0',
            'completed_windows'        => 'sometimes|integer|min:0',
            'total_wood_doors'         => 'sometimes|integer|min:0',
            'total_aluminum_doors'     => 'sometimes|integer|min:0',
            'total_windows'            => 'sometimes|integer|min:0',
            'tile_length'              => 'sometimes|numeric|min:0',
            'tile_width'               => 'sometimes|numeric|min:0',
            'total_area_m2'            => 'sometimes|numeric|min:0',
            'completed_tiles'          => 'sometimes|integer|min:0',
            'ceramic_length'           => 'sometimes|numeric|min:0',
            'ceramic_width'            => 'sometimes|numeric|min:0',
            'completed_pieces'         => 'sometimes|integer|min:0',
            'rooms_total'              => 'sometimes|integer|min:0',
            'rooms_completed'          => 'sometimes|integer|min:0',
            'kitchen_done'             => 'sometimes|boolean',
            'bathroom_done'            => 'sometimes|boolean',
            'toilet_done'              => 'sometimes|boolean',
            'total_doors'              => 'sometimes|integer|min:0',
            'completed_doors'          => 'sometimes|integer|min:0',
            'kitchen_cabinet_done'     => 'sometimes|boolean',
            'total_aluminum'           => 'sometimes|integer|min:0',
            'completed_aluminum'       => 'sometimes|integer|min:0',
            'final_items_total'        => 'sometimes|integer|min:0',
            'final_items_completed'    => 'sometimes|integer|min:0',
            'all_finished'             => 'sometimes|boolean',
            'rooms_status'             => 'sometimes|array',
            'rooms_status.*'           => 'boolean',
            'meta'                     => 'sometimes|array',
            'photos'                   => 'sometimes|array',
            'photos.*'                 => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
