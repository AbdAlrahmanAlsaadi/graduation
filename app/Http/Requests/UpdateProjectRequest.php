<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'total_area' => ['required', 'numeric', 'min:1'],
            'height' => ['required', 'numeric', 'min:1'],
            'project_manager_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
            'assistant_engineer_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
            'owner_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}
