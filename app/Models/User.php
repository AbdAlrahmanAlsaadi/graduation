<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'internal_id',
        'password',
        'status',
        'fcm_token',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected string $guard_name = 'api';
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedContracts()
    {
        return $this->hasMany(Contract::class, 'owner_id');
    }

    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectEngineer::class, 'user_id');
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_engineers', 'user_id', 'project_id')
            ->withPivot(['role', 'assigned_at'])
            ->withTimestamps();
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function equipmentBookings()
    {
        return $this->hasMany(EquipmentBooking::class, 'booked_by');
    }
    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function createdInvoices()
    {
        return $this->hasMany(WorkItemInvoice::class,'created_by');
    }

    public function createdLaborCosts()
    {
        return $this->hasMany(
            WorkItemLaborCost::class,
            'created_by'
        );
    }
    public function workshopExpenses()
    {
        return $this->hasMany(
            WorkshopExpense::class,
            'paid_by'
        );
    }

    public function images()
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function aiVisualizationComments()
    {
        return $this->hasMany(AiVisualizationComment::class);
    }
}
