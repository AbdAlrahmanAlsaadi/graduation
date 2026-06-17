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
                if($type == 'bathroom') {
                    
                }
                else if (! $this->filled('toilet_type') || $this->input('toilet_type') === 'none') {
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

            if ($this->boolean('is_shed_floor_tiled') && $type !== Space::TYPE_SHED) {
                $validator->errors()->add(
                    'is_shed_floor_tiled',
                    'shed floor tiled is only valid for shed spaces.'
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
