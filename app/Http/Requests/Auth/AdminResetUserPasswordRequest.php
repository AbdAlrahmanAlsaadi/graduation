<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_password.required' => 'Admin password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'Password must be at least 6 characters.',
        ];
    }
}
