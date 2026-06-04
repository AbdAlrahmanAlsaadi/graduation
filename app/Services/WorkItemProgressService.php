<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkItemDetail;
use App\Models\ProgressPhoto;
use App\Models\Space;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        if (array_key_exists('photos', $data)) {
            $photos = $data['photos'] ?? [];
            if ($photos instanceof \Illuminate\Http\UploadedFile) {
                $photos = [$photos];
            }
            if (!is_array($photos)) {
                $photos = [];
            }
            $this->storeProgressPhotos($project, $item, $photos);
            unset($data['photos']);
        }

        foreach ($data as $key => $value) {

            // special case: rooms_status → merge
            if ($key === 'rooms_status') {
                $old = WorkItemDetail::where('work_item_id', $item->id)
                    ->where('key', 'rooms_status')
                    ->first();

                $oldValue = $old ? json_decode($old->value, true) : [];
                if (!is_array($oldValue)) $oldValue = [];

                $filtered = [];

                foreach ($value as $spaceId => $completed) {
                    // 1) تحقق أن الغرفة موجودة بالمشروع
                    $space = Space::where('id', $spaceId)
                        ->where('project_id', $project->id)
                        ->first();

                    if (!$space) {
                        continue; // تجاهل الغرفة
                    }

                    // 2) تحقق أن الغرفة ينطبق عليها البند
                    $type = $this->logic['mapping'][$item->name] ?? null;
                    $method = $type ? 'filter' . ucfirst($type) : null;

                    if ($method && method_exists($this, $method)) {
                        if (!$this->{$method}($space)) {
                            continue; // تجاهل الغرفة إذا ما بتنطبق
                        }
                    }

                    // 3) الغرفة صالحة → أضفها
                    $filtered[$spaceId] = $completed;
                }

                // دمج القديم مع الجديد
                $merged = array_merge($oldValue, $filtered);

                WorkItemDetail::updateOrCreate(
                    ['work_item_id' => $item->id, 'key' => 'rooms_status'],
                    ['value' => json_encode($merged)]
                );
                continue;
            }

            // numeric progress
            WorkItemDetail::updateOrCreate(
                ['work_item_id' => $item->id, 'key' => $key],
                ['value' => $value]
            );
        }

        $percent = $this->computeWorkItemPercent($item);

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
        if (!empty($photos)) {
            $this->storeProgressPhotos($project, $item, $photos);
        }

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
                abort(422, "This space does not apply to this work item.");
            }
        }
    
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
    
        return [
            'work_item' => $item->refresh()->load('progressPhotos'),
            'percent'   => $percent,
        ];
    }

    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $photos
     */
    private function storeProgressPhotos(Project $project, WorkItem $item, array $photos): void
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

        $details = $item->details()->get()->keyBy('key');
        $spaces  = Space::where('project_id', $item->project_id)->get();

        return round(
            $this->{$method}($details, $spaces),
            $this->logic['settings']['percent_precision']
        );
    }

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

        foreach ($items as $item) {
            $percent = $this->computeWorkItemPercent($item);
            $sum += ($percent * $item->weight);
        }

        return round($sum / $totalWeight, $this->logic['settings']['percent_precision']);
    }

    /* =========================================================================
       GENERIC ROOMS_STATUS STRATEGY
       ========================================================================= */

    protected function computeRoomsStatus(Collection $details, Collection $spaces, callable $filter): float
    {
        // 1) الغرف التي ينطبق عليها البند
        $filteredSpaces = $spaces->filter($filter);
        $validSpaceIds = $filteredSpaces->pluck('id')->toArray();

        if (empty($validSpaceIds)) {
            return 0;
        }

        // 2) rooms_status
        $statusJson = $details['rooms_status']->value ?? null;
        $status = $statusJson ? json_decode($statusJson, true) : [];

        if (!is_array($status)) {
            $status = [];
        }

        // 3) احسب الغرف المنجزة فقط إذا كانت ضمن validSpaceIds
        $done = 0;
        foreach ($validSpaceIds as $id) {
            if (isset($status[$id]) && $status[$id] === true) {
                $done++;
            }
        }

        // 4) total = عدد الغرف التي ينطبق عليها البند
        $total = count($validSpaceIds);

        return ($done / $total) * 100;
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

    protected function computeElectricity(Collection $details, Collection $spaces): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) => true);
    }

    protected function computeRooms(Collection $details, Collection $spaces): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->wall_finish_type !== 'ceramic'
        );
    }

    protected function computeTile(Collection $details, Collection $spaces): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) => true);
    }

    protected function computeCeramic(Collection $details, Collection $spaces): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->wall_finish_type === 'ceramic' ||
            $s->ceiling_finish_type === 'ceramic'
        );
    }

    protected function computeGypsum(Collection $details, Collection $spaces): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->ceiling_finish_type === 'gypsum' || 
            $s->wall_finish_type === 'gypsum'
        );
    }

    protected function computePaint(Collection $details, Collection $spaces): float
    {
        return $this->computeRoomsStatus($details, $spaces, fn($s) =>
            $s->wall_finish_type === 'paint' ||
            $s->ceiling_finish_type === 'paint'
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
        return $space->wall_finish_type !== 'ceramic';
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

}

