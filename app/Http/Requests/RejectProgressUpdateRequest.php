<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy in controller
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
