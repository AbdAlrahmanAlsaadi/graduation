<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'owner_id',
        'rating',
        'comment',
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
