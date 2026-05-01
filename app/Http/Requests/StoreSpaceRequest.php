<?php

namespace App\Http\Requests;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', 'string', Rule::in(Space::TYPE_OPTIONS)],
            'area' => ['required', 'numeric', 'min:0.1'],
            'finish_type' => ['required', 'string', Rule::in(Space::FINISH_TYPES)],
            'toilet_type' => [
                'nullable',
                'string',
                Rule::in(Space::TOILET_TYPES),
                Rule::requiredIf($this->isBathroomOrToilet($type)),
            ],
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
            $type = $this->input('type');

            if (! $this->supportsCeilingCeramic($type)) {
                if ($this->boolean('has_ceiling_ceramic') || $this->filled('ceiling_ceramic_area')) {
                    $validator->errors()->add(
                        'has_ceiling_ceramic',
                        'Ceiling ceramic is only allowed for kitchen, bathroom, toilet, or balcony.'
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

    private function isBathroomOrToilet(?string $type): bool
    {
        return in_array($type, ['bathroom', 'toilet'], true);
    }

    private function supportsCeilingCeramic(?string $type): bool
    {
        return in_array($type, Space::CEILING_CERAMIC_TYPES, true);
    }
}
