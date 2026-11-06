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
use Spatie\Translatable\HasTranslations;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasTranslations, SoftDeletes, HasMedia, LogsActivity;

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
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "User {$this->name} was {$eventName}")
            ->useLogName('user');
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