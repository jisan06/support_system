<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\HasMedia;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasTranslations, SoftDeletes, HasMedia, LogsActivity;

    public const TYPE_ADMIN = AccountType::ADMIN->value;
    public const TYPE_CUSTOMER = AccountType::CUSTOMER->value;

    protected $guarded = ['id'];
    public array $translatable = ['name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status', 'account_type'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "User {$this->name} was {$eventName}")
            ->useLogName('user');
    }

    /**
     * Scope query to internal admin/staff accounts.
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('account_type', AccountType::ADMIN->value);
    }

    /**
     * Scope query to customer accounts.
     */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('account_type', AccountType::CUSTOMER->value);
    }

    /**
     * Scope query to internal admin/staff accounts.
     */
    public function scopeIsAdmin(Builder $query): Builder
    {
        return $query->where('account_type', AccountType::ADMIN->value);
    }

    /**
     * Scope query to customer accounts.
     */
    public function scopeIsCustomer(Builder $query): Builder
    {
        return $query->where('account_type', AccountType::CUSTOMER->value);
    }

    /**
     * Check if the account is an internal admin/staff account.
     */
    public function isAdmin(): bool
    {
        return $this->account_type === AccountType::ADMIN || $this->account_type === AccountType::ADMIN->value;
    }

    /**
     * Check if the account is a customer account.
     */
    public function isCustomer(): bool
    {
        return $this->account_type === AccountType::CUSTOMER || $this->account_type === AccountType::CUSTOMER->value;
    }

    /**
     * Get the dashboard route name for this user account.
     */
    public function dashboardRoute(): string
    {
        return $this->isCustomer() ? 'customer.dashboard' : 'admin.dashboard';
    }

    /**
     * Get the dashboard URL for this user account.
     */
    public function dashboardUrl(): string
    {
        return route($this->dashboardRoute());
    }

    /**
     * Tickets owned by this user (customer tickets).
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    /**
     * Tickets assigned to this user (staff/agent).
     */
    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * Messages sent by this user in ticket conversations.
     */
    public function ticketMessages()
    {
        return $this->hasMany(TicketMessage::class, 'user_id');
    }

    /**
     * User can have many roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps()
            ->withPivot('created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    protected ?int $cachedRoleLevel = null;

    public function roleLevel(): int
    {
        if ($this->cachedRoleLevel === null) {
            $this->cachedRoleLevel = $this->relationLoaded('roles')
                ? (int) ($this->roles->min('level') ?? 999)
                : (int) ($this->roles()->min('level') ?? 999);
        }

        return $this->cachedRoleLevel;
    }

    protected ?array $cachedPermissions = null;

    public function hasPermissionTo(string $slug): bool
    {
        if ($this->cachedPermissions === null) {
            $this->cachedPermissions = $this->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('slug')
                ->unique()
                ->toArray();
        }

        return in_array($slug, $this->cachedPermissions);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check() && $model->isFillable('created_by')) {
                $model->created_by = Auth::id();
            }
        });
    }
}