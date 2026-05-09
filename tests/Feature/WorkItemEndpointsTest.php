<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkItemEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_work_item_with_details(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $payload = [
            'name' => 'Custom Paint Work',
            'quality_level' => WorkItem::QUALITY_LEVEL_GOOD,
            'duration_days' => 5,
            'details' => [
                [
                    'key' => 'tile_length',
                    'value' => '30',
                    'unit' => 'cm',
                ],
                [
                    'key' => 'tile_width',
                    'value' => '30',
                    'unit' => 'cm',
                ],
            ],
        ];

        $response = $this->postJson("/api/projects/{$project->id}/work-items", $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.is_custom', true)
            ->assertJsonPath('data.details.0.key', 'tile_length');

        $workItemId = $response->json('data.id');

        $this->assertDatabaseHas('work_item_details', [
            'work_item_id' => $workItemId,
            'key' => 'tile_length',
        ]);
    }

    public function test_update_replaces_details(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);
        $workItem = WorkItem::query()->create([
            'project_id' => $project->id,
            'name' => 'Custom Tile Work',
            'quality_level' => WorkItem::QUALITY_LEVEL_GOOD,
            'duration_days' => 3,
            'sort_order' => 1,
            'is_default' => false,
            'is_active' => true,
            'is_custom' => true,
        ]);

        WorkItemDetail::query()->create([
            'work_item_id' => $workItem->id,
            'key' => 'tile_length',
            'value' => '30',
            'unit' => 'cm',
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $payload = [
            'details' => [
                [
                    'key' => 'tile_width',
                    'value' => '40',
                    'unit' => 'cm',
                ],
            ],
        ];

        $this->putJson("/api/projects/{$project->id}/work-items/{$workItem->id}", $payload)
            ->assertStatus(200)
            ->assertJsonPath('data.details.0.key', 'tile_width');

        $this->assertDatabaseMissing('work_item_details', [
            'work_item_id' => $workItem->id,
            'key' => 'tile_length',
        ]);

        $this->assertDatabaseHas('work_item_details', [
            'work_item_id' => $workItem->id,
            'key' => 'tile_width',
        ]);
    }

    public function test_list_work_items_includes_details(): void
    {
        $user = $this->createUserWithRole('project_manager');
        $project = $this->createProjectFor($user);
        $workItem = WorkItem::query()->create([
            'project_id' => $project->id,
            'name' => 'Custom Tile Work',
            'quality_level' => WorkItem::QUALITY_LEVEL_GOOD,
            'duration_days' => 3,
            'sort_order' => 1,
            'is_default' => false,
            'is_active' => true,
            'is_custom' => true,
        ]);

        WorkItemDetail::query()->create([
            'work_item_id' => $workItem->id,
            'key' => 'tile_length',
            'value' => '30',
            'unit' => 'cm',
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->getJson("/api/projects/{$project->id}/work-items")
            ->assertStatus(200)
            ->assertJsonPath('data.0.details.0.key', 'tile_length');
    }

    private function createProjectFor(User $user): Project
    {
        return Project::query()->create([
            'name' => 'Work Items Test Project',
            'location' => 'Giza',
            'latitude' => '30.0',
            'longitude' => '31.1',
            'apartment_area' => 200.0,
            'height' => 3.0,
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
