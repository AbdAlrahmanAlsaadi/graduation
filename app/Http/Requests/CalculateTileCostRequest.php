<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateTileCostRequest extends FormRequest
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
                'min:0'
            ],

            'skirting_factor' => [
                'required',
                'numeric',
                'min:0'
            ],

            'sink_installation_cost' => [
                'required',
                'numeric',
                'min:0'
            ],
        ];
    }}
