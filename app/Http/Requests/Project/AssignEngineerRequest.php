<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignEngineerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => [
                'required',
                'string',
                Rule::in(['project_manager', 'assistant', 'project_owner']),
                function ($attribute, $value, $fail) {
                    $userId = $this->input('user_id');
                    if ($userId) {
                        $user = \App\Models\User::find($userId);
                        if ($user && ! $user->hasRole($value)) {
                            $fail('The selected role must match the user\'s actual role.');
                        }
                    }
                },
            ],
            'assigned_at' => ['nullable', 'date'],
        ];
    }
}
