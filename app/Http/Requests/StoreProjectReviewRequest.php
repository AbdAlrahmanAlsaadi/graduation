<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'The rating field is required.',
            'rating.integer' => 'The rating must be an integer.',
            'rating.min' => 'The minimum rating is 1.',
            'rating.max' => 'The maximum rating is 5.',

            'comment.string' => 'The comment must be a string.',
            'comment.max' => 'The comment may not be greater than 1000 characters.',
        ];
    }
}
