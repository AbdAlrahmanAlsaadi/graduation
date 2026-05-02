<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
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
        $projectManager = $this->getOrCreateUserWithRole('project_manager');
        $assistantEngineer = $this->getOrCreateUserWithRole('assistant');
        $owner = fake()->boolean(70)
            ? $this->getOrCreateUserWithRole('project_owner')
            : null;

        return [
            'name' => fake()->company() . ' Project',
            'project_manager_id' => $projectManager->id,
            'assistant_engineer_id' => $assistantEngineer->id,
            'owner_id' => $owner?->id,
            'location' => fake()->city(),
            'total_area' => fake()->randomFloat(2, 500, 10000),
            'height' => fake()->randomFloat(2, 2.5, 15),
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
