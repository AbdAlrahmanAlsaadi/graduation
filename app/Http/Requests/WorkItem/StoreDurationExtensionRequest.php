<?php

namespace App\Http\Requests\WorkItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreDurationExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy in controller
    }

    public function rules(): array
    {
        return [
            'requested_duration_days' => ['required', 'integer', 'min:1'],
            'reason'                  => ['required', 'string', 'max:2000'],
        ];
    }
}
