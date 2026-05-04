<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_name',
        'location',
        'latitude',
        'longitude',
        'total_area',
        'height',
        'project_manager_id',
        'assistant_engineer_id',
        'owner_id',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'total_area' => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function assistantEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_engineer_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
