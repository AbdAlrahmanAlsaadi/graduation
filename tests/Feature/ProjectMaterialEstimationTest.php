<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Project;
use App\Models\Space;
use App\Models\User;
use App\Models\WorkItemInvoice;
use App\Models\WorkItemInvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectMaterialEstimationTest extends TestCase
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

    public function test_estimation_returns_unavailable_when_no_previous_project_has_invoices(): void
    {
        $user = $this->createUserWithRole('company_admin');

        $project = Project::factory()->create([
            'apartment_area' => 120,
            'height' => 3,
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson("/api/projects/{$project->id}/estimate-materials");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 200,
                'data' => [
                    'project' => [
                        'id' => $project->id,
                    ],
                    'estimation_available' => false,
                    'materials' => [],
                    'grand_total_price' => null,
                ],
            ]);
    }

    public function test_estimation_calculates_quantities_and_prices_from_previous_project(): void
    {
        $user = $this->createUserWithRole('company_admin');

        // Create Previous Project with invoice
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

        $materialCement = Material::create(['name' => 'إسمنت أسود', 'unit' => 'كيس']);
        $materialPaint = Material::create(['name' => 'دهان', 'unit' => 'سطل']);

        $invoice = WorkItemInvoice::create([
            'project_id' => $prevProject->id,
            'supplier_name' => 'المورد الأول',
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'total_amount' => 500,
            'created_by' => $user->id,
        ]);

        WorkItemInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'material_id' => $materialCement->id,
            'material_name_snapshot' => 'إسمنت أسود',
            'quantity' => 10,
            'unit' => 'كيس',
            'unit_price' => 20,
            'total_price' => 200,
        ]);

        WorkItemInvoiceItem::create([
            'invoice_id' => $invoice->id,
            'material_id' => $materialPaint->id,
            'material_name_snapshot' => 'دهان',
            'quantity' => 5,
            'unit' => 'سطل',
            'unit_price' => 60,
            'total_price' => 300,
        ]);

        // Create Current Project
        $currProject = Project::factory()->create([
            'apartment_area' => 150,
            'height' => 3,
        ]);
        Space::factory()->create([
            'project_id' => $currProject->id,
            'type' => 'room',
            'wall_area' => 100, // Double wall area (50 -> 100)
            'ceiling_area' => 30,
            'wall_finish_type' => 'paint',
            'ceiling_finish_type' => 'paint',
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson("/api/projects/{$currProject->id}/estimate-materials");

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

        $materials = $response->json('data.materials');
        $this->assertNotEmpty($materials);

        // Check Black Cement (10 bags for 50 wall area -> 20 bags for 100 wall area)
        $cementItem = collect($materials)->firstWhere('material_name', 'إسمنت أسود');
        $this->assertNotNull($cementItem);
        $this->assertEquals(20, $cementItem['estimated_quantity']);
        $this->assertEquals(20, $cementItem['unit_price']);
        $this->assertEquals(400, $cementItem['total_price']);
    }
}
