<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'project_id' => 'nullable|exists:projects,id',

            'from' => 'nullable|date',

            'to' => 'nullable|date|after_or_equal:from',

            'action' => 'nullable|string',

            'endpoint' => 'nullable|string',

            'method' => 'nullable|string',

            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
