<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetProjectWeatherByDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Date is required.',
            'date.date' => 'Invalid date format.',
        ];
    }
}
