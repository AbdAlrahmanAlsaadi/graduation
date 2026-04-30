<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInternalUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => [
                'required',
                'string',
                Rule::in(['project_manager', 'assistant', 'project_owner']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'The role field is required.',
            'role.in' => 'Invalid role. Available roles are: project_manager, assistant, project_owner.',
        ];
    }
}
