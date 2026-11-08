<?php

if (!function_exists('admin_asset')) {
    function admin_asset($path = '') {
        return asset("backend/assets/{$path}");
    }
}

if (!function_exists('notify')) {
    /**
     * Send a notification to user(s).
     *
     * @param  mixed       $user    User model, array of users, or Collection
     * @param  string      $title
     * @param  string      $message
     * @param  string      $type    info|success|warning|danger
     * @param  string|null $url     Optional link
     * @param  array       $extra   Optional additional metadata
     */
    function notify($user, string $title, string $message, string $type = 'info', ?string $url = null, array $extra = []): void
    {
        $users = $user instanceof \Illuminate\Database\Eloquent\Collection
            ? $user
            : collect(is_array($user) ? $user : [$user]);

        \Illuminate\Support\Facades\Notification::send(
            $users,
            new \App\Notifications\GeneralNotification($title, $message, $type, $url, $extra)
        );

        // Broadcast real-time event to each user
        foreach ($users as $u) {
            event(new \App\Events\NewNotification($u->id, $title, $message, $type, $url));
        }
    }
}

if (!function_exists('password_rules')) {
    /**
     * Build Laravel validation rules array from config/password.php.
     *
     * @return array
     */
    function password_rules(): array
    {
        $rules = ['min:' . config('password.min_length', 8)];

        if (config('password.lowercase')) $rules[] = 'regex:/[a-z]/';
        if (config('password.uppercase')) $rules[] = 'regex:/[A-Z]/';
        if (config('password.numbers'))   $rules[] = 'regex:/[0-9]/';
        if (config('password.symbols'))   $rules[] = 'regex:/[^a-zA-Z0-9]/';

        return $rules;
    }
}

if (!function_exists('setting')) {
    /**
     * Get a setting value by key.
     *
     * @param  string      $key
     * @param  mixed|null  $default
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return app(\App\Services\SettingService::class)->get($key, $default);
    }
}

if (!function_exists('business_timezone')) {
    /**
     * Get the configured business / display timezone.
     *
     * @return string
     */
    function business_timezone(): string
    {
        $tz = setting('business_timezone') ?: setting('timezone') ?: config('app.timezone', 'UTC');

        return in_array($tz, \DateTimeZone::listIdentifiers(), true) ? $tz : config('app.timezone', 'UTC');
    }
}

if (!function_exists('to_business_timezone')) {
    /**
     * Convert a Carbon/datetime to the configured business timezone.
     *
     * @param  \Carbon\Carbon|\DateTimeInterface|string|null  $date
     * @return \Carbon\Carbon|null
     */
    function to_business_timezone($date): ?\Carbon\Carbon
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof \Carbon\Carbon) {
            $carbon = $date->copy();
        } elseif ($date instanceof \DateTimeInterface) {
            $carbon = \Carbon\Carbon::instance($date);
        } else {
            $carbon = \Carbon\Carbon::parse($date, config('app.timezone', 'UTC'));
        }

        return $carbon->setTimezone(business_timezone());
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a Carbon/datetime in the configured business timezone.
     *
     * @param  \Carbon\Carbon|\DateTimeInterface|string|null  $date
     * @param  string  $format
     * @return string
     */
    function format_datetime($date, string $format = 'M d, Y h:i A'): string
    {
        $converted = to_business_timezone($date);

        return $converted ? $converted->format($format) : '';
    }
}
