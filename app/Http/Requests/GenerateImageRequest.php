<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'project_image_id' => [
                'required',
                'exists:project_images,id',
            ],

            'reference_images' => [
                'required',
                'array',
                'min:1',
            ],

            'reference_images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],

            'prompt' => [
                'required',
                'string',
                'max:1000',
            ],

        ];
    }
}
