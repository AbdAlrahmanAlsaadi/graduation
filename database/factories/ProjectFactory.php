<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = $this->faker ?? Faker::create();
        $projectManager = $this->getOrCreateUserWithRole('project_manager');
        $assistantEngineer = $this->getOrCreateUserWithRole('assistant');
        $owner = $faker->boolean(70)
            ? $this->getOrCreateUserWithRole('project_owner')
            : null;

        return [
            'name' => $faker->company() . ' Project',
            'project_manager_id' => $projectManager->id,
            'assistant_engineer_id' => $assistantEngineer->id,
            'owner_id' => $owner?->id,
            'location' => $faker->city(),
            'latitude' => (string) $faker->latitude(),
            'longitude' => (string) $faker->longitude(),
            'apartment_area' => $faker->randomFloat(2, 500, 10000),
            'height' => $faker->randomFloat(2, 2.5, 15),
            'status' => Project::STATUS_PLANNED,
            'created_by' => $projectManager->id,
            'updated_by' => $projectManager->id,
        ];
    }

    private function getOrCreateUserWithRole(string $roleName): User
    {
        $user = User::role($roleName)->inRandomOrder()->first();
        if ($user) {
            return $user;
        }

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }
}
