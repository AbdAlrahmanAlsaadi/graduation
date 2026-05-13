<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $quality_level
 * @property int $sort_order
 * @property int|null $duration_days
 * @property bool $is_default
 * @property bool $is_active
 * @property bool $is_custom
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Project $project
 * @property-read WorkItem|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkItem> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkItemDetail> $details
 * @method static Builder|WorkItem active()
 * @method static Builder|WorkItem default()
 */
class WorkItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';

    public const QUALITY_LEVEL_BASIC = 'basic';
    public const QUALITY_LEVEL_GOOD = 'good';
    public const QUALITY_LEVEL_PREMIUM = 'premium';
    public const QUALITY_LEVEL_CUSTOM = 'custom';

    public const QUALITY_LEVELS = [
        self::QUALITY_LEVEL_BASIC,
        self::QUALITY_LEVEL_GOOD,
        self::QUALITY_LEVEL_PREMIUM,
        self::QUALITY_LEVEL_CUSTOM,
    ];

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'quality_level',
        'duration_days',
        'sort_order',
        'is_default',
        'is_active',
        'is_custom',
    ];

    protected $attributes = [
        'status' => self::STATUS_PLANNED,
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'duration_days' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $nullable = $isUpdate ? 'sometimes' : 'nullable';

        return [
            'name' => [$required, 'string', 'max:255'],
            'quality_level' => [
                $nullable,
                'string',
                Rule::in(self::QUALITY_LEVELS),
            ],
            'duration_days' => [$nullable, 'integer', 'min:1'],
            'sort_order' => [$nullable, 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'is_custom' => ['sometimes', 'boolean'],
            'parent_id' => [$nullable, 'nullable', 'integer', 'exists:work_items,id'],
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(WorkItemDetail::class);
    }

    /**
     * @param array<int, array{key:string, value:mixed, unit:?string}> $details
     */
    public function syncDetails(array $details): void
    {
        DB::transaction(function () use ($details) {
            $keys = array_column($details, 'key');

            $this->details()->whereIn('key', $keys)->delete();
            $now = now();
            $rows = [];
            foreach ($details as $d) {
                $rows[] = [
                    'work_item_id' => $this->id,
                    'key' => $d['key'],
                    'value' => (string) $d['value'],
                    'unit' => $d['unit'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->details()->createMany($rows);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
