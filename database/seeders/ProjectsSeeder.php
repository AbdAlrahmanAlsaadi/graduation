<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::factory()
            ->state(fn () => [
                'latitude' => (string) fake()->latitude(),
                'longitude' => (string) fake()->longitude(),
            ])
            ->count(5)
            ->create();

        foreach($projects as $project){
            if ($project) {
                $projectManager = User::role('project_manager')->first();
                $assistant = User::role('assistant')->first();
            
                if ($projectManager) {
                    $project->assignEngineer($projectManager, 'project_manager', now());
                }
            
                if ($assistant) {
                    $project->assignEngineer($assistant, 'assistant', now());
                }
            }
        }

        if ($projects->isNotEmpty()) {
            $this->call([
                SpacesSeeder::class,
                WorkItemsSeeder::class,
            ]);
        }
    }
}
