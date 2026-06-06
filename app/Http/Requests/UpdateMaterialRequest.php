<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $materialId = $this->route('material')?->id ?? $this->route('material');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('materials', 'name')->ignore($materialId)],
            'unit' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
