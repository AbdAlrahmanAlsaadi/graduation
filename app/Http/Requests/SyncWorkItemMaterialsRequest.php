<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncWorkItemMaterialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'materials' => ['required', 'array'],
            'materials.*.material_id' => ['required', 'integer', 'exists:materials,id', 'distinct'],
            'materials.*.sort_order' => ['required', 'integer', 'min:0'],
            'materials.*.is_required' => ['required', 'boolean'],
        ];
    }
}
