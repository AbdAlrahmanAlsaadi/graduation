<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class SearchProjectRequest extends FormRequest
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
