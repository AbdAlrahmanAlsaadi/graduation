<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkItem>
 */
class WorkItemFactory extends Factory
{
    protected $model = WorkItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'name' => fake()->words(3, true),
            'quality_level' => fake()->randomElement(WorkItem::QUALITY_LEVELS),
            'duration_days' => fake()->boolean(60) ? fake()->numberBetween(1, 30) : null,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_default' => false,
            'is_active' => true,
            'status' => Project::STATUS_PLANNED,
            'is_custom' => fake()->boolean(40),
        ];
    }
}
