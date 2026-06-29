<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'name'  => 'required|string|max:100',
            'image'      => 'required|image|mimes:jpg,jpeg,png|max:20480',
        ];
    }
}
