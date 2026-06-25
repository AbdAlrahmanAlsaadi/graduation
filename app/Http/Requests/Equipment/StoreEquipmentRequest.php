<?php

namespace App\Http\Requests\Equipment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['Available', 'Maintenance', 'Booked'])],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.exists' => 'The selected project does not exist.',
            'name.required' => 'The equipment name field is required.',
            'type.required' => 'The equipment type field is required.',
            'status.in' => 'Invalid status. Available values are: Available, Maintenance, Booked.',
        ];
    }}
