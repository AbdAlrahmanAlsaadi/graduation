<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.required' => 'Keyword is required.',
        ];
    }
}
