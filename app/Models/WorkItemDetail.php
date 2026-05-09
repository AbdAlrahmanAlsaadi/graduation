<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $work_item_id
 * @property string $key
 * @property string $value
 * @property string|null $unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read WorkItem $workItem
 */
class WorkItemDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_item_id',
        'key',
        'value',
        'unit',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }
}
