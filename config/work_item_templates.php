<?php

return [

    // 9.1 بند الملابن
    'ملابن الأبواب' => [
        'wood_doors_count' => [
            'unit' => 'count',
            'rule' => 'required|integer|min:0',
            'label' => 'عدد أبواب الخشب',
        ],
        'aluminum_doors_count' => [
            'unit' => 'count',
            'rule' => 'required|integer|min:0',
            'label' => 'عدد أبواب الألمنيوم',
        ],
        'windows_count' => [
            'unit' => 'count',
            'rule' => 'required|integer|min:0',
            'label' => 'عدد النوافذ',
        ],
    ],

    // 9.2 بند بلاط الأرضيات
    'بلاط أرضيات' => [
        'tile_length' => [
            'unit' => 'cm',
            'rule' => 'required|numeric|min:0',
            'label' => 'طول البلاطة',
        ],
        'tile_width' => [
            'unit' => 'cm',
            'rule' => 'required|numeric|min:0',
            'label' => 'عرض البلاطة',
        ],
    ],

    // 9.3 بند سيراميك الجدران والأسقف
    'سيراميك جدران / أسقف' => [
        'ceramic_length' => [
            'unit' => 'cm',
            'rule' => 'required|numeric|min:0',
            'label' => 'طول السيراميك',
        ],
        'ceramic_width' => [
            'unit' => 'cm',
            'rule' => 'required|numeric|min:0',
            'label' => 'عرض السيراميك',
        ],
    ],

];