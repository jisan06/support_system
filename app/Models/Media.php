<?php

namespace App\Models;

class Media extends BaseModel
{
    public array $translatable = [];

    public function mediable()
    {
        return $this->morphTo();
    }
}
