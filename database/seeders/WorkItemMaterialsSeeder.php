<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkItemMaterialsSeeder extends Seeder
{
    private const WORK_ITEM_TYPES = [
        'ملابن الأبواب',
        'تمديدات صحية سواد',
        'تمديدات كهرباء سواد',
        'طينة / لياسة',
        'سيراميك جدران / أسقف',
        'جبس بورد',
        'بلاط أرضيات',
        'ألمنيوم وأبجورات',
        'أبواب ونجارة',
        'دهان',
        'تمديدات كهرباء بياض',
        'تمديدات صحية بياض',
        'ديكورات',
    ];

    private const WORK_ITEM_MATERIALS = [
        'ملابن الأبواب' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'فوم تثبيت ملابن'],
            ['material' => 'ملابن رخام مع تركيب'],
            ['material' => 'ملابن خشب مع تركيب'],
            ['material' => 'براغي عامة'],
        ],
        'تمديدات كهرباء سواد' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'أنبوب تمديد كهربائي (تيب)'],
            ['material' => 'علبة كهرباء'],
            ['material' => 'سلك كهرباء'],
            ['material' => 'علبة قواطع'],
            ['material' => 'براغي عامة'],
        ],
        'تمديدات كهرباء بياض' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'إسمنت أبيض'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'سلك كهرباء'],
            ['material' => 'بريز كهرباء'],
            ['material' => 'مفتاح كهرباء'],
            ['material' => 'لمبة'],
            ['material' => 'سوكة لمبة'],
            ['material' => 'سبوت إنارة'],
            ['material' => 'علبة قواطع'],
            ['material' => 'قاطع كهربائي'],
            ['material' => 'جرس باب'],
            ['material' => 'براغي عامة'],
        ],
        'تمديدات صحية سواد' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'أنبوب مياه تغذية'],
            ['material' => 'أنبوب صرف صحي'],
            ['material' => 'وصلات وأكواع صحية'],
            ['material' => 'محبس مياه'],
            ['material' => 'بلوعة'],
            ['material' => 'شريط تفلون'],
            ['material' => 'براغي عامة'],
        ],
        'تمديدات صحية بياض' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'إسمنت أبيض'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'محبس مياه'],
            ['material' => 'بلوعة'],
            ['material' => 'مغسلة'],
            ['material' => 'خرطوم تصريف مغسلة'],
            ['material' => 'مجلى مع تركيب'],
            ['material' => 'حوض مجلى'],
            ['material' => 'طقم دوش'],
            ['material' => 'شطاف مع خرطوم'],
            ['material' => 'مرحاض إفرنجي'],
            ['material' => 'مرحاض عربي'],
            ['material' => 'حنفية عادية'],
            ['material' => 'خلاط مياه'],
            ['material' => 'حنفية مغسلة'],
            ['material' => 'سخان مياه'],
            ['material' => 'سيفون مغسلة'],
            ['material' => 'شريط تفلون'],
            ['material' => 'سيليكون صحي'],
            ['material' => 'براغي عامة'],
        ],
        'طينة / لياسة' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'فتحة صوبيا'],
            ['material' => 'براغي عامة'],
        ],
        'بلاط أرضيات' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'إسمنت أبيض'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'سيراميك أرضيات'],
            ['material' => 'لاصق سيراميك'],
            ['material' => 'فواصل سيراميك'],
            ['material' => 'عدسية'],
            ['material' => 'براغي عامة'],
        ],
        'سيراميك جدران / أسقف' => [
            ['material' => 'إسمنت أسود'],
            ['material' => 'إسمنت أبيض'],
            ['material' => 'رمل'],
            ['material' => 'مياه'],
            ['material' => 'سيراميك جدران وأسقف'],
            ['material' => 'لاصق سيراميك'],
            ['material' => 'فواصل سيراميك'],
            ['material' => 'براغي عامة'],
        ],
        'جبس بورد' => [
            ['material' => 'لوح جبس بورد'],
            ['material' => 'قائم معدني للجبس'],
            ['material' => 'مسار معدني للجبس'],
            ['material' => 'علاقة جبس بورد'],
            ['material' => 'شريط فواصل جبس'],
            ['material' => 'معجونة فواصل جبس'],
            ['material' => 'براغي عامة'],
        ],
        'دهان' => [
            ['material' => 'دهان'],
            ['material' => 'معجونة دهان'],
            ['material' => 'رول دهان'],
            ['material' => 'عصاية رول دهان'],
            ['material' => 'فرشاية دهان'],
            ['material' => 'ورق زجاج'],
            ['material' => 'شريط حماية دهان'],
            ['material' => 'نايلون حماية'],
            ['material' => 'تنر دهان'],
            ['material' => 'براغي عامة'],
        ],
        'أبواب ونجارة' => [
            ['material' => 'ملابن خشب مع تركيب'],
            ['material' => 'باب خشب مع تركيب'],
            ['material' => 'قفل باب'],
            ['material' => 'جوزة قفل'],
            ['material' => 'طقم مسكات باب'],
            ['material' => 'مسكة باب خارجية'],
            ['material' => 'مفصلات باب'],
            ['material' => 'دقاقة باب'],
            ['material' => 'عين باب سحرية'],
            ['material' => 'مضخة باب'],
            ['material' => 'مصد باب'],
            ['material' => 'فوم تثبيت ملابن'],
            ['material' => 'صندوق أباجور مع تركيب'],
            ['material' => 'خزن مطبخ مع تركيب'],
            ['material' => 'حماية شباك حديد مع تركيب'],
            ['material' => 'حماية باب حديد مع تركيب'],
            ['material' => 'دهان'],
            ['material' => 'معجونة دهان'],
            ['material' => 'ورق زجاج'],
            ['material' => 'تنر دهان'],
            ['material' => 'مجلى مع تركيب'],
            ['material' => 'براغي عامة'],
        ],
        'ألمنيوم وأبجورات' => [
            ['material' => 'ملابن رخام مع تركيب'],
            ['material' => 'صندوق أباجور مع تركيب'],
            ['material' => 'باب ألمنيوم مع تركيب'],
            ['material' => 'شباك ألمنيوم مع تركيب'],
            ['material' => 'أباجور مع تركيب'],
            ['material' => 'محرك أباجور'],
            ['material' => 'دهان'],
            ['material' => 'معجونة دهان'],
            ['material' => 'تنر دهان'],
            ['material' => 'براغي عامة'],
        ],
        'ديكورات' => [
            ['material' => 'فتحة صوبيا'],
            ['material' => 'دهان'],
            ['material' => 'معجونة دهان'],
            ['material' => 'رول دهان'],
            ['material' => 'عصاية رول دهان'],
            ['material' => 'فرشاية دهان'],
            ['material' => 'ورق زجاج'],
            ['material' => 'شريط حماية دهان'],
            ['material' => 'نايلون حماية'],
            ['material' => 'بريز كهرباء'],
            ['material' => 'مفتاح كهرباء'],
            ['material' => 'لمبة'],
            ['material' => 'سوكة لمبة'],
            ['material' => 'عدسية'],
            ['material' => 'سبوت إنارة'],
            ['material' => 'قاطع كهربائي'],
            ['material' => 'جرس باب'],
            ['material' => 'سيليكون صحي'],
            ['material' => 'مضخة باب'],
            ['material' => 'مصد باب'],
            ['material' => 'ثريا'],
            ['material' => 'بديل خشب'],
            ['material' => 'بديل رخام'],
            ['material' => 'مراية'],
            ['material' => 'خزانة ديكور مع تركيب'],
            ['material' => 'لاصق ديكور'],
            ['material' => 'زوائد تثبيت'],
            ['material' => 'بروفيل ديكور'],
            ['material' => 'براغي عامة'],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $materialNames = collect(self::WORK_ITEM_MATERIALS)
                ->flatten(1)
                ->pluck('material')
                ->unique()
                ->toArray();

            $materialMap = Material::whereIn('name', $materialNames)
                ->pluck('id', 'name')
                ->toArray();

            $pivotRows = [];
            $now = now();

            foreach (self::WORK_ITEM_MATERIALS as $workItemName => $materials) {
                if (! in_array($workItemName, self::WORK_ITEM_TYPES, true)) {
                    continue;
                }

                foreach ($materials as $entry) {
                    $materialId = $materialMap[$entry['material']] ?? null;
                    if (! $materialId) {
                        continue;
                    }

                    $pivotRows[] = [
                        'work_item_name' => $workItemName,
                        'material_id' => $materialId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('work_item_materials')
                ->whereIn('work_item_name', self::WORK_ITEM_TYPES)
                ->delete();

            if (! empty($pivotRows)) {
                DB::table('work_item_materials')->insert($pivotRows);
            }
        });
    }
}