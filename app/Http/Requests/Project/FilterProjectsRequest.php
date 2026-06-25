<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class FilterProjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'in:all,ongoing,completed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' =>
            'Available statuses are: all, ongoing, completed.',
        ];
    }
}
