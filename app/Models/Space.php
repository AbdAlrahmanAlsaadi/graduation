<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Space extends Model
{
    use HasFactory;

    public const TYPE_OPTIONS = [
        'room',
        'salon',
        'kitchen',
        'bathroom',
        'toilet',
        'corridor',
        'entrance',
        'balcony',
        'storage',
        'custom',
    ];

    public const FINISH_TYPES = [
        'paint',
        'ceramic',
        'gypsum',
        'none',
        'custom',
    ];

    public const TOILET_TYPES = [
        'none',
        'arabic',
        'western',
    ];

    public const CEILING_CERAMIC_TYPES = [
        'kitchen',
        'bathroom',
        'toilet',
        'balcony',
    ];

    protected $fillable = [
        'project_id',
        'type',
        'area',
        'finish_type',
        'toilet_type',
        'has_ceiling_ceramic',
        'ceiling_ceramic_area',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'has_ceiling_ceramic' => 'boolean',
            'ceiling_ceramic_area' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
