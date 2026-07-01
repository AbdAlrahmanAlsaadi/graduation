<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $work_item_id
 * @property int $requested_by
 * @property int|null $reviewed_by
 * @property string $status
 * @property int $requested_duration_days
 * @property string $reason
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read WorkItem $workItem
 * @property-read User $requester
 * @property-read User|null $reviewer
 */
class DurationExtensionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'project_id',
        'work_item_id',
        'requested_by',
        'reviewed_by',
        'status',
        'requested_duration_days',
        'reason',
        'comment',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_duration_days' => 'integer',
            'reviewed_at'             => 'datetime',
        ];
    }

    /* ── Relationships ─────────────────────────────────────────── */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ── Helpers ───────────────────────────────────────────────── */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /* ── Scopes ────────────────────────────────────────────────── */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
