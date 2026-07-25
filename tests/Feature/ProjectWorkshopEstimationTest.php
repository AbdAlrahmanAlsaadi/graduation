<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Space;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkshopExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectWorkshopEstimationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_estimation_returns_unavailable_when_no_previous_project_has_workshop_expenses(): void
    {
        $user = $this->createUserWithRole('company_admin');

        $project = Project::factory()->create([
            'apartment_area' => 120,
            'height' => 3,
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson("/api/projects/{$project->id}/estimate-workshops");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'data' => [
                    'project' => [
                        'id' => $project->id,
                    ],
                    'estimation_available' => false,
                    'workshops' => [],
                    'grand_total_workshop_cost' => null,
                ],
            ]);
    }

    public function test_estimation_calculates_workshop_costs_from_previous_project(): void
    {
        $user = $this->createUserWithRole('company_admin');

        // Create Previous Project with workshop expenses
        $prevProject = Project::factory()->create([
            'apartment_area' => 100,
            'height' => 3,
        ]);
        Space::factory()->create([
            'project_id' => $prevProject->id,
            'type' => 'room',
            'wall_area' => 50,
            'ceiling_area' => 20,
            'wall_finish_type' => 'paint',
            'ceiling_finish_type' => 'paint',
        ]);

        $prevWorkItemPlumbing = WorkItem::factory()->create([
            'project_id' => $prevProject->id,
            'name' => 'تمديدات صحية سواد',
        ]);
        $prevWorkItemElec = WorkItem::factory()->create([
            'project_id' => $prevProject->id,
            'name' => 'تمديدات كهرباء سواد',
        ]);

        WorkshopExpense::create([
            'project_id' => $prevProject->id,
            'work_item_id' => $prevWorkItemPlumbing->id,
            'created_by' => $user->id,
            'amount' => 4000,
            'description' => 'مصاريف صحية',
        ]);

        WorkshopExpense::create([
            'project_id' => $prevProject->id,
            'work_item_id' => $prevWorkItemElec->id,
            'created_by' => $user->id,
            'amount' => 5000,
            'description' => 'مصاريف كهرباء',
        ]);

        // Create Current Project (150 m2 apartment area, 1.5x of previous)
        $currProject = Project::factory()->create([
            'apartment_area' => 150,
            'height' => 3,
        ]);
        Space::factory()->create([
            'project_id' => $currProject->id,
            'type' => 'room',
            'wall_area' => 75,
            'ceiling_area' => 30,
            'wall_finish_type' => 'paint',
            'ceiling_finish_type' => 'paint',
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson("/api/projects/{$currProject->id}/estimate-workshops");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'data' => [
                    'project' => [
                        'id' => $currProject->id,
                    ],
                    'estimation_available' => true,
                ],
            ]);

        $workshops = $response->json('data.workshops');
        $this->assertNotEmpty($workshops);

        // Check Plumbing (Sanitary): fixed to previous project cost = 4000
        $sanitaryItem = collect($workshops)->firstWhere('workshop_name', 'ورشة الصحية - سواد وبياض');
        $this->assertNotNull($sanitaryItem);
        $this->assertEquals(4000, $sanitaryItem['estimated_cost']);

        // Check Electrical: scaled by apartment area ratio (5000 / 100 * 150 = 7500)
        $elecItem = collect($workshops)->firstWhere('workshop_name', 'ورشة الكهرباء - سواد وبياض');
        $this->assertNotNull($elecItem);
        $this->assertEquals(7500, $elecItem['estimated_cost']);
    }
}
