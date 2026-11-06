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
     */
    function notify($user, string $title, string $message, string $type = 'info', ?string $url = null): void
    {
        $users = $user instanceof \Illuminate\Database\Eloquent\Collection
            ? $user
            : collect(is_array($user) ? $user : [$user]);

        \Illuminate\Support\Facades\Notification::send(
            $users,
            new \App\Notifications\GeneralNotification($title, $message, $type, $url)
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
