<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseEquipmentMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.required' => 'The end_date field is required.',
            'end_date.date' => 'The end_date must be a valid date.',
        ];
    }
}
