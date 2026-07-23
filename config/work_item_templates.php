<?php

/**
 * config/work_item_templates.php
 *
 * Defines the fields, validation rules, and merge behavior for each work item type.
 *
 * Each field has:
 * - unit:          the measurement unit (count, bool, m, m2, etc.)
 * - rule:          validation rule for initial detail setup (required fields)
 * - progress_rule: validation rule for progress updates (sometimes fields)
 * - label:         Arabic label for display
 * - additive:      if true, progress updates ADD to the existing value instead of replacing
 */

return [

    // 1) ملابن الأبواب (Mellaben)
    'ملابن الأبواب' => [
        'total_wood_doors' => [
            'unit'          => 'count',
            'rule'          => 'required|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'عدد أبواب الخشب',
            'additive'      => false,
        ],
        'completed_wood_doors' => [
            'unit'          => 'count',
            'rule'          => 'sometimes|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'أبواب الخشب المنجزة',
            'additive'      => true,
        ],
        'total_aluminum_doors' => [
            'unit'          => 'count',
            'rule'          => 'required|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'عدد أبواب الألمنيوم',
            'additive'      => false,
        ],
        'completed_aluminum_doors' => [
            'unit'          => 'count',
            'rule'          => 'sometimes|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'أبواب الألمنيوم المنجزة',
            'additive'      => true,
        ],
        'total_windows' => [
            'unit'          => 'count',
            'rule'          => 'required|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'عدد النوافذ',
            'additive'      => false,
        ],
        'completed_windows' => [
            'unit'          => 'count',
            'rule'          => 'sometimes|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'النوافذ المنجزة',
            'additive'      => true,
        ],
    ],

    // 2) تمديدات كهرباء بياض (Electricity - White)
    'تمديدات كهرباء بياض' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 3) تمديدات كهرباء سواد (Electricity - Black)
    'تمديدات كهرباء سواد' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 4) تمديدات صحية بياض (Sanitary - White)
    'تمديدات صحية بياض' => [
        'kitchen_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'المطبخ منجز',
            'additive'      => false,
        ],
        'bathroom_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'الحمام منجز',
            'additive'      => false,
        ],
        'toilet_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'التواليت منجز',
            'additive'      => false,
        ],
    ],

    // 5) تمديدات صحية سواد (Sanitary - Black)
    'تمديدات صحية سواد' => [
        'kitchen_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'المطبخ منجز',
            'additive'      => false,
        ],
        'bathroom_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'الحمام منجز',
            'additive'      => false,
        ],
        'toilet_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'التواليت منجز',
            'additive'      => false,
        ],
    ],

    // 6) طينة / لياسة (Plaster)
    'طينة / لياسة' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 7) بلاط أرضيات (Floor Tiles)
    'بلاط أرضيات' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 8) سيراميك جدران / أسقف (Ceramic Walls/Ceilings)
    'سيراميك جدران / أسقف' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 9) جبس بورد (Gypsum Board)
    'جبس بورد' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 10) دهان (Paint)
    'دهان' => [
        'rooms_status' => [
            'unit'          => 'json',
            'rule'          => 'sometimes|json',
            'progress_rule' => 'sometimes|json',
            'label'         => 'حالة الغرف',
            'additive'      => false,
        ],
    ],

    // 11) أبواب ونجارة (Doors & Carpentry)
    'أبواب ونجارة' => [
        'total_doors' => [
            'unit'          => 'count',
            'rule'          => 'required|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'إجمالي الأبواب',
            'additive'      => false,
        ],
        'completed_doors' => [
            'unit'          => 'count',
            'rule'          => 'sometimes|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'الأبواب المنجزة',
            'additive'      => true,
        ],
        'kitchen_cabinet_done' => [
            'unit'          => 'bool',
            'rule'          => 'required|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'خزن المطبخ منجزة',
            'additive'      => false,
        ],
    ],

    // 12) ألمنيوم وأبجورات (Aluminum & Shutters)
    'ألمنيوم وأبجورات' => [
        'total_aluminum' => [
            'unit'          => 'count',
            'rule'          => 'required|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'إجمالي الألمنيوم',
            'additive'      => false,
        ],
        'completed_aluminum' => [
            'unit'          => 'count',
            'rule'          => 'sometimes|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'الألمنيوم المنجز',
            'additive'      => true,
        ],
    ],

    // 13) ديكورات (Final Finishes)
    'ديكورات' => [
        'final_items_total' => [
            'unit'          => 'count',
            'rule'          => 'required|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'إجمالي بنود التشطيب',
            'additive'      => false,
        ],
        'final_items_completed' => [
            'unit'          => 'count',
            'rule'          => 'sometimes|integer|min:0',
            'progress_rule' => 'sometimes|integer|min:0',
            'label'         => 'بنود التشطيب المنجزة',
            'additive'      => true,
        ],
        'all_finished' => [
            'unit'          => 'bool',
            'rule'          => 'sometimes|boolean',
            'progress_rule' => 'sometimes|boolean',
            'label'         => 'منجز بالكامل',
            'additive'      => false,
        ],
    ],
];