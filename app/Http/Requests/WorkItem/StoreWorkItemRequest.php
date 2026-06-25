<?php

namespace App\Http\Requests\WorkItem;

use App\Models\WorkItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = WorkItem::rules();

        $rules['details'] = ['nullable', 'array'];
        $rules['details.*.key'] = ['required_with:details', 'string'];
        $rules['details.*.value'] = ['required_with:details'];
        $rules['details.*.unit'] = ['nullable', 'string'];

        return $rules;
    }
}
