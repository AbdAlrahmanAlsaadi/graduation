<?php

namespace Tests\Feature;

use App\Models\ProgressUpdateRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemDetail;
use App\Services\NotificationService;
use App\Services\WorkItemProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgressUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock NotificationService globally to avoid Firebase calls in all tests.
        // Individual tests can re-mock with specific expectations.
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('send')->zeroOrMoreTimes();
        });
    }

    /* ================================================================
       SUBMIT TESTS
       ================================================================ */

    public function test_assistant_can_submit_progress_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        Sanctum::actingAs($assistant, ['*'], 'sanctum');

        $payload = [
            'completed_doors' => 3,
            'total_doors'     => 5,
        ];

        $response = $this->postJson(
            "/api/projects/{$project->id}/work-items/{$workItem->id}/progress-requests",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.type', 'progress')
            ->assertJsonPath('data.payload.completed_doors', 3);

        // Verify no actual progress was applied
        $this->assertDatabaseMissing('work_item_details', [
            'work_item_id' => $workItem->id,
            'key'          => 'completed_doors',
        ]);

        // Verify request was created
        $this->assertDatabaseHas('progress_update_requests', [
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
        ]);
    }

    public function test_assistant_can_submit_room_progress_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $space = \App\Models\Space::create([
            'project_id'         => $project->id,
            'type'               => 'room',
            'wall_area'          => 40.00,
            'wall_finish_type'   => 'paint',
            'ceiling_finish_type'=> 'gypsum',
            'ceiling_area'       => 20.00,
        ]);

        Sanctum::actingAs($assistant, ['*'], 'sanctum');

        $response = $this->postJson(
            "/api/projects/{$project->id}/work-items/{$workItem->id}/progress-requests/room/{$space->id}",
            ['completed' => true]
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.type', 'room')
            ->assertJsonPath('data.payload.space_id', $space->id)
            ->assertJsonPath('data.payload.completed', true);
    }

    public function test_engineer_cannot_submit_progress_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $response = $this->postJson(
            "/api/projects/{$project->id}/work-items/{$workItem->id}/progress-requests",
            ['completed_doors' => 3, 'total_doors' => 5]
        );

        $response->assertStatus(403);
    }

    public function test_submit_sends_notification_to_engineer(): void
    {
        // Re-mock with specific expectation
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $this->mock(NotificationService::class, function ($mock) use ($engineer) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (User $user, array $data) use ($engineer) {
                    return $user->id === $engineer->id
                        && $data['type'] === 'progress_update_submitted';
                });
        });

        Sanctum::actingAs($assistant, ['*'], 'sanctum');

        $this->postJson(
            "/api/projects/{$project->id}/work-items/{$workItem->id}/progress-requests",
            ['completed_doors' => 3, 'total_doors' => 5]
        )->assertStatus(201);
    }

    /* ================================================================
       APPROVE TESTS
       ================================================================ */

    public function test_engineer_can_approve_pending_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3, 'total_doors' => 5],
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $response = $this->postJson("/api/progress-requests/{$progressRequest->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $progressRequest->refresh();
        $this->assertSame('approved', $progressRequest->status);
        $this->assertSame($engineer->id, $progressRequest->reviewed_by);
        $this->assertNotNull($progressRequest->reviewed_at);

        // Verify actual progress was applied
        $this->assertDatabaseHas('work_item_details', [
            'work_item_id' => $workItem->id,
            'key'          => 'completed_doors',
            'value'        => '3',
        ]);
    }

    public function test_engineer_can_approve_room_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        // Create a space with all required fields
        $space = \App\Models\Space::create([
            'project_id'         => $project->id,
            'type'               => 'room',
            'wall_area'          => 40.00,
            'wall_finish_type'   => 'paint',
            'ceiling_finish_type'=> 'gypsum',
            'ceiling_area'       => 20.00,
        ]);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'room',
            'payload'      => [
                'space_id'  => $space->id,
                'completed' => true,
            ],
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $response = $this->postJson("/api/progress-requests/{$progressRequest->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_approve_sends_notification_to_requester(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3, 'total_doors' => 5],
        ]);

        $this->mock(NotificationService::class, function ($mock) use ($assistant) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (User $user, array $data) use ($assistant) {
                    return $user->id === $assistant->id
                        && $data['type'] === 'progress_update_approved';
                });
        });

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $this->postJson("/api/progress-requests/{$progressRequest->id}/approve")
            ->assertStatus(200);
    }

    public function test_cannot_approve_already_approved_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'reviewed_by'  => $engineer->id,
            'status'       => 'approved',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3],
            'reviewed_at'  => now(),
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $this->postJson("/api/progress-requests/{$progressRequest->id}/approve")
            ->assertStatus(500);
    }

    public function test_cannot_approve_rejected_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'reviewed_by'  => $engineer->id,
            'status'       => 'rejected',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3],
            'reviewed_at'  => now(),
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $this->postJson("/api/progress-requests/{$progressRequest->id}/approve")
            ->assertStatus(500);
    }

    /* ================================================================
       REJECT TESTS
       ================================================================ */

    public function test_engineer_can_reject_pending_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3, 'total_doors' => 5],
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $response = $this->postJson("/api/progress-requests/{$progressRequest->id}/reject", [
            'comment' => 'Photos are unclear, please resubmit.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.comment', 'Photos are unclear, please resubmit.');

        $progressRequest->refresh();
        $this->assertSame('rejected', $progressRequest->status);
        $this->assertSame($engineer->id, $progressRequest->reviewed_by);
        $this->assertNotNull($progressRequest->reviewed_at);

        // Verify no actual progress was applied
        $this->assertDatabaseMissing('work_item_details', [
            'work_item_id' => $workItem->id,
            'key'          => 'completed_doors',
        ]);
    }

    public function test_reject_includes_reason_in_notification(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3],
        ]);

        $this->mock(NotificationService::class, function ($mock) use ($assistant) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (User $user, array $data) use ($assistant) {
                    return $user->id === $assistant->id
                        && $data['type'] === 'progress_update_rejected'
                        && str_contains($data['body'], 'Incorrect data');
                });
        });

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $this->postJson("/api/progress-requests/{$progressRequest->id}/reject", [
            'comment' => 'Incorrect data',
        ])->assertStatus(200);
    }

    public function test_cannot_reject_already_approved_request(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'reviewed_by'  => $engineer->id,
            'status'       => 'approved',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3],
            'reviewed_at'  => now(),
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $this->postJson("/api/progress-requests/{$progressRequest->id}/reject")
            ->assertStatus(500);
    }

    /* ================================================================
       AUTHORIZATION TESTS
       ================================================================ */

    public function test_assistant_cannot_approve_or_reject(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3],
        ]);

        Sanctum::actingAs($assistant, ['*'], 'sanctum');

        // Assistant should not be able to approve (role middleware blocks this)
        $this->postJson("/api/progress-requests/{$progressRequest->id}/approve")
            ->assertStatus(403);

        $this->postJson("/api/progress-requests/{$progressRequest->id}/reject")
            ->assertStatus(403);
    }

    /* ================================================================
       TRANSACTION ROLLBACK TEST
       ================================================================ */

    public function test_transaction_rollback_on_approval_failure(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        $progressRequest = ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 3, 'total_doors' => 5],
        ]);

        // Mock updateProgress to throw an exception inside the transaction
        $this->partialMock(WorkItemProgressService::class, function ($mock) {
            $mock->shouldReceive('updateProgress')
                ->once()
                ->andThrow(new \RuntimeException('Simulated failure'));
        });

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $this->postJson("/api/progress-requests/{$progressRequest->id}/approve")
            ->assertStatus(500);

        // Verify the request was NOT marked as approved (transaction rolled back)
        $progressRequest->refresh();
        $this->assertSame('pending', $progressRequest->status);
        $this->assertNull($progressRequest->reviewed_by);
    }

    public function test_user_can_fetch_all_progress_requests(): void
    {
        $engineer  = $this->createUserWithRole('project_manager');
        $assistant = $this->createUserWithRole('assistant');
        $project   = $this->createProjectFor($engineer, $assistant);
        $workItem  = $this->createWorkItem($project);

        ProgressUpdateRequest::create([
            'project_id'   => $project->id,
            'work_item_id' => $workItem->id,
            'requested_by' => $assistant->id,
            'status'       => 'pending',
            'type'         => 'progress',
            'payload'      => ['completed_doors' => 2],
        ]);

        Sanctum::actingAs($engineer, ['*'], 'sanctum');

        $response = $this->getJson('/api/progress-requests');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'data');
    }

    /* ================================================================
       HELPERS
       ================================================================ */

    private function createProjectFor(User $engineer, ?User $assistant = null): Project
    {
        return Project::create([
            'name'                   => 'Approval Test Project',
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
            'name'          => 'أبواب ونجارة',
            'quality_level' => WorkItem::QUALITY_LEVEL_GOOD,
            'duration_days' => 5,
            'sort_order'    => 1,
            'is_default'    => false,
            'is_active'     => true,
            'is_custom'     => false,
        ]);
    }
}
