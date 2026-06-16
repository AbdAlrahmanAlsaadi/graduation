<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

/**
 * @property int $id
 * @property int $project_id
 * @property string $type
 * @property string $wall_area
 * @property string $wall_finish_type
 * @property string $ceiling_finish_type
 * @property string $toilet_type
 * @property string|null $ceiling_area
 * @property bool $is_shed_floor_tiled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Project $project
 */
class Space extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_ROOM = 'room';
    public const TYPE_SALON = 'salon';
    public const TYPE_KITCHEN = 'kitchen';
    public const TYPE_BATHROOM = 'bathroom';
    public const TYPE_TOILET = 'toilet';
    public const TYPE_CORRIDOR = 'corridor';
    public const TYPE_ENTRANCE = 'entrance';
    public const TYPE_SHED = 'shed';
    public const TYPE_STORAGE = 'storage';

    public const TYPE_OPTIONS = [
        self::TYPE_ROOM,
        self::TYPE_SALON,
        self::TYPE_KITCHEN,
        self::TYPE_BATHROOM,
        self::TYPE_TOILET,
        self::TYPE_CORRIDOR,
        self::TYPE_ENTRANCE,
        self::TYPE_SHED,
        self::TYPE_STORAGE,
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
        self::TYPE_KITCHEN,
        self::TYPE_BATHROOM,
        self::TYPE_TOILET,
        self::TYPE_SHED,
    ];

    protected $fillable = [
        'project_id',
        'type',
        'wall_area',
        'wall_finish_type',
        'ceiling_finish_type',
        'toilet_type',
        'ceiling_area',
        'is_shed_floor_tiled',
    ];

    protected function casts(): array
    {
        return [
            'wall_area' => 'decimal:2',
            'ceiling_area' => 'decimal:2',
            'is_shed_floor_tiled' => 'boolean',
        ];
    }

    public static function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'type' => [$required, 'string', Rule::in(self::TYPE_OPTIONS)],
            'wall_area' => [$required, 'numeric', 'min:0.1'],
            'wall_finish_type' => [$required, 'string', Rule::in(self::FINISH_TYPES)],
            'ceiling_finish_type' => [$required, 'string', Rule::in(self::FINISH_TYPES)],
            'toilet_type' => [$isUpdate ? 'sometimes' : 'nullable', 'string', Rule::in(self::TOILET_TYPES)],
            'ceiling_area' => [$required, 'numeric', 'min:0.1'],
            'is_shed_floor_tiled' => ['sometimes', 'boolean'],
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getEffectiveFloorAreaAttribute(): float
    {
        if ($this->type === self::TYPE_SHED && $this->is_shed_floor_tiled) {
            return (float) ($this->ceiling_area ?? 0);
        }

        return 0;
    }
}
