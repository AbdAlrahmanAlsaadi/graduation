<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectImageRequest;
use App\Http\Resources\ProjectImageResource;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Services\ProjectImageService;
use App\Http\Resources\AiVisualizationResource;
use App\Models\AiVisualization;

class ProjectImageController extends Controller
{
    public function __construct(
        private ProjectImageService $service
    ) {}

    public function store(StoreProjectImageRequest $request)
    {
        $image = $this->service->store($request);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الصورة بنجاح',
            'data' => new ProjectImageResource($image),
        ]);
    }

    public function destroy($id)
    {
        return $this->service->destroy($id);

    }




    public function index(Project $project)
    {
        $images = $this->service->index($project);

        return response()->json([
            'success' => true,
            'data' => ProjectImageResource::collection($images)
        ]);
    }



    public function index2($projectid)
    {
        $visualizations = AiVisualization::where('project_image_id', $projectid)->latest()->get();

        return response()->json([
            'success' => true,
            'data' => AiVisualizationResource::collection($visualizations),
        ]);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    
    }
}
