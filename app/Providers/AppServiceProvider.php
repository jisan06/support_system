<?php

namespace App\Providers;

use App\Listeners\SecurityNotificationListener;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\TicketMessageRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\ActivityLogRepository;
use App\Repositories\MediaRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SettingRepository;
use App\Repositories\TicketMessageRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\Media\MediaDriverManager;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(TicketMessageRepositoryInterface::class, TicketMessageRepository::class);
        $this->app->singleton(MediaDriverManager::class);
    }

    public function boot(): void
    {
        // Load breadcrumbs
        if (file_exists(base_path('routes/breadcrumbs.php'))) {
            require base_path('routes/breadcrumbs.php');
        }

        // Register gates from config/permissions.php
        $permissions = config('permissions', []);
        foreach ($permissions as $group) {
            foreach ($group['modules'] ?? [] as $module) {
                foreach ($module['permissions'] ?? [] as $slug => $label) {
                    Gate::define($slug, function ($user) use ($slug) {
                        return $user->hasPermissionTo($slug);
                    });
                }
            }
        }

        // Register event listeners
        Event::listen(Lockout::class, SecurityNotificationListener::class);
    }

}
