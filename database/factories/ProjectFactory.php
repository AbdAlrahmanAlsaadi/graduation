<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * قائمة بمواقع وأحياء سورية مع إحداثياتها المرجعية
     */
    private const SYRIAN_LOCATIONS = [
        ['city' => 'دمشق - المزة',            'lat' => 33.5042, 'lng' => 36.2558],
        ['city' => 'دمشق - كفرسوسة',          'lat' => 33.4912, 'lng' => 36.2750],
        ['city' => 'دمشق - المالكي',           'lat' => 33.5220, 'lng' => 36.2780],
        ['city' => 'دمشق - أبو رمانة',         'lat' => 33.5185, 'lng' => 36.2840],
        ['city' => 'ريف دمشق - مشروع دمر',    'lat' => 33.5380, 'lng' => 36.2300],
        ['city' => 'ريف دمشق - جرمانا',        'lat' => 33.4850, 'lng' => 36.3450],
        ['city' => 'ريف دمشق - قدسيا',         'lat' => 33.5580, 'lng' => 36.2180],
        ['city' => 'حلب - الفرقان',           'lat' => 36.2085, 'lng' => 37.1195],
        ['city' => 'حلب - الشهباء',           'lat' => 36.2230, 'lng' => 37.1320],
        ['city' => 'حلب - الموكامبو',          'lat' => 36.2170, 'lng' => 37.1240],
        ['city' => 'حمص - الإنشاءات',         'lat' => 34.7180, 'lng' => 36.6950],
        ['city' => 'حمص - الغوطة',            'lat' => 34.7310, 'lng' => 36.7020],
        ['city' => 'حمص - الدبلان',           'lat' => 34.7290, 'lng' => 36.7110],
        ['city' => 'اللاذقية - المشروع السابع', 'lat' => 35.5380, 'lng' => 35.7920],
        ['city' => 'اللاذقية - الزراعة',       'lat' => 35.5220, 'lng' => 35.8050],
        ['city' => 'اللاذقية - الشاطئ الأزرق',  'lat' => 35.5870, 'lng' => 35.7420],
        ['city' => 'طرطوس - الميناء',          'lat' => 34.8940, 'lng' => 35.8750],
        ['city' => 'طرطوس - المشبكة',          'lat' => 34.8820, 'lng' => 35.8920],
        ['city' => 'حماة - الشريعة',          'lat' => 35.1380, 'lng' => 36.7450],
        ['city' => 'حماة - الحاضر',           'lat' => 35.1420, 'lng' => 36.7620],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectManager = $this->getOrCreateUserWithRole('project_manager');
        $assistantEngineer = $this->getOrCreateUserWithRole('assistant');
        $owner = fake()->boolean(70)
            ? $this->getOrCreateUserWithRole('project_owner')
            : null;

        // اختيار موقع عشوائي من سوريا مع إضافة إزاحة طفيفة للإحداثيات لتوليد مواقع مختلفة في نفس الحي
        $locationData = fake()->randomElement(self::SYRIAN_LOCATIONS);
        $latitude = $locationData['lat'] + fake()->randomFloat(6, -0.004, 0.004);
        $longitude = $locationData['lng'] + fake()->randomFloat(6, -0.004, 0.004);

        return [
            'name' => 'مشروع ' . fake()->randomElement(['شقة']) . ' ' . fake()->lastName(),
            'project_manager_id' => $projectManager->id,
            'assistant_engineer_id' => $assistantEngineer->id,
            'owner_id' => $owner?->id,
            'location' => $locationData['city'],
            'latitude' => (string) $latitude,
            'longitude' => (string) $longitude,
            'apartment_area' => fake()->randomFloat(2, 60, 450),
            'height' => fake()->randomFloat(2, 2.7, 3.5),
            'status' => fake()->randomElement([
                Project::STATUS_PLANNED, 
                Project::STATUS_ONGOING, 
                Project::STATUS_COMPLETED
            ]),
            'created_by' => $projectManager->id,
            'updated_by' => $projectManager->id,
        ];
    }

    private function getOrCreateUserWithRole(string $roleName): User
    {
        $user = User::role($roleName)->inRandomOrder()->first();
        if ($user) {
            return $user;
        }

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }
}