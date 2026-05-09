<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'owner_id',
        'contract_no',
        'title',
        'contract_date',
        'start_date',
        'end_date',
        'contract_value',
        'currency',
        'status',
        'description',
        'company_signature',
        'owner_signature',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
