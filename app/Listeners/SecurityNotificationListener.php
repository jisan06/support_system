<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;

class SecurityNotificationListener
{
    public function handle(Lockout $event): void
    {
        $request = $event->request;
        $email = $request->input('email');
        $ip = $request->ip();

        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->get();

        if ($admins->isEmpty()) {
            $admins = User::where('id', 1)->get();
        }

        notify(
            $admins,
            __('notification.account_locked_title'),
            __('notification.account_locked_message', ['email' => $email, 'ip' => $ip]),
            'danger',
            route('activity-logs.index')
        );
    }
}
