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
        $faker = \Faker\Factory::create();

        $imageFiles = Storage::disk('public')->files('project-images');
        $sampleImagePath = !empty($imageFiles) ? $imageFiles[0] : null;

        $aiFiles = Storage::disk('public')->files('generated');
        $sampleAiPath = !empty($aiFiles) ? $aiFiles[0] : null;


        if (!$sampleImagePath) {
            $this->command->warn('⚠️ لا توجد صور في مجلد project-images، سيتم استخدام روابط وهمية.');
        }
        if (!$sampleAiPath) {
            $this->command->warn('⚠️ لا توجد صور في مجلد generated، سيتم استخدام روابط وهمية.');
        }

        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('⚠️ لا توجد مشاريع في قاعدة البيانات.');
            return;
        }

        foreach ($projects as $project) {
            // 5. إضافة صورتين قبل الاكساء لكل مشروع
            for ($i = 1; $i <= 2; $i++) {
                // --- الصورة الأصلية (قبل الاكساء) ---
                if ($sampleImagePath) {
                    $newImagePath = 'project-images/project_' . $project->id . '_before_' . $i . '.jpg';
                    Storage::disk('public')->copy($sampleImagePath, $newImagePath);
                } else {
                    $newImagePath = $faker->imageUrl(800, 600, 'construction', true, 'before_' . $project->id . '_' . $i);
                }

                $projectImage = ProjectImage::create([
                    'project_id' => $project->id,
                    'created_by' => 1,
                    'name' => 'صورة قبل التنفيذ ' . $i . ' - ' . $project->name,
                    'image' => $newImagePath,
                ]);

                for ($j = 1; $j <= 2; $j++) {

                    if ($sampleAiPath) {
                        $newAiPath = 'generated/ai_' . $project->id . '_' . $i . '_' . $j . '.png';
                        Storage::disk('public')->copy($sampleAiPath, $newAiPath);
                    } else {
                        $newAiPath = $faker->imageUrl(800, 600, 'building', true, 'ai_' . $project->id . '_' . $i . '_' . $j);
                    }


                    AiVisualization::create([
                        'project_image_id' => $projectImage->id,
                        'reference_images' => json_encode([
                            $newImagePath,
                            'reference_' . $j . '.jpg'
                        ]),
                        'generated_image' => $newAiPath,
                    ]);
                }
            }
        }

        $this->command->info('✅ تم إضافة الصور لكل المشاريع بنجاح!');
        $this->command->info('📸 عدد المشاريع: ' . $projects->count());
        $this->command->info('🖼️ عدد الصور الأصلية: ' . ($projects->count() * 2));
        $this->command->info('🤖 عدد الصور التخيلية: ' . ($projects->count() * 4));
    }
}
