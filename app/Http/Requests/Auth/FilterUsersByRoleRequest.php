<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterUsersByRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::in(['all', 'project_manager', 'assistant', 'project_owner']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'The role field is required.',
            'role.in' => 'Invalid role. Available values are: all, project_manager, assistant, project_owner.',
        ];
    }
}
