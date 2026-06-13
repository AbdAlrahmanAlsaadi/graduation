<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkItemDetail extends Model
{
    use HasFactory;

    protected $fillable = [

        'work_item_id',

        'key',

        'value',

        'pending_value',

        'approval_status',

        'approved_by',

        'approved_at',

        'unit',
    ];

    protected $casts = [

        'approved_at' => 'datetime',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }
}
