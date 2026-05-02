<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Space>
 */
class SpaceFactory extends Factory
{
    protected $model = Space::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasCeilingCeramic = fake()->boolean(25);

        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(Space::TYPE_OPTIONS),
            'area' => fake()->randomFloat(2, 6, 180),
            'finish_type' => fake()->randomElement(Space::FINISH_TYPES),
            'toilet_type' => fake()->randomElement(Space::TOILET_TYPES),
            'has_ceiling_ceramic' => $hasCeilingCeramic,
            'ceiling_ceramic_area' => $hasCeilingCeramic
                ? fake()->randomFloat(2, 2, 40)
                : null,
        ];
    }
}
