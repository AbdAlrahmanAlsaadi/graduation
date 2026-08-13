<?php

namespace App\Services\WorkItem;

use App\Services\Project\ProjectService;

use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkItemDetail;
use App\Models\ProgressPhoto;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WorkItemProgressService
{
    protected array $logic;
    protected array $templates;

    public function __construct()
    {
        $this->logic     = config('work_item_logic');
        $this->templates = config('work_item_templates');
    }

    /* =========================================================================
       UPDATE DETAILS (FULL SYNC)
       ========================================================================= */

    public function updateDetails(Project $project, WorkItem $item, array $data): WorkItem
    {
        $template = $this->templates[$item->name] ?? null;

        if (!$template) {
            abort(400, "No template defined for work item name: {$item->name}");
        }

        $details = [];

        foreach ($template as $key => $meta) {
            if (!array_key_exists($key, $data)) {
                abort(422, "Missing required field: {$key}");
            }

            $details[] = [
                'key'   => $key,
                'value' => $data[$key],
                'unit'  => $meta['unit'] ?? null,
            ];
        }

        $item->syncDetails($details);

        return $item->fresh('details');
    }

    /* =========================================================================
       UPDATE PROGRESS (PARTIAL UPDATE)
       ========================================================================= */
    public function updateProgress(Project $project, WorkItem $item, array $data): array
    {
        if($item->isPlanned()){
            app(WorkItemService::class)->startWorkItem($project, $item);
        }

        if($item->isCompleted()){
            abort(400,'Work item already completed');
        }
        
        if (array_key_exists('photos', $data)) {

            $photos = $data['photos'] ?? [];

            if ($photos instanceof \Illuminate\Http\UploadedFile) {
                $photos = [$photos];
            }
            if (!is_array($photos)) {

                $photos = [];
            }
            $this->storeProgressPhotos($project, $item, $photos, null);

            unset($data['photos']);
        }

        $template = $this->templates[$item->name] ?? [];

        DB::transaction(function () use ($data, $item, $template) {
            foreach ($data as $key => $value) {
                // Only allow keys defined in the template
                if (!empty($template) && !array_key_exists($key, $template)) {
                    continue;
                }

                $meta = $template[$key] ?? [];
                $isAdditive = $meta['additive'] ?? false;

                if ($isAdditive && is_numeric($value)) {
                    // Additive: add new value to existing
                    $existing = WorkItemDetail::where('work_item_id', $item->id)
                        ->where('key', $key)
                        ->first();

                    $oldValue = $existing ? (float) $existing->value : 0;
                    $newValue = $oldValue + (float) $value;

                    // Abort if exceeding total
                    $totalKey = str_replace('completed_', 'total_', $key);
                    if ($totalKey !== $key) {
                        $totalDetail = WorkItemDetail::where('work_item_id', $item->id)
                            ->where('key', $totalKey)
                            ->first();
                        if ($totalDetail && $newValue > (float) $totalDetail->value) {
                            abort(422, "Value for '{$key}' would exceed its total ({$newValue} > {$totalDetail->value}).");
                        }
                    }

                    WorkItemDetail::updateOrCreate(
                        ['work_item_id' => $item->id, 'key' => $key],
                        ['value' => $newValue]
                    );
                } else {
                    // Replace: standard behavior
                    WorkItemDetail::updateOrCreate(
                        ['work_item_id' => $item->id, 'key' => $key],
                        ['value' => $value]
                    );
                }
            }
        });

        $percent = $this->computeWorkItemPercent($item);
        
        if($percent == 100 && !$item->isCompleted()) {
            app(WorkItemService::class)->completeWorkItem($project, $item);
        }

        return [
            'work_item' => $item->refresh()->load('progressPhotos'),
            'percent'   => $percent,
        ];
    }

    /* =========================================================================
       UPDATE SINGLE ROOM STATUS (NEW ENDPOINT)
       ========================================================================= */

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $photos
     */
    public function updateRoomStatus(Project $project, WorkItem $item, int $spaceId, bool $completed, array $photos = []): array
    {
        if($item->isPlanned()){
            app(WorkItemService::class)->startWorkItem($project, $item);
        }

        if($item->isCompleted()){
            abort(400,'Work item already completed');
        }

        if (!empty($photos)) {
            $this->storeProgressPhotos($project, $item, $photos, $spaceId);
        }

        $space = $this->validateSpaceForWorkItem($project, $item, $spaceId);

        // get old rooms_status
        $old = WorkItemDetail::where('work_item_id', $item->id)
            ->where('key', 'rooms_status')
            ->first();

        $status = $old ? json_decode($old->value, true) : [];
        if (!is_array($status)) $status = [];

        // update one room
        $status[$spaceId] = $completed;

        // save
        WorkItemDetail::updateOrCreate(
            ['work_item_id' => $item->id, 'key' => 'rooms_status'],
            ['value' => json_encode($status)]
        );

        // compute percent
        $percent = $this->computeWorkItemPercent($item);
        
        if($percent == 100 && !$item->isCompleted()) {
            app(WorkItemService::class)->completeWorkItem($project, $item);
        }

        return [
            'work_item' => $item->refresh()->load('progressPhotos'),
            'percent'   => $percent,
        ];
    }

    public function validateSpaceForWorkItem(Project $project, WorkItem $item, int $spaceId): Space
    {
        $space = Space::where('id', $spaceId)
            ->where('project_id', $project->id)
            ->first();

        if (!$space) {
            abort(404, "Space does not belong to this project.");
        }

        // تحقق أن الغرفة ينطبق عليها البند
        $type = $this->logic['mapping'][$item->name] ?? null;
        $method = $type ? 'filter' . ucfirst($type) : null;

        if ($method && method_exists($this, $method)) {
            if (!$this->{$method}($space)) {
                abort(422, "This space does not apply to this work item." . " Method: {$method}");
            }
        }

        return $space;
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $photos
     */
    private function storeProgressPhotos(Project $project, WorkItem $item, array $photos, int $spaceId = null): void
    {
        if (empty($photos)) {
            return;
        }

        $baseDir = 'progress-photos/' . $project->id . '/' . $item->id;

        foreach ($photos as $photo) {
            $extension = $photo->getClientOriginalExtension();
            $fileName = Str::uuid()->toString() . '.' . $extension;
            $storedPath = $photo->storeAs($baseDir, $fileName, 'public');

            ProgressPhoto::create([
                'project_id' => $project->id,
                'work_item_id' => $item->id,
                'file_path' => $storedPath,
                'original_name' => $photo->getClientOriginalName(),
                'space_id' => $spaceId,
            ]);
        }
    }

    /* =========================================================================
       COMPUTE WORK ITEM PERCENT
       ========================================================================= */

    public function computeWorkItemPercent(WorkItem $item): float
    {
        $type = $this->logic['mapping'][$item->name] ?? null;
        if (!$type) return 0;

        $method = 'compute' . ucfirst($type);
        if (!method_exists($this, $method)) return 0;
        if($item->id == 2)
            dd($method);
        $details = $item->details()->get()->keyBy('key');
        $spaces  = Space::where('project_id', $item->project_id)->get();

        $ref = new \ReflectionMethod($this, $method);
        $paramCount = $ref->getNumberOfParameters();

        if ($paramCount >= 3) {
            $result = $this->{$method}($details, $spaces, $item->name);
        } elseif ($paramCount === 2) {
            $result = $this->{$method}($details, $spaces);
        } else {
            $result = $this->{$method}($details);
        }

        return round($result, $this->logic['settings']['percent_precision']);
    }

    // public function computeWorkItemPercent(WorkItem $item): float
    // {
    //     $type = $this->logic['mapping'][$item->name] ?? null;

    //     if (!$type) return 0;

    //     $method = 'compute' . ucfirst($type);

    //     if (!method_exists($this, $method)) return 0;

    //     $details = $item->details()->get()->keyBy('key');
    //     $spaces  = Space::where('project_id', $item->project_id)->get();

    //     return round(
    //         $this->{$method}($details, $spaces),
    //         $this->logic['settings']['percent_precision']
    //     );
    // }

    /* =========================================================================
       COMPUTE PROJECT PERCENT
       ========================================================================= */

    public function computeProjectPercent(Project $project): float
    {
        $items = $project->workItems()
        ->where('is_active', true)
        ->with('details')
        ->get();

        $totalWeight = $items->sum('weight');
        if ($totalWeight == 0) return 0;

        $sum = 0;
        $completedItems = 0;
        foreach ($items as $item) {
            $percent = $this->computeWorkItemPercent($item);
            if($percent == 100) $completedItems++;
            $sum += ($percent * $item->weight);
        }

        // if all work items are completed, complete the project
        if($completedItems == $items->count()){
            app(ProjectService::class)->completeProject($project);
        }

        return round($sum / $totalWeight, $this->logic['settings']['percent_precision']);
    }

    /* =========================================================================
       GENERIC ROOMS_STATUS STRATEGY
       ========================================================================= */

    protected function computeRoomsStatus(Collection $details, Collection $spaces, callable $filter, ?string $workItemName = null): float
    {
        $filteredSpaces = $spaces->filter($filter);
        $validSpaceIds = $filteredSpaces->pluck('id')->toArray();

        if (empty($validSpaceIds)) {
            return 0;
        }

        $roomsStatusDetail = $details->get('rooms_status');
        $statusJson = $roomsStatusDetail ? $roomsStatusDetail->value : null;
        $status = $statusJson ? json_decode($statusJson, true) : [];
        if (!is_array($status)) $status = [];

        $totalArea = 0.0;
        $doneArea  = 0.0;
        $hasAreaData = false;

        foreach ($filteredSpaces as $s) {
            $spaceArea = null;
            if ($workItemName) {
                $spaceArea = $this->getSpaceAreaForWorkItem($s, $workItemName);
            }

            if ($spaceArea !== null && $spaceArea > 0) {
                $hasAreaData = true;
                $totalArea += $spaceArea;
                if (isset($status[$s->id]) && ($status[$s->id] === true || $status[$s->id] == 1)) {
                    $doneArea += $spaceArea;
                }
            }
        }

        if ($hasAreaData && $totalArea > 0) {
            return ($doneArea / $totalArea) * 100;
        }

        // fallback to count-based percent
        $doneCount = 0;
        foreach ($validSpaceIds as $id) {
            if (isset($status[$id]) && ($status[$id] === true || $status[$id] == 1)) {
                $doneCount++;
            }
        }
        $totalCount = count($validSpaceIds);
        if ($totalCount === 0) return 0;
        return ($doneCount / $totalCount) * 100;
    }


    /**
     * Returns the applicable spaces for a work item, split into finished and unfinished.
     *
     * @return array{finished: \Illuminate\Database\Eloquent\Collection, unfinished: \Illuminate\Database\Eloquent\Collection}
     */
    public function getFinishedSpaces($project, $workItem): array
    {
        // 1) Determine the filter method for this work item type
        $type = $this->logic['mapping'][$workItem->name] ?? null;
        $filterMethod = $type ? 'filter' . ucfirst($type) : null;

        // 2) Fetch all project spaces in a single query
        $spaces = $project->spaces;

        // 3) Filter to only applicable spaces
        if ($filterMethod && method_exists($this, $filterMethod)) {
            $spaces = $spaces->filter(fn(Space $s) => $this->{$filterMethod}($s));
        }

        // 4) Fetch the rooms_status JSON (single query)
        $detail = WorkItemDetail::where('work_item_id', $workItem->id)
            ->where('key', 'rooms_status')
            ->first();

        $status = $detail ? json_decode($detail->value, true) : [];
        if (!is_array($status)) {
            $status = [];
        }

        // 5) Fetch all photos for this work item in a single query, grouped by space_id
        $spaceIds = $spaces->pluck('id')->toArray();
        $photosBySpace = ProgressPhoto::where('work_item_id', $workItem->id)
            ->whereIn('space_id', $spaceIds)
            ->get()
            ->groupBy('space_id');

        // 6) Attach photos to each space
        $spaces->each(function (Space $s) use ($photosBySpace) {
            $s->setAttribute('photos', $photosBySpace->get($s->id, collect()));
        });

        // 7) Partition into finished / unfinished
        [$finished, $unfinished] = $spaces->partition(
            fn(Space $s) => isset($status[$s->id]) && ($status[$s->id] === true || $status[$s->id] == 1)
        );

        return [
            'finished'   => $finished->values(),
            'unfinished' => $unfinished->values(),
        ];
    }

    /* =========================================================================
       STRATEGIES
       ========================================================================= */

    protected function computeMellaben(Collection $details): float
    {
        $parts = [
            ['total_wood_doors', 'completed_wood_doors'],
            ['total_aluminum_doors', 'completed_aluminum_doors'],
            ['total_windows', 'completed_windows'],
        ];

        $sum = 0;
        $weights = 0;

        foreach ($parts as [$totalKey, $doneKey]) {
            $total = (int) ($details[$totalKey]->value ?? 0);
            $done  = (int) ($details[$doneKey]->value ?? 0);

            if ($total > 0) {
                $sum += ($done / $total) * 100 * $total;
                $weights += $total;
            }
        }

        return $weights > 0 ? $sum / $weights : 0;
    }

    protected function computeElectricity(Collection $details, Collection $spaces, ?string $itemName = null): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) => true,
        $itemName ?? 'تمديدات كهرباء ');
    }

    protected function computeRooms(Collection $details, Collection $spaces, ?string $itemName = null): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->wall_finish_type !== 'ceramic',
        $itemName ?? 'سيراميك جدران / أسقف'
        );
    }

    protected function computeTile(Collection $details, Collection $spaces, ?string $itemName = null): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) => true,
        $itemName ?? 'بلاط أرضيات');
    }

    protected function computeCeramic(Collection $details, Collection $spaces, ?string $itemName = null): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->wall_finish_type === 'ceramic' ||
            $s->ceiling_finish_type === 'ceramic',
        $itemName ?? 'سيراميك جدران / أسقف'
        );
    }

    protected function computeGypsum(Collection $details, Collection $spaces, ?string $itemName = null): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->ceiling_finish_type === 'gypsum' ||
            $s->wall_finish_type === 'gypsum',
        $itemName ?? 'جبس بورد'
        );
    }

    protected function computePaint(Collection $details, Collection $spaces, ?string $itemName = null): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->wall_finish_type === 'paint' ||
            $s->ceiling_finish_type === 'paint',
        $itemName ?? 'دهان'
        );
    }

    protected function computeSanitary(Collection $details, Collection $spaces): float
    {
        $targets = [
            Space::TYPE_KITCHEN => 'kitchen_done',
            Space::TYPE_BATHROOM => 'bathroom_done',
            Space::TYPE_TOILET => 'toilet_done',
        ];

        $typesInProject = $spaces->pluck('type')->unique()->toArray();

        $total = 0;
        $done = 0;

        foreach ($targets as $type => $key) {
            if (!in_array($type, $typesInProject, true)) {
                continue;
            }

            $total++;
            if (($details[$key]->value ?? false) == true) {
                $done++;
            }
        }

        if ($total === 0) {
            return 0;
        }

        return ($done / $total) * 100;
    }

    protected function computeDoors(Collection $details): float
    {
        $total = (int) ($details['total_doors']->value ?? 0);
        $done  = (int) ($details['completed_doors']->value ?? 0);

        $kitchenCabinet = $details['kitchen_cabinet_done']->value ?? null;
        if ($kitchenCabinet !== null) {
            $total += 1;
            if ($kitchenCabinet == true) {
                $done += 1;
            }
        }

        if ($total == 0) return 0;

        return ($done / $total) * 100;
    }

    protected function computeAluminum(Collection $details): float
    {
        $total = (int) ($details['total_aluminum']->value ?? 0);
        $done  = (int) ($details['completed_aluminum']->value ?? 0);

        if ($total == 0) return 0;

        return ($done / $total) * 100;
    }

    protected function computeFinals(Collection $details): float
    {
        if (($details['all_finished']->value ?? false) == true) {
            return 100;
        }

        $total = (int) ($details['final_items_total']->value ?? 0);
        $done  = (int) ($details['final_items_completed']->value ?? 0);

        if ($total == 0) return 0;

        return ($done / $total) * 100;
    }

    protected function filterGypsum(Space $space): bool
    {
        return $space->ceiling_finish_type === 'gypsum'
            || $space->wall_finish_type === 'gypsum';
    }

    protected function filterPaint(Space $space): bool
    {
        return $space->wall_finish_type === 'paint'
            || $space->ceiling_finish_type === 'paint';
    }

    protected function filterCeramic(Space $space): bool
    {
        return $space->wall_finish_type === 'ceramic'
            || $space->ceiling_finish_type === 'ceramic';
    }

    protected function filterTile(Space $space): bool
    {
        return in_array($space->type, ['room', 'salon', 'kitchen']);
    }

    protected function filterRooms(Space $space): bool
    {
        return $space->wall_finish_type !== 'ceramic' || ($space->ceiling_finish_type === 'paint' || $space->wall_finish_type === 'paint' || $space->ceiling_finish_type === 'gypsum' || $space->wall_finish_type === 'gypsum');
    }

    protected function filterElectricity(Space $space): bool
    {
        return true; // كل الفراغات
    }

    protected function filterSanitary(Space $space): bool
    {
        return in_array($space->type, [
            Space::TYPE_KITCHEN,
            Space::TYPE_BATHROOM,
            Space::TYPE_TOILET,
        ], true);
    }

    private function getSpaceAreaForWorkItem(Space $space, string $workItemName): ?float
    {
        $area = 0.0;

        switch ($workItemName) {
            case 'سيراميك جدران / أسقف': // ceramic walls/ceilings
                if (($space->wall_finish_type ?? null) === 'ceramic' && $space->wall_area) {
                    $area += (float) $space->wall_area;
                }
                if (($space->ceiling_finish_type ?? null) === 'ceramic' && $space->ceiling_area) {
                    $area += (float) $space->ceiling_area;
                }
                break;

            case 'بلاط أرضيات': // floor tiles — use ceiling_area as floor proxy if floor_area absent
                if (isset($space->floor_area) && $space->floor_area > 0) {
                    $area += (float) $space->floor_area;
                } elseif (!is_null($space->ceiling_area) && $space->ceiling_area > 0) {
                    $area += (float) $space->ceiling_area;
                }
                break;

            case 'دهان': // paint
                if (($space->wall_finish_type ?? null) === 'paint' && $space->wall_area) {
                    $area += (float) $space->wall_area;
                }
                if (($space->ceiling_finish_type ?? null) === 'paint' && $space->ceiling_area) {
                    $area += (float) $space->ceiling_area;
                }
                break;

            case 'جبس بورد': // gypsum
                if (($space->ceiling_finish_type ?? null) === 'gypsum' && $space->ceiling_area) {
                    $area += (float) $space->ceiling_area;
                }
                if (($space->wall_finish_type ?? null) === 'gypsum' && $space->wall_area) {
                    $area += (float) $space->wall_area;
                }
                break;
            case 'تمديدات كهرباء':
                if (!empty($space->wall_area)) $area += (float) $space->wall_area;
                if (!empty($space->ceiling_area)) $area += (float) $space->ceiling_area;
                break;
            case 'تمديدات كهرباء سواد': // electricity — use wall+ceiling as proxy
                if (!empty($space->wall_area)) $area += (float) $space->wall_area;
                if (!empty($space->ceiling_area)) $area += (float) $space->ceiling_area;
                break;
            case 'تمديدات كهرباء بياض': // electricity — use wall+ceiling as proxy
                if (!empty($space->wall_area)) $area += (float) $space->wall_area;
                if (!empty($space->ceiling_area)) $area += (float) $space->ceiling_area;
                break;

            default:
                return null;
        }

        return $area > 0 ? $area : null;
    }



}

