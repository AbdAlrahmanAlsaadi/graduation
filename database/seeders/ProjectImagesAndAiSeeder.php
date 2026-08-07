<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\AiVisualization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProjectImagesAndAiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب أحدث صورة أصلية (من رفعك عبر Postman)
        $latestImage = ProjectImage::latest()->first();
        if (!$latestImage) {
            $this->command->error('⚠️ لا توجد صورة أصلية موجودة مسبقاً. قم برفع صورة أولاً.');
            return;
        }
        $sampleImagePath = $latestImage->image; // مثال: project-images/xxx.jpg

        // 2. جلب أحدث صورة تخيلية (من رفعك عبر Postman)
        $latestAi = AiVisualization::latest()->first();
        if (!$latestAi) {
            $this->command->error('⚠️ لا توجد صورة تخيلية موجودة مسبقاً. قم بإنشاء صورة AI أولاً.');
            return;
        }
        $sampleAiImagePath = $latestAi->generated_image; // مثال: generated/xxx.png

        // 3. التأكد من وجود الصور فعلياً
        if (!Storage::disk('public')->exists($sampleImagePath)) {
            $this->command->error('⚠️ الصورة الأصلية غير موجودة: ' . $sampleImagePath);
            return;
        }

        if (!Storage::disk('public')->exists($sampleAiImagePath)) {
            $this->command->error('⚠️ الصورة التخيلية غير موجودة: ' . $sampleAiImagePath);
            return;
        }

        // 4. جلب جميع المشاريع
        $projects = Project::all();

        foreach ($projects as $project) {
            // --- إضافة صورتين قبل الاكساء لكل مشروع ---
            for ($i = 1; $i <= 2; $i++) {
                $newImagePath = 'project-images/project_' . $project->id . '_before_' . $i . '.' . pathinfo($sampleImagePath, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($sampleImagePath, $newImagePath);

                $projectImage = ProjectImage::create([
                    'project_id' => $project->id,
                    'created_by' => 1,
                    'name' => 'صورة قبل التنفيذ ' . $i . ' - ' . $project->name,
                    'image' => $newImagePath,
                ]);

                // --- إضافة صورتين تخيليتين (AI) لكل صورة أصلية ---
                for ($j = 1; $j <= 2; $j++) {
                    $newAiPath = 'generated/ai_' . $project->id . '_' . $i . '_' . $j . '.' . pathinfo($sampleAiImagePath, PATHINFO_EXTENSION);
                    Storage::disk('public')->copy($sampleAiImagePath, $newAiPath);

                    AiVisualization::create([
                        'project_image_id' => $projectImage->id,
                        'reference_images' => json_encode([$newImagePath, 'sample_reference_' . $j . '.jpg']),
                        'generated_image' => $newAiPath,
                    ]);
                }
            }
        }

        $this->command->info('✅ تم إضافة الصور قبل الاكساء والصور التخيلية لكل المشاريع بنجاح!');
    }
}
