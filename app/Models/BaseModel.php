<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Translatable\HasTranslations;

class BaseModel extends Model
{
    use HasTranslations;
    protected $guarded = ['id'];

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
