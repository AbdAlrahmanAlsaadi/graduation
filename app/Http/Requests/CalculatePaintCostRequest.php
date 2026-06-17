<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePaintCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'price_per_meter' => [
                'required',
                'numeric',
                'min:0',
            ],

            'beams_count' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}
