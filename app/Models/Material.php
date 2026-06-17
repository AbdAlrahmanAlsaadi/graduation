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
        'work_item_materials',   // pivot table
        'material_id',           // foreignPivotKey (pivot column that points to this model)
        'work_item_name',        // relatedPivotKey (pivot column that points to WorkItem)
        'id',                    // parentKey (Material primary key)
        'name'                   // relatedKey (WorkItem key to match pivot.work_item_name)
    )
    ->using(WorkItemMaterial::class)
    ->withTimestamps();
}
    public function invoiceItems()
    {
        return $this->hasMany( WorkItemInvoiceItem::class
        );
    }
}
