<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'equipment_id' => [
                'required',
                'exists:equipment,id',
            ],
            'work_item_id' => [
                'required',
                'exists:work_items,id',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'notes' => [
                'string',
                'required'
            ],
        ];
    }
}
