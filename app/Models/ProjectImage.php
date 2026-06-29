<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{

    protected $fillable = [
        'project_id',
        'created_by',
        'name',
        'image',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function visualizations()
    {
        return $this->hasMany(AiVisualization::class);
    }
}
