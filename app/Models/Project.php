<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

/**
 * @property int $id
 * @property string $name
 * @property string $location
 * @property string $latitude
 * @property string $longitude
 * @property string $apartment_area
 * @property string $height
 * @property string $status
 * @property int|null $project_manager_id
 * @property int|null $assistant_engineer_id
 * @property int|null $owner_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Space> $spaces
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkItem> $workItems
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectEngineer> $projectEngineers
 * @property-read User|null $projectManager
 * @property-read User|null $assistantEngineer
 * @property-read User|null $owner
 * @property-read User|null $createdBy
 * @property-read User|null $updatedBy
 * @method static Builder|Project withSummary()
 */
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_OPTIONS = [
        self::STATUS_PLANNED,
        self::STATUS_ONGOING,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'apartment_area',
        'height',
        'project_manager_id',
        'assistant_engineer_id',
        'owner_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'status' => self::STATUS_PLANNED,
    ];

    protected function casts(): array
    {
        return [
            'apartment_area' => 'decimal:2',
            'height' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $optionalNullable = $isUpdate ? ['sometimes', 'nullable'] : ['nullable'];

        return [
            'name' => [$required, 'string', 'max:255'],
            'location' => [$required, 'string', 'max:255'],
            'latitude' => [$required, 'numeric', 'max:255'],
            'longitude' => [$required, 'numeric', 'max:255'],
            'apartment_area' => [$required, 'numeric', 'min:0.1'],
            'height' => [$required, 'numeric', 'min:0.1'],
            'status' => ['sometimes', 'string', Rule::in(self::STATUS_OPTIONS)],
            'project_manager_id' => [
                ...$optionalNullable,
                'integer',
                'exists:users,id',
            ],
            'assistant_engineer_id' => [
                ...$optionalNullable,
                'integer',
                'exists:users,id',
            ],
            'owner_id' => [
                ...$optionalNullable,
                'integer',
                'exists:users,id',
            ],
            'created_by' => [
                ...$optionalNullable,
                'integer',
                'exists:users,id',
            ],
            'updated_by' => [
                ...$optionalNullable,
                'integer',
                'exists:users,id',
            ],
            'total_wood_doors' => [$required, 'numeric', 'max:255'],
            'total_aluminum_doors' => [$required, 'numeric', 'max:255'],
            'total_windows' => [$required, 'numeric', 'max:255'],
            'total_doors' => [$required, 'numeric', 'max:255'],
            'total_aluminum' => [$required, 'numeric', 'max:255'],
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

    public function activeWorkItems(): HasMany
    {
        return $this->workItems()->where('is_active', true);
    }

    public function projectEngineers(): HasMany
    {
        return $this->hasMany(ProjectEngineer::class);
    }

    public function assignEngineer(User|int $userOrId, string $role, ?string $assignedAt = null): ProjectEngineer
    {
        $userId = $userOrId instanceof User ? $userOrId->id : (int) $userOrId;

        return $this->projectEngineers()->firstOrCreate(
            [
                'user_id' => $userId,
                'role' => $role,
            ],
            [
                'assigned_at' => $assignedAt,
            ]
        );
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

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeWithSummary(Builder $query): Builder
    {
        return $query
            ->withCount(['spaces', 'workItems'])
            ->with([
                'spaces',
                'workItems' => fn (Builder $workItems) => $workItems->orderBy('sort_order'),
            ]);
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

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function invoices()
    {
        return $this->hasMany(
            WorkItemInvoice::class
        );
    }

    public function laborCosts()
    {
        return $this->hasMany(
            WorkItemLaborCost::class
        );
    }

    public function progressUpdateRequests(): HasMany
    {
        return $this->hasMany(ProgressUpdateRequest::class);
    }

    public function workshopExpenses()
    {
        return $this->hasMany(
            WorkshopExpense::class
        );
    }



    public function images()
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function durationExtensionRequests(): HasMany
    {
        return $this->hasMany(DurationExtensionRequest::class);
    }

    public function review()
    {
        return $this->hasOne(ProjectReview::class);
    }
    public function returnInvoices(): HasMany
    {
        return $this->hasMany(ReturnInvoice::class);
    }
}
