<?php

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'equipment_id' => ['required', 'exists:equipment,id'],            'start_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['Breakdown', 'Preventive'])],
            'description' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_id.required' => 'The equipment_id field is required.',
            'equipment_id.exists' => 'The selected equipment does not exist.',
            'start_date.required' => 'The start_date field is required.',
            'start_date.date' => 'The start_date must be a valid date.',
            'type.required' => 'The type field is required.',
            'type.in' => 'Invalid maintenance type. Available values are: Breakdown, Preventive.',
        ];
    }
}
