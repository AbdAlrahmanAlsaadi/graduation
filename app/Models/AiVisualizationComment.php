<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVisualizationComment extends Model
{
    protected $fillable = [
        'ai_visualization_id',
        'user_id',
        'comment',
    ];

    public function visualization(): BelongsTo
    {
        return $this->belongsTo(AiVisualization::class, 'ai_visualization_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
