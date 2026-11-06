<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    protected $table = 'activity_log';

    public function getCauserNameAttribute(): string
    {
        if ($this->causer) {
            return $this->causer->name ?? "#{$this->causer_id}";
        }

        return __('activity_log.system');
    }

    public function getSubjectLabelAttribute(): string
    {
        if ($this->subject_type) {
            return class_basename($this->subject_type);
        }

        return '-';
    }
}