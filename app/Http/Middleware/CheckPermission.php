<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Route name to permission slug mapping.
     * Maps route names to the required permission slug.
     */
    protected array $routePermissions = [
        // Users
        'users.index'          => 'view_user',
        'users.trash'          => 'view_user',
        'users.create'         => 'create_user',
        'users.store'          => 'create_user',
        'users.show'           => 'view_user',
        'users.edit'           => 'edit_user',
        'users.update'         => 'edit_user',
        'users.destroy'        => 'delete_user',
        'users.bulk_delete'    => 'delete_user',
        'users.restore'        => 'delete_user',
        'users.bulk_restore'   => 'delete_user',
        'users.force-delete'   => 'delete_user',
        'users.bulk_force_delete' => 'delete_user',

        // Customers
        'customers.index'          => 'view_customer',
        'customers.trash'          => 'view_customer',
        'customers.create'         => 'create_customer',
        'customers.store'          => 'create_customer',
        'customers.show'           => 'view_customer',
        'customers.edit'           => 'edit_customer',
        'customers.update'         => 'edit_customer',
        'customers.destroy'        => 'delete_customer',
        'customers.bulk_delete'    => 'delete_customer',
        'customers.restore'        => 'delete_customer',
        'customers.bulk_restore'   => 'delete_customer',
        'customers.force-delete'   => 'delete_customer',
        'customers.bulk_force_delete' => 'delete_customer',

        // Roles
        'roles.index'          => 'view_role',
        'roles.trash'          => 'view_role',
        'roles.create'         => 'create_role',
        'roles.store'          => 'create_role',
        'roles.edit'           => 'edit_role',
        'roles.update'         => 'edit_role',
        'roles.destroy'        => 'delete_role',
        'roles.bulk_delete'    => 'delete_role',
        'roles.restore'        => 'delete_role',
        'roles.bulk_restore'   => 'delete_role',
        'roles.force-delete'   => 'delete_role',
        'roles.bulk_force_delete' => 'delete_role',
        'roles.permission'     => 'edit_role',
        'roles.permission_update' => 'edit_role',

        // Media
        'media.index'          => 'view_media',
        'media.store'          => 'create_media',
        'media.destroy'        => 'delete_media',
        'media.bulk_destroy'   => 'delete_media',

        // Activity Logs
        'activity-logs.index'  => 'view_activity_log',
        'activity-logs.show'   => 'view_activity_log',
        'activity-logs.destroy' => 'delete_activity_log',
        'activity-logs.bulk_delete' => 'delete_activity_log',
        'activity-logs.clear'  => 'delete_activity_log',

        // Backups
        'backups.index'        => 'view_backup',
        'backups.store'        => 'create_backup',
        'backups.download'     => 'view_backup',
        'backups.destroy'      => 'delete_backup',
        'backups.restore'      => 'restore_backup',
        'backups.clean'        => 'delete_backup',

        // System Monitoring
        'system-monitoring.index'       => 'view_system_monitoring',
        'system-monitoring.clear-cache' => 'view_system_monitoring',
        'system-monitoring.clear-logs'  => 'view_system_monitoring',

        // Update
        'update.index' => 'view_system_monitoring',
        'update.check' => 'view_system_monitoring',

        // Notifications
        'notifications.index'          => 'view_notification',
        'notifications.poll'           => 'view_notification',
        'notifications.mark_read'      => 'view_notification',
        'notifications.mark_all_read'  => 'view_notification',
        'notifications.destroy'        => 'delete_notification',
        'notifications.bulk_delete'    => 'delete_notification',
        'notifications.send_test'      => 'view_notification',

        // Settings
        'settings.index'  => null, // handled dynamically by group
        'settings.update' => null, // handled dynamically by group
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Enforce account_type: Only internal admin/staff accounts can access the admin panel
        if ($request->user() && !$request->user()->isAdmin()) {
            return $this->denyAccess($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        // Skip routes that don't need permission checks
        $skipRoutes = [
            'admin.dashboard', 'profile.index', 'profile.update', 'profile.password',
            'menu-sort.index', 'menu-sort.update', 'lang.switch', 'logout',
        ];

        if (in_array($routeName, $skipRoutes)) {
            return $next($request);
        }

        // Handle settings routes dynamically by group param
        if (in_array($routeName, ['settings.index', 'settings.update'])) {
            $group = $request->route('group', 'general');
            $action = $routeName === 'settings.update' ? 'edit' : 'view';
            $permission = "{$action}_setting_{$group}";

            if (Gate::has($permission) && Gate::denies($permission)) {
                return $this->denyAccess($request);
            }

            return $next($request);
        }

        // Check mapped permission
        $permission = $this->routePermissions[$routeName] ?? null;

        if ($permission && Gate::denies($permission)) {
            return $this->denyAccess($request);
        }

        return $next($request);
    }

    protected function denyAccess(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => __('permission.no_access'),
            ], 403);
        }

        return response()->view('admin.errors.403', [], 403);
    }
}
