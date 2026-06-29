<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVisualization extends Model
{
    protected $fillable = [
        'project_image_id',
        'reference_images',
        'generated_image',
    ];

    protected $casts = [
        'reference_images' => 'array',
    ];

    public function projectImage(): BelongsTo
    {
        return $this->belongsTo(ProjectImage::class);
    }
    public function comments()
    {
        return $this->hasMany(AiVisualizationComment::class);
    }

}
