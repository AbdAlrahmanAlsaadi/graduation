<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $work_item_type
 * @property string $material_name
 * @property string $unit
 * @property string|null $default_qty
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WorkItemMaterialTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_item_type',
        'material_name',
        'unit',
        'default_qty',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'default_qty' => 'decimal:2',
        ];
    }
}
