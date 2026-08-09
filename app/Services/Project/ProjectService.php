<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use App\Services\ProjectCostEstimationService; // استيراد خدمة التقدير

class ProjectService
{
    protected ProjectCostEstimationService $estimationService;

    // حقن EstimationService في المُنشئ
    public function __construct(ProjectCostEstimationService $estimationService)
    {
        $this->estimationService = $estimationService;
    }
    public const DEFAULT_WORK_ITEMS = [
        'ملابن الأبواب',
        'تمديدات صحية سواد',
        'تمديدات كهرباء سواد',
        'طينة / لياسة',
        'سيراميك جدران / أسقف',
        'جبس بورد',
        'بلاط أرضيات',
        'ألمنيوم وأبجورات',
        'أبواب ونجارة',
        'دهان',
        'تمديدات كهرباء بياض',
        'تمديدات صحية بياض',
        'ديكورات',
    ];

    public function index() {
        $user = Auth::user();
        if($user->hasRole('company_admin'))
            $projects = Project::query()->latest()->get();
        else if($user->hasRole('project_manager'))
            $projects = Project::query()->where('project_manager_id', $user->id)->latest()->get();
        else if($user->hasRole('assistant'))
            $projects = Project::query()->where('assistant_engineer_id', $user->id)->latest()->get();
        else
            $projects = Project::query()->where('owner_id', $user->id)->latest()->get();
        return $projects;
    }

    public function createProjectWithDefaults(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();
            if ($userId) {
                $data['created_by'] = $data['created_by'] ?? $userId;
                $data['updated_by'] = $data['updated_by'] ?? $userId;
            }
            $data['status'] = Project::STATUS_PLANNED;
            $project = Project::query()->create($data);
            $now = now();
            $workItems = [];

            foreach (self::DEFAULT_WORK_ITEMS as $index => $name) {
                $workItems[] = [
                    'project_id' => $project->id,
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'quality_level' => WorkItem::QUALITY_LEVEL_BASIC,
                    'is_default' => true,
                    'is_active' => true,
                    'is_custom' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            WorkItem::query()->insert($workItems);

            $project->load([
                'workItems' => fn ($query) => $query->orderBy('sort_order'),
            ]);

            return $project;
        });
    }

    public function updateProject(Project $project, array $data): Project
    {
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException('Unauthorized.', 401);
        }

        if (! $user->hasRole('company_admin')) {
            $projectManagerId = array_key_exists('project_manager_id', $data)
                ? $data['project_manager_id']
                : $project->project_manager_id;
            $assistantEngineerId = array_key_exists('assistant_engineer_id', $data)
                ? $data['assistant_engineer_id']
                : $project->assistant_engineer_id;
            $ownerId = array_key_exists('owner_id', $data)
                ? $data['owner_id']
                : $project->owner_id;

            if (
                (int) $projectManagerId !== (int) $project->project_manager_id
                || (int) $assistantEngineerId !== (int) $project->assistant_engineer_id
                || (int) ($ownerId ?? 0) !== (int) ($project->owner_id ?? 0)
            ) {
                throw new RuntimeException('Only admin can modify project assignments.', 403);
            }
        }

        $data['updated_by'] = $user->id;

        $project->update($data);

        return $project->fresh();
    }

    /**
     * Start a project if it is planned.
     */
    public function startProject(Project $project): Project
    {
        if ($project->isCompleted()) {
            throw new RuntimeException('Project already completed.', 400);
        }

        if ($project->isOngoing()) {
            return $project;
        }

        return DB::transaction(function () use ($project) {
            if ($project->started_at === null) {
                $project->started_at = now();
            }
            $project->status = Project::STATUS_ONGOING;
            $project->save();

            return $project->fresh();
        });
    }

    /**
     * Complete a project if it has been started.
     */
    public function completeProject(Project $project): Project
    {
        if ($project->isCompleted()) {
            return $project;
        }

        if ($project->isPlanned()) {
            throw ValidationException::withMessages([
                'status' => 'Project must be started before completing.',
            ])->status(400);
        }

        if($project->workItems()->where('status', '!=', 'completed')->count() > 0){
            throw ValidationException::withMessages([
                'status' => 'All work items must be completed before completing the project.',
            ])->status(400);
        }

        return DB::transaction(function () use ($project) {
            $project->status = Project::STATUS_COMPLETED;
            if ($project->completed_at === null) {
                $project->completed_at = now();
            }
            $project->save();

            return $project->fresh();
        });
    }
    public function search($request): array
    {
        $request->validated();

        $projects = Project::query()

            ->with([
                'projectManager',
                'assistantEngineer',
                'owner',
                'workItems',
                'workItems.equipmentBookings.equipment',
            ])

            ->where(function ($query) use ($request) {

                $query->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('location', 'like', '%' . $request->keyword . '%');
            })

            ->paginate(10);

        if ($projects->total() === 0) {
            throw new \Exception('No projects found.', 404);
        }

        $projects->getCollection()->transform(function ($project) {

            $equipment = collect();

            foreach ($project->workItems as $workItem) {

                foreach ($workItem->equipmentBookings as $booking) {

                    if ($booking->equipment) {

                        $equipment->push([
                            'id' => $booking->equipment->id,
                            'name' => $booking->equipment->name,
                            'type' => $booking->equipment->type,
                            'identifier_no' => $booking->equipment->identifier_no,
                            'status' => $booking->equipment->status,
                        ]);
                    }
                }
            }

            return [

                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'status' => $project->status,

                'manager' => $project->projectManager ? [
                    'id' => $project->projectManager->id,
                    'name' => $project->projectManager->name,
                    'internal_id' => $project->projectManager->internal_id,
                ] : null,

                'assistant_engineer' => $project->assistantEngineer ? [
                    'id' => $project->assistantEngineer->id,
                    'name' => $project->assistantEngineer->name,
                    'internal_id' => $project->assistantEngineer->internal_id,
                ] : null,

                'owner' => $project->owner ? [
                    'id' => $project->owner->id,
                    'name' => $project->owner->name,
                    'internal_id' => $project->owner->internal_id,
                ] : null,

                'work_items_count' => $project->workItems->count(),

                'equipment_count' => $equipment->unique('id')->count(),

                'work_items' => $project->workItems->map(function ($item) {

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quality_level' => $item->quality_level,
                        'duration_days' => $item->duration_days,
                        'is_active' => $item->is_active,
                    ];
                })->values(),

                'equipment' => $equipment
                    ->unique('id')
                    ->values(),
            ];
        });

        return [
            'message' => 'Projects fetched successfully.',

            'projects' => $projects->items(),

            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],

            'status' => 200,
        ];
    }

    public function getOngoingProjects(): array
    {
        // جلب المشاريع مع العلاقات
        $projects = Project::with([
            'workItems',
            'invoices',
            'laborCosts',
            'workshopExpenses'
        ])
            ->where('status', Project::STATUS_ONGOING)
            ->get();

        if ($projects->isEmpty()) {
            return [
                'data'    => [],
                'message' => 'لا يوجد أي مشروع قيد التنفيذ حالياً.',
            ];
        }
        // داخل دالة getOngoingProjects() في ProjectService

        // ... بعد جلب البيانات ...

        $data = $projects->map(function ($project) {
            // 1. نسبة الإنجاز
            $workItems = $project->workItems;
            $total = $workItems->count();
            $completed = $workItems->where('status', WorkItem::STATUS_COMPLETED)->count();
            $completionPercentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

            // 2. ✅ حساب الأيام المتبقية (باستخدام started_at + مجموع duration_days)
            $remainingDays = 0;
            if ($project->started_at) {
                // مجموع أيام العمل من جميع البنود
                $totalDuration = $project->workItems->sum('duration_days');
                if ($totalDuration > 0) {
                    $expectedEndDate = Carbon::parse($project->started_at)->addDays($totalDuration);
                    $remainingDays = (int) Carbon::now()->diffInDays($expectedEndDate, false);
                    // لو كانت القيمة سالبة، هذا يعني أن الموعد انتهى والمشروع متأخر.
                }
            }

            // 3. التكلفة الحالية
            $invoicesTotal = (float) $project->invoices->sum('total_amount');
            $laborCostsTotal = (float) $project->laborCosts->sum('cost');
            $workshopExpensesTotal = (float) $project->workshopExpenses->sum('amount');
            $currentCost = $invoicesTotal + $laborCostsTotal + $workshopExpensesTotal;

            // 4. التكلفة التقديرية (من EstimationService)
            $estimatedValue = $this->getEstimatedValue($project->id);

            // 5. تحديد الحالة (طبيعي / متأخر / تجاوز الميزانية)
            $status = $this->determineStatus($currentCost, $estimatedValue, $remainingDays, $completionPercentage);

            return [
                'project_name'          => $project->name,
                'completion_percentage' => $completionPercentage,
                'remaining_days'        => $remainingDays, // الآن تظهر القيمة الحقيقية (قد تكون سالبة إذا متأخر)
                'current_cost'          => number_format($currentCost, 2),
                'estimated_value'       => number_format($estimatedValue, 2),
                'status'                => $status,
            ];
        });
        return [
            'data'    => $data,
            'message' => 'تم جلب البيانات بنجاح',
        ];
    }

    /**
     * جلب التكلفة التقديرية للمشروع من EstimationService.
     */
    private function getEstimatedValue(int $projectId): float
    {
        try {
            // استدعاء خدمة التقدير بقيم افتراضية (يمكن تعديلها حسب الحاجة)
            $result = $this->estimationService->estimateTotalProjectCost(
                $projectId,
                0,      // beams_count
                0.1     // skirting_factor
            );

            // استخراج grand_total_estimated_cost من النتيجة
            return (float) ($result['data']['grand_total_estimated_cost'] ?? 0.0);
        } catch (\Exception $e) {
            // في حال فشل التقدير، نعيد 0
            return 0.0;
        }
    }

    /**
     * تحديد حالة المشروع.
     */
    private function determineStatus(float $currentCost, float $estimatedValue, int $remainingDays, float $completionPercentage): string
    {
        // إذا كانت التكلفة الحالية > التكلفة التقديرية (والتقديرية > 0)
        if ($estimatedValue > 0 && $currentCost > $estimatedValue) {
            return 'تجاوز الميزانية';
        }

        // إذا انتهى الموعد ولم تكتمل الأعمال
        if ($remainingDays < 0 && $completionPercentage < 100) {
            return 'متأخر';
        }

        return 'طبيعي';
    }

    public function calculateOnTimeDeliveryRate(): array
    {
        // 1. جلب جميع المشاريع المكتملة مع بنود العمل
        $projects = Project::with('workItems')
            ->where('status', Project::STATUS_COMPLETED)
            ->get();

        if ($projects->isEmpty()) {
            return [
                'status' => 200,
                'message' => 'لا توجد مشاريع مكتملة.',
                'data' => [
                    'total_completed_projects' => 0,
                    'on_time_projects' => 0,
                    'delayed_projects' => 0,
                    'on_time_rate' => 0,
                    'projects' => [],
                ],
            ];
        }

        $onTime = 0;
        $delayed = 0;
        $projectDetails = [];

        foreach ($projects as $project) {
            // حساب تاريخ الانتهاء المتوقع
            $totalDuration = $project->workItems->sum('duration_days');
            $startedAt = Carbon::parse($project->started_at);
            $expectedEndDate = $startedAt->copy()->addDays($totalDuration);

            // تاريخ الانتهاء الفعلي
            $completedAt = Carbon::parse($project->completed_at);

            // تحديد الحالة
            $isOnTime = $completedAt->lte($expectedEndDate);

            if ($isOnTime) {
                $onTime++;
            } else {
                $delayed++;
            }

            $projectDetails[] = [
                'id' => $project->id,
                'name' => $project->name,
                'started_at' => $project->started_at,
                'completed_at' => $project->completed_at,
                'expected_end_date' => $expectedEndDate->toDateTimeString(),
                'status' => $isOnTime ? 'في الموعد' : 'متأخر',
            ];
        }

        $totalCompleted = $projects->count();
        $onTimeRate = $totalCompleted > 0 ? round(($onTime / $totalCompleted) * 100, 2) : 0;

        return [
            'status' => 200,
            'message' => 'تم حساب نسبة التسليم في الموعد بنجاح.',
            'data' => [
                'total_completed_projects' => $totalCompleted,
                'on_time_projects' => $onTime,
                'delayed_projects' => $delayed,
                'on_time_rate' => $onTimeRate,
                'projects' => $projectDetails,
            ],
        ];
    }
}
