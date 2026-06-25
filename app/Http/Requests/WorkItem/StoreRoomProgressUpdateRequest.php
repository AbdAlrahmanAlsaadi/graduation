<?php

namespace App\Http\Requests\WorkItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy in controller
    }

    public function rules(): array
    {
        return [
            'completed' => ['required', 'boolean'],
            'photos'    => ['sometimes', 'array'],
            'photos.*'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
