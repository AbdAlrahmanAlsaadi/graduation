<?php

namespace App\Http\Requests\Misc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'owner_id' => ['nullable', 'exists:users,id'],

            'contract_no' => ['required', 'string', 'max:255', 'unique:contracts,contract_no'],
            'title' => ['required', 'string', 'max:255'],

            'contract_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'contract_value' => ['required', 'numeric', 'min:0'],

            'currency' => ['required', Rule::in(['USD', 'SYP'])],

            'status' => ['nullable', Rule::in(['Draft', 'Active', 'Completed', 'Cancelled'])],

            'description' => ['required', 'string'],

            'company_signature' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'owner_signature' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
}
