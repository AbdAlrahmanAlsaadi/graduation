<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WorkItemMaterial extends Pivot
{
    protected $table = 'work_item_materials';

    public $timestamps = true;

    protected $fillable = [
        'work_item_name',
        'material_id',
        'sort_order',
        'is_required',
    ];

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'material_id' => 'integer',
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'work_item_name', 'name');
    }
}