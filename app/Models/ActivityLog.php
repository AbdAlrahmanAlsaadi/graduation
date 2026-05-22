<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [

        'user_id',

        'action',

        'method',

        'endpoint',

        'entity_type',

        'entity_id',

        'description',

        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
