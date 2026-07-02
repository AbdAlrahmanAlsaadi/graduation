<?php

namespace App\Http\Requests\WorkItem;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkItemProgressRequest extends FormRequest
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

        $rules = [];

        if ($template) {
            foreach ($template as $key => $meta) {
                $rules[$key] = $meta['progress_rule'] ?? 'sometimes|numeric|min:0';
            }
        }

        // Always allow photos
        $rules['photos']   = 'sometimes|array';
        $rules['photos.*'] = 'image|mimes:jpg,jpeg,png,webp|max:5120';

        return $rules;
    }
    /**
     * Reject any keys not defined in the template (photos always allowed).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $workItem = $this->route('workItem');
            $template = config("work_item_templates.{$workItem->name}");

            if (!$template) {
                return;
            }

            $allowedKeys = array_merge(array_keys($template), ['photos']);
            $extraKeys = array_diff(array_keys($this->all()), $allowedKeys);

            foreach ($extraKeys as $key) {
                $validator->errors()->add($key, "The field '{$key}' is not allowed for this work item.");
            }
        });
    }
}
