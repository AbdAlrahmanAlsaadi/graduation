<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
    ];

    /**
     * @return BelongsToMany<WorkItem, $this>
     */
    public function workItems(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkItem::class,
            'work_item_materials',
            'material_id',
            'work_item_name',
            'id',
            'name'
        )
            ->using(WorkItemMaterial::class)
            ->withPivot(['sort_order', 'is_required'])
            ->withTimestamps();
    }
}
