<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends BaseModel
{
    use SoftDeletes, LogsActivity;
    public array $translatable = ['name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Role {$this->name} was {$eventName}")
            ->useLogName('role');
    }

    public function permissions() {
        return $this->hasMany(RolePermission::class);
    }
}