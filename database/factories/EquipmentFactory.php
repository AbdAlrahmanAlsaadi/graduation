<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        $types = [
            'حفارة',
            'رافعة',
            'خلاطة خرسانة',
            'جرافة',
            'رافعة شوكية',
            'مولدة كهرباء',
            'ضاغط هواء',
        ];

        $type = fake()->randomElement($types);

        return [

            'name' => $type . ' ' . fake()->numberBetween(100, 999),

            'type' => $type,

            'identifier_no' => $this->generateIdentifierNo(),

            'status' => fake()->randomElement([
                'Available',
                'Maintenance',
            ]),
        ];
    }

    private function generateIdentifierNo(): string
    {
        return 'EQ-' . fake()->unique()->numerify('######');
    }
}
