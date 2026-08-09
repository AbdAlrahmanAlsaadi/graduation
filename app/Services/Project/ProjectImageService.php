<?php

namespace App\Services\Project;

use App\Http\Requests\StoreProjectImageRequest;
use App\Models\AiVisualization;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
class ProjectImageService
{
    public function store(StoreProjectImageRequest $request): ProjectImage
    {
        $image = $request->file('image');

        $path = $image->store('project-images', 'public');

        return ProjectImage::create([
            'project_id' => $request->project_id,
            'created_by' => auth()->id(),
            'name' => $request->name,
            'image' => $path,
        ]);
    }


    public function destroy($id)
    {
        $projectImage = ProjectImage::find($id);

        if (!$projectImage) {
            return response()->json([
                'success' => false,
                'message' => 'الصورة غير موجودة.'
            ], 404);
        }

        if (Storage::disk('public')->exists($projectImage->image)) {
            Storage::disk('public')->delete($projectImage->image);
        }

        $projectImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصورة بنجاح.'
        ]);
    }

    public function index(Project $project)
    {
        return $project->images()->latest()->get();
    }

    public function index2(ProjectImage $projectImage)
    {
        return $projectImage
            ->visualizations()
            ->latest()
            ->get();
    }



    public function delete(int $id)
    {
        $visualization = AiVisualization::find($id);

        if (!$visualization) {
            return response()->json([
                'success' => false,
                'message' => 'الصورة غير موجودة.'
            ], 404);
        }

        if (
            $visualization->generated_image &&
            Storage::disk('public')->exists($visualization->generated_image)
        ) {

            Storage::disk('public')->delete($visualization->generated_image);
        }

        if (!empty($visualization->reference_images)) {

            foreach ($visualization->reference_images as $image) {

                if (Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
            }
        }

        $visualization->delete();

        return [
            'success' => true,
            'message' => 'تم حذف التصميم بنجاح.',
        ];
    }
}
