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
        $type = fake()->randomElement(Space::TYPE_OPTIONS);
        $ceilingFinishOptions = Space::FINISH_TYPES;

        if (! in_array($type, Space::CEILING_CERAMIC_TYPES, true)) {
            $ceilingFinishOptions = array_values(array_diff($ceilingFinishOptions, ['ceramic']));
        }

        $ceilingFinishType = fake()->randomElement($ceilingFinishOptions);
        $toiletType = in_array($type, [Space::TYPE_BATHROOM, Space::TYPE_TOILET], true)
            ? fake()->randomElement(array_values(array_diff(Space::TOILET_TYPES, ['none'])))
            : 'none';
        $isBalconyFloorTiled = $type === Space::TYPE_BALCONY ? fake()->boolean(35) : false;

        return [
            'project_id' => Project::factory(),
            'type' => $type,
            'wall_area' => fake()->randomFloat(2, 8, 220),
            'floor_area' => fake()->randomFloat(2, 6, 180),
            'wall_finish_type' => fake()->randomElement(Space::FINISH_TYPES),
            'ceiling_finish_type' => $ceilingFinishType,
            'toilet_type' => $toiletType,
            'ceiling_ceramic_area' => $ceilingFinishType === 'ceramic'
                ? fake()->randomFloat(2, 2, 40)
                : null,
            'is_balcony_floor_tiled' => $isBalconyFloorTiled,
        ];
    }
}
