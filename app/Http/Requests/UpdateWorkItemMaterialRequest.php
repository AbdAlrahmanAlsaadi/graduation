<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkItemMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }
}
