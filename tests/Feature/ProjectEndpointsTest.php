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
