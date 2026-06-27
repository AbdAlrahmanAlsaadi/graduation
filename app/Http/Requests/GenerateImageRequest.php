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

            'room_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240'
            ],

            'reference_images' => [
                'required',
                'array'
            ],

            'reference_images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240'
            ],

            'prompt' => [
                'required',
                'string',
                'max:500'
            ],

        ];
    }
}
