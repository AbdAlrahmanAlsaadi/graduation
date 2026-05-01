<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentMaintenance extends Model
{
    protected $fillable = [
        'equipment_id',
        'start_date',
        'end_date',
        'type',
        'description',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
