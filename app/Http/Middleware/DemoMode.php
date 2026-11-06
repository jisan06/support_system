<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMode
{
    /**
     * Routes that are always allowed in demo mode (read-only actions).
     */
    protected array $allowedRoutes = [
        // Dashboard
        'admin.dashboard',

        // View/list routes
        'users.index', 'users.trash', 'users.show',
        'roles.index', 'roles.trash', 'roles.show', 'roles.permission',
        'media.index',
        'activity-logs.index', 'activity-logs.show',
        'backups.index',
        'system-monitoring.index',
        'update.index',
        'notifications.index', 'notifications.poll', 'notifications.mark_read', 'notifications.mark_all_read',
        'settings.index',

        // Profile view (not update)
        'profile.index',

        // Menu sort view
        'menu-sort.index',

        // Language switch
        'lang.switch',

        // Auth
        'login', 'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('demo.enabled')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        // Allow all GET requests (browsing is fine)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Allow explicitly permitted routes (even non-GET like mark-read)
        if (in_array($routeName, $this->allowedRoutes)) {
            return $next($request);
        }

        // Block all other write operations
        return $this->denyAction($request);
    }

    protected function denyAction(Request $request): Response
    {
        $message = __('This action is disabled in demo mode.');

        if ($request->expectsJson() || $request->ajax()) {
            return ResponseHelper::error($message, 403);
        }

        return redirect()->back()->with('error', $message);
    }
}