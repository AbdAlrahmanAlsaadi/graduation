<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectReviewStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    'all',
                    'project',
                    'average',
                    'ranking',
                ]),
            ],

            'project_id' => [
                'required_if:type,project',
                'exists:projects,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'The type field is required.',
            'type.in' => 'Invalid type. Allowed types are: all, project, average, ranking.',

            'project_id.required_if' => 'The project_id field is required when type is project.',
            'project_id.exists' => 'The selected project does not exist.',
        ];
    }}
