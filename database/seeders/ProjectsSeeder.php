<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Space;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::factory()
            ->count(fake()->numberBetween(5, 10))
            ->create();

        foreach ($projects as $project) {
            Space::factory()
                ->count(fake()->numberBetween(3, 5))
                ->for($project)
                ->create();
        }
    }
}
