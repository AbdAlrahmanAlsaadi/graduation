<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkItemProgressRequest;
use App\Services\WorkItemProgressService;
use App\Http\Resources\WorkItemProgressResource;
use App\Http\Resources\ProjectProgressResource;
use App\Models\Project;
use App\Models\Space;
use App\Models\WorkItem;
use App\Http\Responses\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Throwable;

class WorkItemProgressController extends Controller
{
    public function __construct(private WorkItemProgressService $service) {}

    /* ============================================================
       UPDATE PROGRESS (JSON كامل)
       ============================================================ */
        public function update(UpdateWorkItemProgressRequest $request, Project $project, WorkItem $workItem)
    {
        try {

            if ($workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $result = $this->service->updateProgress($project, $workItem, $request->validated());

            $workItemFresh = $result['work_item']->load('details', 'progressPhotos');
            $workItemFresh->percent = $result['percent'];

            // Compute project percent
            $projectPercent = $this->service->computeProjectPercent($project);

            $project->project_percent = $projectPercent;

            // Load ONLY active work items
            $items = $project->workItems()
                ->where('is_active', true)
                ->with('details')
                ->get()
                ->map(function ($item) use ($workItemFresh) {

                    if ($item->id === $workItemFresh->id) {
                        // استخدم النسبة المحسوبة مسبقاً
                        $item->percent = $workItemFresh->percent;
                    } else {
                        // احسب فقط للبنود الأخرى
                        $item->percent = $this->service->computeWorkItemPercent($item);
                    }

                    return $item;
                });

            $project->setRelation('workItems', $items);

            return Response::success('Progress updated successfully', [
                'work_item' => new WorkItemProgressResource($workItemFresh),
                'project'   => new ProjectProgressResource($project),
            ]);
        } catch (Throwable $e) {
            return Response::error('Failed to update progress. ' . $e->getMessage(), 500);
        }
    }

    /* ============================================================
       UPDATE SINGLE ROOM STATUS (NEW ENDPOINT)
       ============================================================ */
    public function updateRoom(Request $request, Project $project, WorkItem $workItem, int $spaceId)
    {
        try {
            if ($workItem->project_id !== $project->id) {
                return Response::error('Work item does not belong to this project.', 404);
            }

            $validated = $request->validate([
                'completed' => ['required', 'boolean'],
                'photos' => ['sometimes', 'array'],
                'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);

            $photos = $this->extractPhotos($request);
            if (!empty($photos)) {
                $validated['photos'] = $photos;
            }

            $result = $this->service->updateRoomStatus(
                $project,
                $workItem,
                $spaceId,
                $validated['completed'],
                $validated['photos'] ?? []
            );

            $workItemFresh = $result['work_item']->load('details', 'progressPhotos');
            $workItemFresh->percent = $result['percent'];

            // Compute project percent
            $projectPercent = $this->service->computeProjectPercent($project);
            $project->project_percent = $projectPercent;

            // Load ONLY active work items
            $items = $project->workItems()
                ->where('is_active', true)
                ->with('details')
                ->get()
                ->map(function ($item) use ($workItemFresh) {
                    if ($item->id === $workItemFresh->id) {
                        $item->percent = $workItemFresh->percent;
                    } else {
                        $item->percent = $this->service->computeWorkItemPercent($item);
                    }
                    return $item;
                });

            $project->setRelation('workItems', $items);

            return Response::success('Room progress updated', [
                'work_item' => new WorkItemProgressResource($workItemFresh),
                'project'   => new ProjectProgressResource($project),
            ]);

        } catch (Throwable $e) {
            return Response::error('Failed to update room progress. ' . $e->getMessage(), 500);
        }
    }

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function extractPhotos(Request $request): array
    {
        $photos = $request->file('photos');
        if ($photos === null) {
            $files = $request->allFiles();
            if (array_key_exists('photos', $files)) {
                $photos = $files['photos'];
            }
        }

        if ($photos instanceof \Illuminate\Http\UploadedFile) {
            return [$photos];
        }

        return is_array($photos) ? $photos : [];
    }

    /* ============================================================
       PROJECT PROGRESS
       ============================================================ */
    public function projectProgress(Project $project)
    {
        try {
            $projectPercent = $this->service->computeProjectPercent($project);
            $project->project_percent = $projectPercent;

            // Load ONLY active work items
            $items = $project->workItems()
                ->where('is_active', true)
                ->with('details')
                ->get()
                ->map(function ($item) {
                    $item->percent = $this->service->computeWorkItemPercent($item);
                    return $item;
                });

            $project->setRelation('workItems', $items);

            return Response::success('Project progress fetched', new ProjectProgressResource($project));

        } catch (Throwable $e) {
            return Response::error('Failed to fetch project progress. ' . $e->getMessage(), 500);
        }
    }
}
