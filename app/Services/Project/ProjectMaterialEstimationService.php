<?php

namespace App\Services\Project;

use App\Services\Material\HistoricalMaterialService;

use App\Models\Material;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectMaterialEstimationService
{
    public function __construct(
        private ProjectMetricsService $metricsService,
        private HistoricalMaterialService $historicalService
    ) {}

    public function validateProjectAccess(Project $project): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Unauthenticated.', 401);
        }

        $isCompanyAdmin = $user->hasRole('company_admin');
        $isProjectManager = $user->hasRole('project_manager') && $project->project_manager_id == $user->id;
        $isAssistant = $user->hasRole('assistant') && $project->assistant_engineer_id == $user->id;
        $isOwner = $user->hasRole('project_owner') && $project->owner_id == $user->id;

        if (! $isCompanyAdmin && ! $isProjectManager && ! $isAssistant && ! $isOwner) {
            throw new \Exception('You are not allowed to access this project.', 403);
        }
    }

    /**
     * Main API method: Estimate all materials for a project with full formatted output.
     */
    public function estimateProjectMaterials(int $projectId): array
    {
        $project = Project::query()->with(['spaces', 'workItems.details'])->find($projectId);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        $this->validateProjectAccess($project);

        $previousProject = $this->historicalService->findPreviousProjectWithInvoices($project->id);

        if (! $previousProject) {
            return [
                'status' => 200,
                'message' => 'Estimation unavailable: No previous completed projects with invoice data found.',
                'data' => [
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'estimation_available' => false,
                    'materials' => [],
                    'grand_total_price' => null,
                ],
            ];
        }

        $materials = Material::query()->orderBy('id')->get();
        $estimatedMaterials = [];
        $grandTotal = 0.0;

        $currMetrics = $this->metricsService->extractMetrics($project);
        $prevMetrics = $this->metricsService->extractMetrics($previousProject);

        foreach ($materials as $material) {
            $name = trim($material->name);
            $qty = $this->calculateQuantity($name, $project, $previousProject, $currMetrics, $prevMetrics);

            // Exclude materials with 0 quantity or non-estimable items
            if ($qty === null || $qty <= 0) {
                continue;
            }

            $roundedQty = round($qty, 2);
            $unitPrice = $this->historicalService->getMaterialUnitPrice($material->id, $name, $project->id);

            $totalPrice = null;
            if ($unitPrice !== null) {
                $totalPrice = round($roundedQty * $unitPrice, 2);
                $grandTotal += $totalPrice;
            }

            $estimatedMaterials[] = [
                'material_id' => $material->id,
                'material_name' => $material->name,
                'unit' => $material->unit,
                'estimated_quantity' => $roundedQty,
                'unit_price' => $unitPrice !== null ? round($unitPrice, 2) : null,
                'total_price' => $totalPrice,
            ];
        }

        return [
            'status' => 200,
            'message' => 'Project materials estimated successfully.',
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'estimation_available' => true,
                'materials' => $estimatedMaterials,
                'grand_total_price' => round($grandTotal, 2),
            ],
        ];
    }

    /**
     * Reusable helper method to get just the total estimated material cost float for a project.
     * Useful for Total Project Cost Estimation API or Comparison APIs.
     */
    public function getGrandTotalMaterialCost(int $projectId): ?float
    {
        $result = $this->estimateProjectMaterials($projectId);
        if (! ($result['data']['estimation_available'] ?? false)) {
            return null;
        }

        return $result['data']['grand_total_price'] ?? 0.0;
    }

    private function calculateQuantity(
        string $name,
        Project $currentProject,
        Project $previousProject,
        array $c,
        array $p
    ): ?float {
        // Direct calculations from current project
        $directQty = $this->calculateDirectQuantity($name, $currentProject, $c);
        if ($directQty !== false) {
            return $directQty;
        }

        // Ratio-based calculations from Previous Project
        $prevQty = $this->historicalService->getPreviousMaterialQty($name, $previousProject->id);
        if ($prevQty <= 0) {
            return null;
        }

        return $this->calculateRatioQuantity($name, $prevQty, $c, $p);
    }

    private function calculateDirectQuantity(string $name, Project $currentProject, array $c): float|null|false
    {
        switch ($name) {
            case 'سيراميك أرضيات':
                $shedTiledArea = (float) $currentProject->spaces->where('type', 'shed')->where('is_shed_floor_tiled', true)->sum('ceiling_area');
                return $c['apt_area'] + $shedTiledArea;

            case 'سيراميك جدران وأسقف':
                return $c['ceramic_wall_area'] + $c['ceramic_ceiling_area'];

            case 'فتحة صوبيا':
                return (float) $c['rooms_count'];

            case 'لمبة':
                return (float) $c['bathrooms_and_toilets_count'];

            case 'علبة قواطع':
            case 'جرس باب':
            case 'سخان مياه':
            case 'مسكة باب خارجية':
            case 'دقاقة باب':
            case 'عين باب سحرية':
            case 'حماية باب حديد مع تركيب':
                return 1.0;

            case 'بلوعة':
                return (float) $c['all_spaces_count'];

            case 'مغسلة':
            case 'خرطوم تصريف مغسلة':
            case 'حنفية عادية':
            case 'حنفية مغسلة':
            case 'سيفون مغسلة':
                return (float) $c['bathrooms_and_toilets_count'];

            case 'حوض مجلى':
                return (float) $c['kitchens_count'];

            case 'طقم دوش':
                return (float) $c['bathrooms_count'];

            case 'شطاف مع خرطوم':
                return (float) $c['bathrooms_and_toilets_count'];

            case 'مرحاض إفرنجي':
                return (float) $c['western_toilets_count'];

            case 'مرحاض عربي':
                return (float) $c['arabic_toilets_count'];

            case 'خلاط مياه':
                return (float) ($c['bathrooms_count'] + $c['kitchens_count']);

            case 'لوح جبس بورد':
                return $c['gypsum_surface_total'] > 0 ? $c['gypsum_surface_total'] / 3.6 : 0.0;

            case 'مرابين رخام مع تركيب':
            case 'مالبن رخام مع تركيب':
                return (float) ($c['aluminum_doors'] + $c['windows']);

            case 'مرابين خشب مع تركيب':
            case 'مالبن خشب مع تركيب':
            case 'باب خشب مع تركيب':
            case 'قفل باب':
            case 'جوزة قفل':
            case 'طقم مسكات باب':
            case 'مصد باب':
                return (float) $c['wood_doors'];

            case 'مفصلات باب':
                return (float) ($c['wood_doors'] * 3);

            case 'صندوق أباجور مع تركيب':
            case 'حماية شباك حديد مع تركيب':
            case 'شباك ألمنيوم مع تركيب':
            case 'أباجور مع تركيب':
                return (float) $c['windows'];

            case 'باب ألمنيوم مع تركيب':
                return (float) $c['aluminum_doors'];

            case 'ثريا':
                return (float) $c['rooms_and_salons_count'];

            // Excluded materials (No automatic estimation)
            case 'فواصل سيراميك':
            case 'سوكة لمبة':
            case 'مضخة باب':
            case 'محرك أباجور':
            case 'بديل خشب':
            case 'بديل رخام':
            case 'مراية':
            case 'خزانة ديكور مع تركيب':
            case 'لاصق ديكور':
            case 'زوائد تثبيت':
            case 'بروفيل ديكور':
                return null;

            default:
                return false; // Signal that it requires ratio calculation
        }
    }

    private function calculateRatioQuantity(string $name, float $prevQty, array $c, array $p): ?float
    {
        switch ($name) {
            case 'إسمنت أسود':
            case 'رمل':
            case 'مياه':
                return $p['wall_area'] > 0 ? ($prevQty / $p['wall_area']) * $c['wall_area'] : null;

            case 'إسمنت أبيض':
                $prevBase = $p['apt_area'] + $p['ceramic_wall_area'];
                $currBase = $c['apt_area'] + $c['ceramic_wall_area'];
                return $prevBase > 0 ? ($prevQty / $prevBase) * $currBase : null;

            case 'لاصق سيراميك':
                return $p['total_ceramic_area'] > 0 ? ($prevQty / $p['total_ceramic_area']) * $c['total_ceramic_area'] : null;

            case 'دهان':
            case 'معجونة دهان':
                return $p['paint_surface_total'] > 0 ? ($prevQty / $p['paint_surface_total']) * $c['paint_surface_total'] : null;

            case 'رول دهان':
            case 'عصاية رول دهان':
            case 'فرشاية دهان':
            case 'ورق زجاج':
            case 'شريط حماية دهان':
            case 'نايلون حماية':
            case 'تنر دهان':
            case 'أنبوب تمديد كهربائي (تيب)':
            case 'علبة كهرباء':
            case 'سلك كهرباء':
            case 'بريز كهرباء':
            case 'مفتاح كهرباء':
            case 'عدسية':
            case 'سبوت إنارة':
            case 'قاطع كهربائي':
            case 'أنبوب مياه تغذية':
            case 'أنبوب صرف صحي':
            case 'وصلات وأكواع صحية':
            case 'محبس مياه':
            case 'شريط تفلون':
            case 'سيليكون صحي':
            case 'فوم تثبيت مالبن':
            case 'براغي عامة':
                return $p['apt_area'] > 0 ? ($prevQty / $p['apt_area']) * $c['apt_area'] : null;

            case 'مجلى مع تركيب':
            case 'خزن مطبخ مع تركيب':
                return $p['kitchen_area'] > 0 ? ($prevQty / $p['kitchen_area']) * $c['kitchen_area'] : null;

            case 'قائم معدني للجبس':
            case 'مسار معدني للجبس':
            case 'علاقة جبس بورد':
            case 'شريط فواصل جبس':
            case 'معجونة فواصل جبس':
                return $p['gypsum_surface_total'] > 0 ? ($prevQty / $p['gypsum_surface_total']) * $c['gypsum_surface_total'] : null;

            default:
                return $p['apt_area'] > 0 ? ($prevQty / $p['apt_area']) * $c['apt_area'] : null;
        }
    }
}
