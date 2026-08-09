<?php

namespace App\Services\AI;

use App\Http\Requests\GenerateImageRequest;
use App\Models\AiVisualization;
use App\Models\ProjectImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiVisualizationService
{
    public function generate(GenerateImageRequest $request): array
    {
        set_time_limit(300);

        // الصورة الأصلية للمشروع
        $projectImage = ProjectImage::findOrFail($request->project_image_id);

        $roomPath = storage_path('app/public/' . $projectImage->image);

        // الصور المرجعية
        $materials = $request->file('reference_images') ?? [];

        $referenceImages = [];

        $prompt = "You are an expert interior designer.\n\n";
        $prompt .= "The first image (Image 1) is the original room requiring renovation.\n";
        $prompt .= "The subsequent images are the design and material references provided in sequential order:\n";

        foreach ($materials as $index => $file) {

            // حفظ الصورة المرجعية
            $path = $file->store('ai-references', 'public');

            $referenceImages[] = $path;

            $imageNumber = $index + 2;

            $prompt .= "- Image {$imageNumber} is a material/texture reference.\n";
        }

        $prompt .= "\nUser instruction detailing which material goes where:\n{$request->prompt}\n\n";

        $prompt .= "Rules:\n";
        $prompt .= "- Modify only the requested areas according to the user instructions.\n";
        $prompt .= "- Match and apply the texture and patterns from the reference images accurately.\n";
        $prompt .= "- Keep furniture unchanged.\n";
        $prompt .= "- Keep walls, ceiling, windows and objects unchanged unless explicitly requested.\n";
        $prompt .= "- Preserve the original lighting, shadows and perspective.\n";
        $prompt .= "- Produce a completely photorealistic result.\n";

        $httpRequest = Http::timeout(300)
            ->withToken(config('services.openai.api_key'))
            ->attach(
                'image[]',
                file_get_contents($roomPath),
                basename($roomPath)
            );

        foreach ($materials as $file) {

            $httpRequest->attach(
                'image[]',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        $response = $httpRequest->post(
            'https://api.openai.com/v1/images/edits',
            [
                'model' => 'gpt-image-2',
                'prompt' => $prompt,
                'size' => '1024x1024',
                'quality' => 'medium',
            ]
        );

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        $base64 = $data['data'][0]['b64_json'];

        $generatedImage = 'generated/' . Str::random(40) . '.png';

        Storage::disk('public')->put(
            $generatedImage,
            base64_decode($base64)
        );

        $visualization = AiVisualization::create([
            'project_image_id' => $projectImage->id,
            'reference_images' => $referenceImages,
            'generated_image' => $generatedImage,
        ]);

        return [
            'success' => true,
            'message' => 'تم إنشاء الصورة التخيلية بنجاح 🎨',
            'data' => [
                'id' => $visualization->id,
                'generated_image' => Storage::url($visualization->generated_image),
            ]
        ];
    }
}
