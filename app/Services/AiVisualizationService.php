<?php

namespace App\Services;

use App\Http\Requests\GenerateImageRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiVisualizationService
{
    public function generate(GenerateImageRequest $request): array
    {
        // 1. استقبال صورة الغرفة الأساسية
        $room = $request->file('room_image');

        // 2. استقبال مصفوفة الصور المرجعية المتعددة (مثل: بلاط، دهان، ديكور)
        // تفترض الهيكلية أن الحقل في الـ Request يُرسل كمصفوفة باسم: reference_images[]
        $materials = $request->file('reference_images') ?? [];

        // 3. بناء الـ Prompt برمجياً لشرح ترتيب الصور للذكاء الاصطناعي
        $prompt = "You are an expert interior designer.\n\n";
        $prompt .= "The first image (Image 1) is the original room requiring renovation.\n";
        $prompt .= "The subsequent images are the design and material references provided in sequential order:\n";

        // ترقيم وشرح الصور المرجعية (صورة الغرفة هي 1، والمواد تبدأ من 2 فصاعداً)
        foreach ($materials as $index => $file) {
            $imageNumber = $index + 2;
            $prompt .= "- Image {$imageNumber} is a material/texture reference.\n";
        }

        $prompt .= "\nUser instruction detailing which material goes where:\n{$request->prompt}\n\n";

        $prompt .= "Rules:\n";
        $prompt .= "- Modify only the requested areas according to the user instructions.\n";
        $prompt .= "- Match and apply the texture and patterns from the reference images onto the corresponding surfaces accurately.\n";
        $prompt .= "- Keep furniture unchanged.\n";
        $prompt .= "- Keep walls, ceiling, windows and objects unchanged unless explicitly requested.\n";
        $prompt .= "- Keep the original perspective, lighting and shadows perfect.\n";
        $prompt .= "- Make the final blended result completely photorealistic.\n";

        // 4. تجهيز الطلب وإرفاق صورة الغرفة الأساسية كأول ملف
        $httpRequest = Http::timeout(300)
            ->withToken(config('services.openai.api_key'))
            ->attach(
                'image[]',
                file_get_contents($room->getRealPath()),
                $room->getClientOriginalName()
            );

        // 5. حلقة تكرارية لإرفاق كافة الصور المرجعية المرفوعة ديناميكياً داخل المصفوفة
        foreach ($materials as $file) {
            $httpRequest->attach(
                'image[]',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        // 6. إرسال الطلب النهائي إلى مسار الـ Edits
        $response = $httpRequest->post(
            'https://api.openai.com/v1/images/edits',
            [
                'model' => 'gpt-image-1',
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

        // 7. تخزين الصورة الناتجة في الـ Storage المحلي
        $fileName = 'generated/' . Str::random(40) . '.png';
        Storage::disk('public')->put($fileName, base64_decode($base64));

        return [
            'success' => true,
            'message' => 'تم إنشاء الصورة التخيلية بنجاح 🎨',
            'generated_image' => Storage::url($fileName)
        ];
    }
}
