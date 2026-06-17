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
    ];

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'material_id' => 'integer',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id', 'id');
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'work_item_name', 'name');
    }
}