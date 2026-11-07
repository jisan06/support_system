<?php

namespace App\Models;

class Media extends BaseModel
{
    public array $translatable = [];

    public function mediable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if ($this->disk === 'public') {
            return asset('storage/' . $this->path);
        }

        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
    }
}
