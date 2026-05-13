<?php

namespace App\Http\Requests;

use App\Models\WorkItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = WorkItem::rules(true);
        return $rules;
    }
}
