<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project_endpoint(): void
    {
        $admin = $this->createUserWithRole('company_admin');
        $projectManager = User::factory()->create();
        $assistant = User::factory()->create();

        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $payload = [
            'name' => 'Test Project',
            'location' => 'Cairo',
            'latitude' => '30.1',
            'longitude' => '31.2',
            'apartment_area' => 120.5,
            'height' => 3.1,
            'status' => Project::STATUS_PLANNED,
            'project_manager_id' => $projectManager->id,
            'assistant_engineer_id' => $assistant->id,
        ];

        $response = $this->postJson('/api/projects', $payload);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Project')
            ->assertJsonPath('data.status', Project::STATUS_PLANNED);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'status' => Project::STATUS_PLANNED,
        ]);
    }

    public function test_start_project_sets_status_and_started_at(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = Project::query()->create([
            'name' => 'Start Project',
            'location' => 'Giza',
            'latitude' => '30.0',
            'longitude' => '31.1',
            'apartment_area' => 200.0,
            'height' => 3.0,
            'status' => Project::STATUS_PLANNED,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->postJson("/api/projects/{$project->id}/start");

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.status', Project::STATUS_ONGOING);

        $this->assertNotNull($response->json('data.started_at'));

        $project->refresh();
        $this->assertSame(Project::STATUS_ONGOING, $project->status);
        $this->assertNotNull($project->started_at);
    }

    public function test_complete_project_requires_started_and_sets_completed_at(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = Project::query()->create([
            'name' => 'Complete Project',
            'location' => 'Giza',
            'latitude' => '30.0',
            'longitude' => '31.1',
            'apartment_area' => 200.0,
            'height' => 3.0,
            'status' => Project::STATUS_PLANNED,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson("/api/projects/{$project->id}/complete")
            ->assertStatus(400);

        $this->postJson("/api/projects/{$project->id}/start")
            ->assertStatus(200);

        $response = $this->postJson("/api/projects/{$project->id}/complete");

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.status', Project::STATUS_COMPLETED);

        $this->assertNotNull($response->json('data.completed_at'));

        $project->refresh();
        $this->assertSame(Project::STATUS_COMPLETED, $project->status);
        $this->assertNotNull($project->completed_at);
    }

    private function createUserWithRole(string $roleName): User
    {
        Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }
}
