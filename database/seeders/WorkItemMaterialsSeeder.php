<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkItemMaterialsSeeder extends Seeder
{
    private const WORK_ITEM_TYPES = [
        'ملابن الأبواب',
        'تمديدات كهرباء سواد',
        'تمديدات كهرباء بياض',
        'تمديدات صحية سواد',
        'تمديدات صحية بياض',
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
            ['material' => 'Cement',],
            ['material' => 'Sand',  ],
            ['material' => 'Water', ],
        ],
        'تمديدات كهرباء سواد' => [
            ['material' => 'Electrical Tape', ],
            ['material' => 'Electrical Boxes',],
            ['material' => 'Wires',           ],
            ['material' => 'Power Outlets',   ],
            ['material' => 'Switches',        ],
            ['material' => 'Lighting Fixture',],
        ],
        'تمديدات كهرباء بياض' => [
            ['material' => 'Electrical Tape', ],
            ['material' => 'Electrical Boxes',],
            ['material' => 'Wires',           ],
            ['material' => 'Power Outlets',   ],
            ['material' => 'Switches',        ],
            ['material' => 'Lighting Fixture',],
        ],
        'تمديدات صحية سواد' => [
            ['material' => 'Cement',],
            ['material' => 'Sand',  ],
            ['material' => 'Water', ],
            ['material' => 'Faucet',],
            ['material' => 'Boiler',],
        ],
        'تمديدات صحية بياض' => [
            ['material' => 'Cement'],
            ['material' => 'Sand' ],
            ['material' => 'Water',],
            ['material' => 'Faucet',],
            ['material' => 'Boiler',],
        ],
        'طينة / لياسة' => [
            ['material' => 'Cement',],
            ['material' => 'Sand',  ],
            ['material' => 'Water', ],
        ],
        'بلاط أرضيات' => [
            ['material' => 'Cement',          ],
            ['material' => 'Sand',            ],
            ['material' => 'Water',           ],
            ['material' => 'Floor Tiles',     ],
            ['material' => 'Ceramic Adhesive',],
            ['material' => 'Ceramic Spacers', ],
        ],
        'سيراميك جدران / أسقف' => [
            ['material' => 'Wall Ceramic',    ],
            ['material' => 'Ceiling Ceramic', ],
            ['material' => 'Ceramic Adhesive',],
            ['material' => 'Ceramic Spacers', ],
        ],
        'جبس بورد' => [
            ['material' => 'Gypsum Board',],
            ['material' => 'Gypsum Screws',   ],
            ['material' => 'Joint Compound',  ],
            ['material' => 'Joint Tape',      ],
            ['material' => 'Corner Bead',     ],
            ['material' => 'Metal Studs',     ],
            ['material' => 'Metal Tracks',    ],
            ['material' => 'Insulation',      ],
            ['material' => 'Drywall Saw',     ],
            ['material' => 'Screw Gun',       ],
            ['material' => 'Taping Knife',    ],
        ],
        'دهان' => [
            ['material' => 'Paint',    ],
            ['material' => 'Putty',    ],
            ['material' => 'Roller',   ],
            ['material' => 'Brush',    ],
            ['material' => 'Sandpaper',],
        ],
        'أبواب ونجارة' => [
            ['material' => 'Paint', ],
            ['material' => 'Putty', ],
        ],
        'ألمنيوم وأبجورات' => [
            ['material' => 'Paint',],
            ['material' => 'Putty',],
        ],
        'تشطيبات نهائية' => [
            ['material' => 'Paint',           ],
            ['material' => 'Putty',           ],
            ['material' => 'Roller',          ],
            ['material' => 'Brush',           ],
            ['material' => 'Sandpaper',       ],
            ['material' => 'Lighting Fixture',],
            ['material' => 'Switches',        ],
            ['material' => 'Power Outlets',   ],
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

            if (!empty($pivotRows)) {
                DB::table('work_item_materials')->insert($pivotRows);
            }
        });
    }
}