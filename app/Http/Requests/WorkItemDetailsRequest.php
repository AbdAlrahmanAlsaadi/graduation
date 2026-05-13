<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkItemDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
            $workItem = $this->route('workItem');

        $template = config("work_item_templates.{$workItem->name}");

        if (!$template) {
            return [];
        }

        $rules = [];

        foreach ($template as $key => $meta) {
            $rules[$key] = $meta['rule'];
        }

        return $rules;
    }
}
