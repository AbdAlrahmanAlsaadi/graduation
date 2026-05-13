<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpaceEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_bathroom_space_requires_toilet_type(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $payload = [
            'type' => Space::TYPE_BATHROOM,
            'wall_area' => 40.5,
            'wall_finish_type' => 'ceramic',
            'ceiling_finish_type' => 'ceramic',
            'ceiling_area' => 10.5,
        ];

        $this->postJson("/api/projects/{$project->id}/spaces", $payload)
            ->assertStatus(422);

        $payload['toilet_type'] = 'western';

        $this->postJson("/api/projects/{$project->id}/spaces", $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.type', Space::TYPE_BATHROOM);
    }

    public function test_create_shed_space_allows_floor_tiled_flag(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $payload = [
            'type' => Space::TYPE_SHED,
            'wall_area' => 18.5,
            'wall_finish_type' => 'paint',
            'ceiling_finish_type' => 'ceramic',
            'ceiling_area' => 6.3,
            'toilet_type' => 'none',
            'is_shed_floor_tiled' => true,
        ];

        $this->postJson("/api/projects/{$project->id}/spaces", $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.is_shed_floor_tiled', true);
    }

    private function createProjectFor(User $user): Project
    {
        return Project::query()->create([
            'name' => 'Spaces Test Project',
            'location' => 'Alexandria',
            'latitude' => '31.2',
            'longitude' => '29.9',
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
