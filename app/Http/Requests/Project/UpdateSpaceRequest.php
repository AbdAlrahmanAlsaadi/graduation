<?php

namespace App\Http\Requests\Project;

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
            $ceilingCeramicArea = $this->has('ceiling_area')
                ? $this->input('ceiling_area')
                : ($space instanceof Space ? $space->ceiling_area : null);
            $isshedFloorTiled = $this->has('is_shed_floor_tiled')
                ? $this->boolean('is_shed_floor_tiled')
                : ($space instanceof Space ? (bool) $space->is_shed_floor_tiled : false);

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

            if (! $this->supportsCeilingCeramic($type) && $this->filled('ceiling_area')) {
                $validator->errors()->add(
                    'ceiling_area',
                    'Ceiling ceramic area is only allowed for kitchen, bathroom, toilet, or shed.'
                );
            }

            if ($ceilingFinishType === 'ceramic' && ! $this->supportsCeilingCeramic($type)) {
                $validator->errors()->add(
                    'ceiling_finish_type',
                    'Ceiling ceramic finish is only allowed for kitchen, bathroom, toilet, or shed.'
                );
            }

            if ($ceilingFinishType === 'ceramic' && ! $ceilingCeramicArea) {
                $validator->errors()->add(
                    'ceiling_area',
                    'Ceiling ceramic area is required when ceiling finish is ceramic.'
                );
            }

            if ($ceilingFinishType !== 'ceramic' && $ceilingCeramicArea) {
                $validator->errors()->add(
                    'ceiling_area',
                    'Ceiling ceramic area is only allowed when ceiling finish is ceramic.'
                );
            }

            if ($isshedFloorTiled && $type !== Space::TYPE_SHED) {
                $validator->errors()->add(
                    'is_shed_floor_tiled',
                    'shed floor tiled is only valid for shed spaces.'
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
