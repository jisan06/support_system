<?php

namespace Tests\Traits;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;

trait CreatesAdminUser
{
    protected User $admin;
    protected Role $adminRole;

    protected function createAdminUser(array $permissions = []): User
    {
        $this->adminRole = Role::create([
            'name' => 'Super Admin',
            'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            RolePermission::create([
                'role_id' => $this->adminRole->id,
                'slug' => $permission,
            ]);
        }

        $this->admin = User::factory()->admin()->create(['status' => 'Active']);
        $this->admin->roles()->attach($this->adminRole->id, ['created_by' => $this->admin->id]);

        return $this->admin;
    }

    protected function createAllPermissionsAdmin(): User
    {
        return $this->createAdminUser([
            // Users
            'view_user', 'create_user', 'edit_user', 'delete_user',
            // Roles
            'view_role', 'create_role', 'edit_role', 'delete_role',
            // Media
            'view_media', 'create_media', 'delete_media',
            // Activity Logs
            'view_activity_log', 'delete_activity_log',
            // Backups
            'view_backup', 'create_backup', 'restore_backup', 'delete_backup',
            // System Monitoring
            'view_system_monitoring',
            // Notifications
            'view_notification', 'delete_notification',
            // Settings
            'view_setting_general', 'edit_setting_general',
            'view_setting_seo', 'edit_setting_seo',
            'view_setting_contact', 'edit_setting_contact',
            'view_setting_email', 'edit_setting_email',
        ]);
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        return $this->createAdminUser($permissions);
    }

    protected function createUserWithoutPermissions(): User
    {
        return $this->createAdminUser([]);
    }
}
