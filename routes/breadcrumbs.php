<?php

use App\Services\BreadcrumbsManager;

/*
 * Role Breadcrumb
 */
    // Roles index (hidden)
    BreadcrumbsManager::for('roles.index', [
        ['label' => trans_choice('role.role', 2), 'route' => 'roles.index', 'show' => false],
    ]);

    // Roles create
    BreadcrumbsManager::for('roles.create', [
        ['label' => trans_choice('role.role', 2), 'route' => 'roles.index'],
        ['label' => trans('role.create_role'), 'route' => null],
    ]);

    // Roles edit (dynamic)
    BreadcrumbsManager::for('roles.edit', function ($role) {
        return [
            ['label' => trans_choice('role.role', 2), 'route' => 'roles.index'],
            ['label' => trans('common.edit') . ": {$role->name}", 'route' => null],
        ];
    });

    // Roles permission (dynamic)
    BreadcrumbsManager::for('roles.permission', function ($role) {
        return [
            ['label' => trans_choice('role.role', 2), 'route' => 'roles.index'],
            ['label' => trans('role.permission') . ": {$role->name}", 'route' => null],
        ];
    });
/*
 * Role Breadcrumb End
 */

/*
 * User Breadcrumb
 */
    // Users index (hidden)
    BreadcrumbsManager::for('users.index', [
        ['label' => trans_choice('user.user', 2), 'route' => 'users.index', 'show' => false],
    ]);

    // Users create
    BreadcrumbsManager::for('users.create', [
        ['label' => trans_choice('user.user', 2), 'route' => 'users.index'],
        ['label' => trans('user.create_user'), 'route' => null],
    ]);

    // Users show (dynamic)
    BreadcrumbsManager::for('users.show', function ($user) {
        return [
            ['label' => trans_choice('user.user', 2), 'route' => 'users.index'],
            ['label' => $user->name, 'route' => null],
        ];
    });

    // Users edit (dynamic)
    BreadcrumbsManager::for('users.edit', function ($user) {
        return [
            ['label' => trans_choice('user.user', 2), 'route' => 'users.index'],
            ['label' => trans('common.edit') . ": {$user->name}", 'route' => null],
        ];
    });
/*
 * User Breadcrumb End
 */

/*
 * Customer Breadcrumb
 */
    // Customers index (hidden)
    BreadcrumbsManager::for('customers.index', [
        ['label' => trans_choice('customer.customers', 2), 'route' => 'customers.index', 'show' => false],
    ]);

    // Customers create
    BreadcrumbsManager::for('customers.create', [
        ['label' => trans_choice('customer.customers', 2), 'route' => 'customers.index'],
        ['label' => trans('customer.add_new'), 'route' => null],
    ]);

    // Customers show (dynamic)
    BreadcrumbsManager::for('customers.show', function ($customer) {
        return [
            ['label' => trans_choice('customer.customers', 2), 'route' => 'customers.index'],
            ['label' => $customer->name, 'route' => null],
        ];
    });

    // Customers edit (dynamic)
    BreadcrumbsManager::for('customers.edit', function ($customer) {
        return [
            ['label' => trans_choice('customer.customers', 2), 'route' => 'customers.index'],
            ['label' => trans('common.edit') . ": {$customer->name}", 'route' => null],
        ];
    });
/*
 * Customer Breadcrumb End
 */

/*
 * Ticket Breadcrumb
 */
    // Tickets index (hidden)
    BreadcrumbsManager::for('tickets.index', [
        ['label' => trans('menu.admin_dashboard', [], null) !== 'menu.admin_dashboard' ? trans('menu.admin_dashboard') : 'Admin Dashboard', 'route' => 'admin.dashboard'],
        ['label' => trans('menu.support'), 'route' => null],
        ['label' => trans_choice('menu.ticket', 2), 'route' => 'tickets.index', 'show' => false],
    ]);

    // Tickets create
    BreadcrumbsManager::for('tickets.create', [
        ['label' => trans('menu.admin_dashboard', [], null) !== 'menu.admin_dashboard' ? trans('menu.admin_dashboard') : 'Admin Dashboard', 'route' => 'admin.dashboard'],
        ['label' => trans('menu.support'), 'route' => null],
        ['label' => trans_choice('menu.ticket', 2), 'route' => 'tickets.index'],
        ['label' => trans('permission.create_ticket'), 'route' => null],
    ]);

    // Tickets import
    BreadcrumbsManager::for('tickets.import.index', [
        ['label' => trans('menu.admin_dashboard', [], null) !== 'menu.admin_dashboard' ? trans('menu.admin_dashboard') : 'Admin Dashboard', 'route' => 'admin.dashboard'],
        ['label' => trans('menu.support'), 'route' => null],
        ['label' => trans_choice('menu.ticket', 2), 'route' => 'tickets.index'],
        ['label' => 'Import Tickets', 'route' => null],
    ]);

    // Tickets show (dynamic)
    BreadcrumbsManager::for('tickets.show', function ($ticket) {
        return [
            ['label' => trans('menu.admin_dashboard', [], null) !== 'menu.admin_dashboard' ? trans('menu.admin_dashboard') : 'Admin Dashboard', 'route' => 'admin.dashboard'],
            ['label' => trans('menu.support'), 'route' => null],
            ['label' => trans_choice('menu.ticket', 2), 'route' => 'tickets.index'],
            ['label' => is_object($ticket) ? "#{$ticket->ticket_number}" : "#{$ticket}", 'route' => null],
        ];
    });
/*
 * Ticket Breadcrumb End
 */

/*
 * Activity Log Breadcrumb
 */
    // Activity Log index
    BreadcrumbsManager::for('activity-logs.index', [
        ['label' => trans_choice('activity_log.activity_log', 2), 'route' => 'activity-logs.index', 'show' => false],
    ]);

    // Activity Log show (dynamic)
    BreadcrumbsManager::for('activity-logs.show', function ($id) {
        return [
            ['label' => trans_choice('activity_log.activity_log', 2), 'route' => 'activity-logs.index'],
            ['label' => trans('activity_log.log_detail') . " #{$id}", 'route' => null],
        ];
    });
/*
 * Activity Log Breadcrumb End
 */

/*
 * Notification Breadcrumb
 */
    BreadcrumbsManager::for('notifications.index', [
        ['label' => trans_choice('notification.notification', 2), 'route' => 'notifications.index', 'show' => false],
    ]);
/*
 * Notification Breadcrumb End
 */

/*
 * Backup Breadcrumb
 */
    // Backup index (hidden)
    BreadcrumbsManager::for('backups.index', [
        ['label' => trans('backup.title'), 'route' => 'backups.index', 'show' => false],
    ]);
/*
 * Backup Breadcrumb End
 */

/*
 * System Monitoring Breadcrumb
 */
    BreadcrumbsManager::for('system-monitoring.index', [
        ['label' => trans('system_monitoring.title'), 'route' => 'system-monitoring.index', 'show' => false],
    ]);
/*
 * System Monitoring Breadcrumb End
 */

/*
 * Update Breadcrumb
 */
    BreadcrumbsManager::for('update.index', [
        ['label' => trans('update.title'), 'route' => 'update.index', 'show' => false],
    ]);
/*
 * Update Breadcrumb End
 */

/*
 * Profile Breadcrumb
 */
    BreadcrumbsManager::for('profile.index', [
        ['label' => trans('profile.title'), 'route' => 'profile.index', 'show' => false],
    ]);
/*
 * Profile Breadcrumb End
 */

/*
 * Menu Sort Breadcrumb
 */
    BreadcrumbsManager::for('menu-sort.index', [
        ['label' => trans('menu_sort.title'), 'route' => 'menu-sort.index', 'show' => false],
    ]);
/*
 * Menu Sort Breadcrumb End
 */

/*
 * Setting Breadcrumb
 */
    // Settings index
    BreadcrumbsManager::for('settings.index', function ($group = null) {
        $crumbs = [
            ['label' => trans('setting.settings'), 'route' => 'settings.index', 'show' => false],
        ];

        if ($group && config("settings.{$group}")) {
            $crumbs = [
                ['label' => trans('setting.settings'), 'route' => 'settings.index'],
                ['label' => trans(config("settings.{$group}.label")), 'route' => null],
            ];
        }

        return $crumbs;
    });
/*
 * Setting Breadcrumb End
 */
