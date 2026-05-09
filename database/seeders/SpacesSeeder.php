<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Space;
use Illuminate\Database\Seeder;

class SpacesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Project::query()->exists() || Space::query()->exists()) {
            return;
        }

        Project::query()->each(function (Project $project) {
            Space::factory()
                ->count(fake()->numberBetween(3, 5))
                ->for($project)
                ->create();
        });
    }
}
