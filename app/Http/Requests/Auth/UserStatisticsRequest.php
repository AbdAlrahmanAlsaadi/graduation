<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStatisticsRequest extends FormRequest
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

                    'overview',

                    'projects',

                    'activities',

                    'endpoints',

                    'comments',

                    'bookings',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'type.in' =>

            'Invalid type. Available types are: overview, projects, activities, endpoints, comments, bookings.',
        ];
    }
}
