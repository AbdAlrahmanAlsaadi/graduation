<?php

/**
 * config/work_item_logic.php
 *
 * هذا الملف مسؤول عن:
 * - ربط اسم البند بنوع الحسبة (calculation type)
 * - تعريف نوع الحسبة وما يعتمد عليه من مفاتيح
 * - إعدادات عامة لحساب النسب
 *
 * ملاحظة مهمة:
 * - هذا الملف لا يعرّف الوحدات ولا قواعد التحقق (rules)
 * - هذه الأشياء تبقى في ملف config آخر خاص بـ work_item_details
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Mapping: ربط اسم البند بنوع الحسبة
    |--------------------------------------------------------------------------
    |
    | تستخدمه WorkItemProgressService لاختيار دالة الحساب المناسبة لكل بند.
    |
    */

    'mapping' => [
        'ملابن الأبواب'        => 'mellaben',
        'تمديدات كهرباء بياض' => 'rooms',
        'تمديدات كهرباء سواد' => 'rooms',
        'تمديدات صحية بياض' => 'sanitary',
        'تمديدات صحية سواد' => 'sanitary',
        'طينة / لياسة'         => 'plaster',
        'بلاط أرضيات'          => 'tile',
        'سيراميك جدران / أسقف' => 'ceramic',
        'جبس بورد'             => 'gypsum',
        'دهان'                 => 'rooms',
        'أبواب ونجارة'         => 'doors',
        'ألمنيوم وأبجورات'     => 'aluminum',
        'ديكورات'       => 'finals',
    ],

    /*
    |--------------------------------------------------------------------------
    | Calculation definitions
    |--------------------------------------------------------------------------
    |
    | لكل نوع حسبة نحدد:
    | - keys: المفاتيح الأساسية التي يعتمد عليها الحساب
    | - strategy: اسم الاستراتيجية/الدالة داخل الخدمة (اختياري لو حابب تسميها)
    |
    */

    'calculations' => [

        // 1) ملابن الأبواب: أجزاء مرجّحة (خشب / ألمنيوم / نوافذ)
        'mellaben' => [
            'strategy' => 'mellaben',
            'keys' => [
                'total_wood_doors',
                'completed_wood_doors',
                'total_aluminum_doors',
                'completed_aluminum_doors',
                'total_windows',
                'completed_windows',
            ],
        ],

        // 2) بنود غرفية بسيطة: كهرباء، طينة، جبس، دهان
        'rooms' => [
            'strategy' => 'rooms',
            'keys' => [
                'rooms_total',
                'rooms_completed',
            ],
        ],

        // 4) بلاط أرضيات: مساحة / طول / عرض / عدد قطع منجزة
        'tile' => [
            'strategy' => 'tile',
            'keys' => [
                'tile_length',
                'tile_width',
                'total_area_m2',
                'completed_tiles',
            ],
        ],

        // 5) سيراميك جدران / أسقف: نفس منطق البلاط
        'ceramic' => [
            'strategy' => 'ceramic',
            'keys' => [
                'ceramic_length',
                'ceramic_width',
                'total_area_m2',
                'completed_pieces',
            ],
        ],

        // 6) أبواب ونجارة: عدد أبواب + خزن المطبخ
        'doors' => [
            'strategy' => 'doors',
            'keys' => [
                'total_doors',
                'completed_doors',
                'kitchen_cabinet_done',
            ],
        ],

        // 7) ألمنيوم وأبجورات: عدد عناصر ألمنيوم
        'aluminum' => [
            'strategy' => 'aluminum',
            'keys' => [
                'total_aluminum',
                'completed_aluminum',
            ],
        ],

        'finals' => [
            'strategy' => 'finals',
            'keys' => [
                'rooms_total',
                'rooms_completed',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        // عدد الأرقام بعد الفاصلة في النسب
        'percent_precision' => 2,

        // القيمة الافتراضية لوزن البند إذا لم يحدد في work_items.weight
        'default_weight' => 1.00,
    ],

];