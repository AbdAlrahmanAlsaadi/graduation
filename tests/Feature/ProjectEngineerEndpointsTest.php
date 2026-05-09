<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectEngineer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectEngineerEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_engineer_returns_201_and_payload(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);
        $engineer = User::factory()->create();

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $payload = [
            'user_id' => $engineer->id,
            'role' => 'assistant_engineer',
            'assigned_at' => now()->toDateTimeString(),
        ];

        $this->postJson("/api/projects/{$project->id}/engineers", $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.user.id', $engineer->id)
            ->assertJsonPath('data.role', 'assistant_engineer');
    }

    public function test_cannot_duplicate_same_user_role(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);
        $engineer = User::factory()->create();

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $payload = [
            'user_id' => $engineer->id,
            'role' => 'assistant_engineer',
        ];

        $this->postJson("/api/projects/{$project->id}/engineers", $payload)
            ->assertStatus(201);

        $this->postJson("/api/projects/{$project->id}/engineers", $payload)
            ->assertStatus(201);

        $this->assertDatabaseCount('project_engineers', 1);
    }

    public function test_list_engineers_returns_assigned(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);
        $engineer = User::factory()->create();

        ProjectEngineer::query()->create([
            'project_id' => $project->id,
            'user_id' => $engineer->id,
            'role' => 'project_manager',
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->getJson("/api/projects/{$project->id}/engineers")
            ->assertStatus(200)
            ->assertJsonPath('data.0.user.id', $engineer->id);
    }

    public function test_remove_assignment_returns_204(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);
        $engineer = User::factory()->create();

        $assignment = ProjectEngineer::query()->create([
            'project_id' => $project->id,
            'user_id' => $engineer->id,
            'role' => 'assistant_engineer',
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->deleteJson("/api/projects/{$project->id}/engineers/{$assignment->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('project_engineers', [
            'id' => $assignment->id,
        ]);
    }

    private function createProjectFor(User $user): Project
    {
        return Project::query()->create([
            'name' => 'Engineer Test Project',
            'location' => 'Riyadh',
            'latitude' => '24.7',
            'longitude' => '46.6',
            'apartment_area' => 150.0,
            'height' => 3.2,
            'status' => Project::STATUS_PLANNED,
            'created_by' => $user->id,
            'updated_by' => $user->id,
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
