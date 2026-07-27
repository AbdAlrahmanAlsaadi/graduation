<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Project;
use App\Models\Space;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemInvoice;
use App\Models\WorkItemInvoiceItem;
use App\Models\WorkshopExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectCostEstimationTest extends TestCase
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

    public function test_estimate_total_cost_api_combines_material_and_workshop_estimates(): void
    {
        $user = $this->createUserWithRole('company_admin');

        $project = Project::factory()->create([
            'apartment_area' => 100,
            'height' => 3,
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson("/api/projects/{$project->id}/estimate-total-cost");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'data' => [
                    'project' => [
                        'id' => $project->id,
                    ],
                    'estimation_available' => false,
                    'estimated_materials_cost' => null,
                    'estimated_workshops_cost' => null,
                    'grand_total_estimated_cost' => null,
                ],
            ]);
    }

    public function test_compare_cost_api_calculates_actual_vs_estimated(): void
    {
        $user = $this->createUserWithRole('company_admin');

        $project = Project::factory()->create([
            'apartment_area' => 100,
            'height' => 3,
        ]);

        $workItem = WorkItem::factory()->create([
            'project_id' => $project->id,
            'name' => 'طينة / لياسة',
        ]);

        // Add actual invoice and workshop expense
        WorkItemInvoice::create([
            'project_id' => $project->id,
            'supplier_name' => 'المورد الأول',
            'invoice_number' => 'INV-999',
            'invoice_date' => now(),
            'total_amount' => 1200,
            'created_by' => $user->id,
        ]);

        WorkshopExpense::create([
            'project_id' => $project->id,
            'work_item_id' => $workItem->id,
            'created_by' => $user->id,
            'amount' => 800,
            'description' => 'أجرة طينة فعلية',
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson("/api/projects/{$project->id}/compare-cost");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'data' => [
                    'project' => [
                        'id' => $project->id,
                    ],
                    'actual_cost' => [
                        'invoices_materials_cost' => 1200,
                        'workshops_expenses_cost' => 800,
                        'returns_deduction' => 0,
                        'net_actual_cost' => 2000,
                    ],
                ],
            ]);
    }
}
