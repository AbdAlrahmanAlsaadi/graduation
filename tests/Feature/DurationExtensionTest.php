<?php

namespace Tests\Feature;

use App\Models\DurationExtensionRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DurationExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('send')->zeroOrMoreTimes();
        });
    }

    public function test_user_can_fetch_all_duration_extension_requests(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        DurationExtensionRequest::create([
            'project_id'              => $project->id,
            'work_item_id'            => $workItem->id,
            'requested_by'            => $assistant->id,
            'status'                  => 'pending',
            'requested_duration_days' => 5,
            'reason'                  => 'Need additional time due to weather conditions',
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $response = $this->getJson('/api/duration-extensions');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'data');
    }

    private function createProjectFor(User $engineer, ?User $assistant = null): Project
    {
        return Project::create([
            'name'                   => 'Extension Test Project',
            'location'               => 'Test City',
            'latitude'               => '30.0',
            'longitude'              => '31.1',
            'apartment_area'         => 200.0,
            'height'                 => 3.0,
            'status'                 => Project::STATUS_ONGOING,
            'project_manager_id'     => $engineer->id,
            'assistant_engineer_id'  => $assistant?->id,
            'created_by'             => $engineer->id,
            'updated_by'             => $engineer->id,
        ]);
    }

    private function createUserWithRole(string $roleName): User
    {
        Role::firstOrCreate([
            'name'       => $roleName,
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function createWorkItem(Project $project): WorkItem
    {
        return WorkItem::create([
            'project_id'    => $project->id,
            'name'          => 'دهان',
            'quality_level' => WorkItem::QUALITY_LEVEL_GOOD,
            'duration_days' => 5,
            'sort_order'    => 1,
            'is_default'    => false,
            'is_active'     => true,
            'is_custom'     => false,
        ]);
    }
}
