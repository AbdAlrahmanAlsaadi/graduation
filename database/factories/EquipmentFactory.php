<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
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
            'project_id' => fake()->boolean(70)
                ? $this->getOrCreateProject()->id
                : null,

            'name' => $type . ' ' . fake()->numberBetween(100, 999),

            'type' => $type,

            'identifier_no' => $this->generateIdentifierNo(),

            'status' => fake()->randomElement([
                'Available',
                'Maintenance',
                'Booked',
            ]),
        ];
    }

    private function getOrCreateProject(): Project
    {
        $project = Project::query()->inRandomOrder()->first();

        if ($project) {
            return $project;
        }

        return Project::factory()->create();
    }

    private function generateIdentifierNo(): string
    {
        return 'EQ-' . fake()->unique()->numerify('######');
    }
}
