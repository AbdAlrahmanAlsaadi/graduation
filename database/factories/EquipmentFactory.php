<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        $types = [
            'سقالة معدنية',
            'رافعة مواد كهربائية',
            'خلاطة إسمنت',
            'مضخة إسمنت',
            'ماكينة قص البلاط',
            'ماكينة تلميع الرخام والجرانيت',
            'ماكينة جلي الأرضيات',
            'ماكينة صنفرة الجدران',
            'ماكينة رش الدهان',
            'ضاغط هواء',
            'مولدة كهرباء',
            '_hzaz خرسانة',
            'قاطع خرسانة',
            'صاروخ قص وجلخ',
            'دريل كهربائي',
            'شنيور',
            'ماكينة تخريم',
            'ماكينة لحام',
            'رافعة شوكية',
            'عربة نقل مواد',
            'ماكينة قص الحديد',
            'ماكينة ثني الحديد',
            'جهاز ليزر ميزان',
            'جهاز قياس مسافات ليزري',
            'مكنسة صناعية',
            'ماكينة غسيل ضغط عالي',
            'مضخة مياه',
            'خزان مياه متنقل'
        ];

        $type = fake()->randomElement($types);

        return [

            'name' => $type . ' ' . fake()->numberBetween(100, 999),

            'type' => $type,

            'identifier_no' => $this->generateIdentifierNo(),

            'status' => fake()->randomElement([
                'Available',
                'Maintenance',
            ]),
        ];
    }

    private function generateIdentifierNo(): string
    {
        return 'EQ-' . fake()->unique()->numerify('######');
    }
}
