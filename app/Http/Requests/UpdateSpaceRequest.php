<?php

namespace App\Http\Requests;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(Space::TYPE_OPTIONS)],
            'area' => ['sometimes', 'numeric', 'min:0.1'],
            'finish_type' => ['sometimes', 'string', Rule::in(Space::FINISH_TYPES)],
            'toilet_type' => ['sometimes', 'nullable', 'string', Rule::in(Space::TOILET_TYPES)],
            'has_ceiling_ceramic' => ['sometimes', 'boolean'],
            'ceiling_ceramic_area' => [
                'nullable',
                'numeric',
                'min:0.1',
                Rule::requiredIf($this->boolean('has_ceiling_ceramic')),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->resolvedType();

            if ($this->has('type') && $this->isBathroomOrToilet($type) && ! $this->filled('toilet_type')) {
                $validator->errors()->add('toilet_type', 'Toilet type is required for bathroom or toilet.');
            }

            if (! $this->supportsCeilingCeramic($type)) {
                if ($this->has('has_ceiling_ceramic') && $this->boolean('has_ceiling_ceramic')) {
                    $validator->errors()->add(
                        'has_ceiling_ceramic',
                        'Ceiling ceramic is only allowed for kitchen, bathroom, toilet, or balcony.'
                    );
                }

                if ($this->filled('ceiling_ceramic_area')) {
                    $validator->errors()->add(
                        'ceiling_ceramic_area',
                        'Ceiling ceramic area is only allowed for kitchen, bathroom, toilet, or balcony.'
                    );
                }
            }

            if (! $this->isBathroomOrToilet($type) && $this->filled('toilet_type')) {
                if ($this->input('toilet_type') !== 'none') {
                    $validator->errors()->add(
                        'toilet_type',
                        'Toilet type is only allowed for bathroom or toilet.'
                    );
                }
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

    private function isBathroomOrToilet(?string $type): bool
    {
        return in_array($type, ['bathroom', 'toilet'], true);
    }

    private function supportsCeilingCeramic(?string $type): bool
    {
        return in_array($type, Space::CEILING_CERAMIC_TYPES, true);
    }
}
