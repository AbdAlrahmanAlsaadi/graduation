<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) جلب المستخدمين لكل دور
        $projectManagers = User::role('project_manager')->get();
        $assistants      = User::role('assistant')->get();
        $owners          = User::role('project_owner')->get();

        if ($projectManagers->isEmpty() || $assistants->isEmpty() || $owners->isEmpty()) {
            $this->command->warn('يرجى التأكد من تشغيل RolesAndPermissionsSeeder أولاً لتوليد المستخدمين.');
            return;
        }

        // 2) إنشاء المشاريع
        $projects = Project::factory()
            ->count(5)
            ->create();

        // 3) توزيع المستخدمين على المشاريع
        foreach ($projects as $index => $project) {
            // توزيع دوري (Round-Robin) لضمان تغطية جميع المستخدمين
            $pm        = $projectManagers[$index % $projectManagers->count()];
            $assistant = $assistants[$index % $assistants->count()];
            $owner     = $owners[$index % $owners->count()];

            // تحديث الحقول الأساسية في جدول projects
            $project->update([
                'project_manager_id'    => $pm->id,
                'assistant_engineer_id' => $assistant->id,
                'owner_id'              => $owner->id,
                'created_by'            => $pm->id,
                'updated_by'            => $pm->id,
            ]);

            // إسناد المهندسين عبر دالة assignEngineer (Pivot / Activity)
            $project->assignEngineer($pm, 'project_manager', now());
            $project->assignEngineer($assistant, 'assistant', now());
        }

        // 4) استدعاء السيدرز التابعة
        if ($projects->isNotEmpty()) {
            $this->call([
                SpacesSeeder::class,
                WorkItemsSeeder::class,
            ]);
        }
    }
}