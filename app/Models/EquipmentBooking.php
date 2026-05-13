<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentBooking extends Model
{
    protected $fillable = [
        'equipment_id',
        'work_item_id',
        'booked_by',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function workItem()
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function bookedBy()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

}
