<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkItemMaterialsSeeder extends Seeder
{
    private const WORK_ITEM_TYPES = [
        'ملابن الأبواب',
        'تمديدات كهرباء',
        'تمديدات صحية',
        'طينة / لياسة',
        'بلاط أرضيات',
        'سيراميك جدران / أسقف',
        'جبس بورد',
        'دهان',
        'أبواب ونجارة',
        'ألمنيوم وأبجورات',
        'تشطيبات نهائية',
    ];

    private const WORK_ITEM_MATERIALS = [
        'ملابن الأبواب' => [
            ['material' => 'Cement', 'sort_order' => 1, 'is_required' => true],
            ['material' => 'Sand',   'sort_order' => 2, 'is_required' => true],
            ['material' => 'Water',  'sort_order' => 3, 'is_required' => true],
        ],
        'تمديدات كهرباء' => [
            ['material' => 'Electrical Tape',  'sort_order' => 1, 'is_required' => true],
            ['material' => 'Electrical Boxes', 'sort_order' => 2, 'is_required' => true],
            ['material' => 'Wires',            'sort_order' => 3, 'is_required' => true],
            ['material' => 'Power Outlets',    'sort_order' => 4, 'is_required' => true],
            ['material' => 'Switches',         'sort_order' => 5, 'is_required' => true],
            ['material' => 'Lighting Fixture', 'sort_order' => 6, 'is_required' => true],
        ],
        'تمديدات صحية' => [
            ['material' => 'Cement', 'sort_order' => 1, 'is_required' => true],
            ['material' => 'Sand',   'sort_order' => 2, 'is_required' => true],
            ['material' => 'Water',  'sort_order' => 3, 'is_required' => true],
            ['material' => 'Faucet', 'sort_order' => 4, 'is_required' => true],
            ['material' => 'Boiler', 'sort_order' => 5, 'is_required' => true],
        ],
        'طينة / لياسة' => [
            ['material' => 'Cement', 'sort_order' => 1, 'is_required' => true],
            ['material' => 'Sand',   'sort_order' => 2, 'is_required' => true],
            ['material' => 'Water',  'sort_order' => 3, 'is_required' => true],
        ],
        'بلاط أرضيات' => [
            ['material' => 'Cement',            'sort_order' => 1, 'is_required' => true],
            ['material' => 'Sand',              'sort_order' => 2, 'is_required' => true],
            ['material' => 'Water',             'sort_order' => 3, 'is_required' => true],
            ['material' => 'Floor Tiles',       'sort_order' => 4, 'is_required' => true],
            ['material' => 'Ceramic Adhesive',  'sort_order' => 5, 'is_required' => true],
            ['material' => 'Ceramic Spacers',   'sort_order' => 6, 'is_required' => false],
        ],
        'سيراميك جدران / أسقف' => [
            ['material' => 'Wall Ceramic',     'sort_order' => 1, 'is_required' => true],
            ['material' => 'Ceiling Ceramic',  'sort_order' => 2, 'is_required' => true],
            ['material' => 'Ceramic Adhesive', 'sort_order' => 3, 'is_required' => true],
            ['material' => 'Ceramic Spacers',  'sort_order' => 4, 'is_required' => false],
        ],
        'جبس بورد' => [
            ['material' => 'Gypsum Board', 'sort_order' => 1, 'is_required' => true],
        ],
        'دهان' => [
            ['material' => 'Paint',     'sort_order' => 1, 'is_required' => true],
            ['material' => 'Putty',     'sort_order' => 2, 'is_required' => true],
            ['material' => 'Roller',    'sort_order' => 3, 'is_required' => true],
            ['material' => 'Brush',     'sort_order' => 4, 'is_required' => true],
            ['material' => 'Sandpaper', 'sort_order' => 5, 'is_required' => true],
        ],
        'أبواب ونجارة' => [
            ['material' => 'Paint', 'sort_order' => 1, 'is_required' => false],
            ['material' => 'Putty', 'sort_order' => 2, 'is_required' => false],
        ],
        'ألمنيوم وأبجورات' => [
            ['material' => 'Paint', 'sort_order' => 1, 'is_required' => false],
            ['material' => 'Putty', 'sort_order' => 2, 'is_required' => false],
        ],
        'تشطيبات نهائية' => [
            ['material' => 'Paint',             'sort_order' => 1, 'is_required' => true],
            ['material' => 'Putty',             'sort_order' => 2, 'is_required' => true],
            ['material' => 'Roller',            'sort_order' => 3, 'is_required' => true],
            ['material' => 'Brush',             'sort_order' => 4, 'is_required' => true],
            ['material' => 'Sandpaper',         'sort_order' => 5, 'is_required' => true],
            ['material' => 'Lighting Fixture',  'sort_order' => 6, 'is_required' => true],
            ['material' => 'Switches',          'sort_order' => 7, 'is_required' => true],
            ['material' => 'Power Outlets',     'sort_order' => 8, 'is_required' => true],
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
                        'sort_order' => $entry['sort_order'],
                        'is_required' => $entry['is_required'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('work_item_materials')
                ->whereIn('work_item_name', self::WORK_ITEM_TYPES)
                ->delete();

            if (!empty($pivotRows)) {
                DB::table('work_item_materials')->insert($pivotRows);
            }
        });
    }
}