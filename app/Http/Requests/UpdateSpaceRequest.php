<?php

namespace App\Http\Requests;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Space::rules(true);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $space = $this->route('space');
            $type = $this->resolvedType();
            $ceilingFinishType = $this->resolvedCeilingFinishType();
            $toiletType = $this->has('toilet_type')
                ? $this->input('toilet_type')
                : ($space instanceof Space ? $space->toilet_type : null);
            $ceilingCeramicArea = $this->has('ceiling_ceramic_area')
                ? $this->input('ceiling_ceramic_area')
                : ($space instanceof Space ? $space->ceiling_ceramic_area : null);
            $isBalconyFloorTiled = $this->has('is_balcony_floor_tiled')
                ? $this->boolean('is_balcony_floor_tiled')
                : ($space instanceof Space ? (bool) $space->is_balcony_floor_tiled : false);

            if ($this->isBathroomOrToilet($type)) {
                if (! $toiletType || $toiletType === 'none') {
                    $validator->errors()->add('toilet_type', 'Toilet type is required for bathroom or toilet.');
                }
            } elseif ($this->filled('toilet_type') && $toiletType !== 'none') {
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

            if ($ceilingFinishType === 'ceramic' && ! $ceilingCeramicArea) {
                $validator->errors()->add(
                    'ceiling_ceramic_area',
                    'Ceiling ceramic area is required when ceiling finish is ceramic.'
                );
            }

            if ($ceilingFinishType !== 'ceramic' && $ceilingCeramicArea) {
                $validator->errors()->add(
                    'ceiling_ceramic_area',
                    'Ceiling ceramic area is only allowed when ceiling finish is ceramic.'
                );
            }

            if ($isBalconyFloorTiled && $type !== Space::TYPE_BALCONY) {
                $validator->errors()->add(
                    'is_balcony_floor_tiled',
                    'Balcony floor tiled is only valid for balcony spaces.'
                );
            }
        });
    }

    private function resolvedType(): ?string
    {
        if ($this->has('type')) {
            return $this->input('type');
        }

        $space = $this->route('space');

        return $space instanceof Space ? $space->type : null;
    }

    private function resolvedCeilingFinishType(): ?string
    {
        if ($this->has('ceiling_finish_type')) {
            return $this->input('ceiling_finish_type');
        }

        $space = $this->route('space');

        return $space instanceof Space ? $space->ceiling_finish_type : null;
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
