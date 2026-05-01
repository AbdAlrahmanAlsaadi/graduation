<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $fillable = [
        'id',
        'name',
        'type',
        'identifier_no',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function maintenances()
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }
}
