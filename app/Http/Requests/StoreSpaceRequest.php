<?php

namespace App\Http\Requests;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Space::rules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $ceilingFinishType = $this->input('ceiling_finish_type');

            if ($this->isBathroomOrToilet($type)) {
                if (! $this->filled('toilet_type') || $this->input('toilet_type') === 'none') {
                    $validator->errors()->add(
                        'toilet_type',
                        'Toilet type is required for bathroom or toilet.'
                    );
                }
            } elseif ($this->filled('toilet_type') && $this->input('toilet_type') !== 'none') {
                $validator->errors()->add(
                    'toilet_type',
                    'Toilet type is only allowed for bathroom or toilet.'
                );
            }

            if (! $this->supportsCeilingCeramic($type) && $this->filled('ceiling_ceramic_area')) {
                $validator->errors()->add(
                    'ceiling_ceramic_area',
                    'Ceiling ceramic area is only allowed for kitchen, bathroom, toilet, or balcony.'
                );
            }

            if ($ceilingFinishType === 'ceramic' && ! $this->supportsCeilingCeramic($type)) {
                $validator->errors()->add(
                    'ceiling_finish_type',
                    'Ceiling ceramic finish is only allowed for kitchen, bathroom, toilet, or balcony.'
                );
            }

            if ($ceilingFinishType === 'ceramic' && ! $this->filled('ceiling_ceramic_area')) {
                $validator->errors()->add(
                    'ceiling_ceramic_area',
                    'Ceiling ceramic area is required when ceiling finish is ceramic.'
                );
            }

            if ($ceilingFinishType !== 'ceramic' && $this->filled('ceiling_ceramic_area')) {
                $validator->errors()->add(
                    'ceiling_ceramic_area',
                    'Ceiling ceramic area is only allowed when ceiling finish is ceramic.'
                );
            }

            if ($this->boolean('is_balcony_floor_tiled') && $type !== Space::TYPE_BALCONY) {
                $validator->errors()->add(
                    'is_balcony_floor_tiled',
                    'Balcony floor tiled is only valid for balcony spaces.'
                );
            }
        });
    }

    private function isBathroomOrToilet(?string $type): bool
    {
        return in_array($type, [Space::TYPE_BATHROOM, Space::TYPE_TOILET], true);
    }

    private function supportsCeilingCeramic(?string $type): bool
    {
        return in_array($type, Space::CEILING_CERAMIC_TYPES, true);
    }
}
