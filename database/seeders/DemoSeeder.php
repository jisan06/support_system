<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedUsers();
        $this->seedActivityLogs();
    }

    protected function seedRoles(): void
    {
        $now = now();

        $roleIds = [2, 3, 4, 5];
        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();

        $roles = [
            [
                'id' => 2,
                'name' => json_encode(['en' => 'Admin', 'bn' => 'অ্যাডমিন']),
                'level' => 2,
                'status' => 'Active',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => json_encode(['en' => 'Editor', 'bn' => 'সম্পাদক']),
                'level' => 3,
                'status' => 'Active',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => json_encode(['en' => 'Moderator', 'bn' => 'মডারেটর']),
                'level' => 3,
                'status' => 'Active',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => json_encode(['en' => 'Viewer', 'bn' => 'দর্শক']),
                'level' => 4,
                'status' => 'Active',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('roles')->insert($roles);

        // Admin: all permissions except system monitoring and backups
        $adminPermissions = [
            'view_user', 'create_user', 'edit_user', 'delete_user',
            'view_role', 'create_role', 'edit_role', 'delete_role',
            'view_media', 'create_media', 'delete_media',
            'view_activity_log', 'delete_activity_log',
            'view_notification', 'delete_notification',
            'view_setting_general', 'edit_setting_general',
            'view_setting_seo', 'edit_setting_seo',
            'view_setting_contact', 'edit_setting_contact',
            'view_setting_email', 'edit_setting_email',
        ];

        // Editor: content management only
        $editorPermissions = [
            'view_user',
            'view_role',
            'view_media', 'create_media', 'delete_media',
            'view_activity_log',
            'view_notification',
            'view_setting_general',
            'view_setting_seo', 'edit_setting_seo',
            'view_setting_contact', 'edit_setting_contact',
        ];

        // Moderator: user management + content
        $moderatorPermissions = [
            'view_user', 'create_user', 'edit_user',
            'view_role',
            'view_media', 'create_media',
            'view_activity_log',
            'view_notification', 'delete_notification',
        ];

        // Viewer: read-only
        $viewerPermissions = [
            'view_user',
            'view_role',
            'view_media',
            'view_activity_log',
            'view_notification',
            'view_setting_general',
            'view_setting_seo',
            'view_setting_contact',
        ];

        $permissionMap = [
            2 => $adminPermissions,
            3 => $editorPermissions,
            4 => $moderatorPermissions,
            5 => $viewerPermissions,
        ];

        $permissionRows = [];
        foreach ($permissionMap as $roleId => $permissions) {
            foreach ($permissions as $slug) {
                $permissionRows[] = [
                    'role_id' => $roleId,
                    'slug' => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('role_permissions')->insert($permissionRows);
    }

    protected function seedUsers(): void
    {
        $now = now();
        $password = Hash::make('12345678');

        $demoUserIds = range(2, 11);
        DB::table('role_user')->whereIn('user_id', $demoUserIds)->delete();
        DB::table('users')->whereIn('id', $demoUserIds)->delete();

        $users = [
            ['name' => 'Rahim Ahmed',     'email' => 'admin@demo.com',       'role_id' => 2],
            ['name' => 'Sarah Johnson',    'email' => 'editor@demo.com',      'role_id' => 3],
            ['name' => 'Karim Hassan',     'email' => 'moderator@demo.com',   'role_id' => 4],
            ['name' => 'Emily Chen',       'email' => 'viewer@demo.com',      'role_id' => 5],
            ['name' => 'Tanvir Hossain',   'email' => 'tanvir@demo.com',      'role_id' => 3],
            ['name' => 'Maria Garcia',     'email' => 'maria@demo.com',       'role_id' => 4],
            ['name' => 'David Wilson',     'email' => 'david@demo.com',       'role_id' => 5],
            ['name' => 'Fatima Ali',       'email' => 'fatima@demo.com',      'role_id' => 3],
            ['name' => 'James Brown',      'email' => 'james@demo.com',       'role_id' => 5],
            ['name' => 'Ayesha Khan',      'email' => 'ayesha@demo.com',      'role_id' => 2],
        ];

        $roleUserRows = [];

        foreach ($users as $index => $userData) {
            $userId = $index + 2; // Start from ID 2 (ID 1 is Super Admin)

            DB::table('users')->insert([
                'id' => $userId,
                'name' => json_encode(['en' => $userData['name']]),
                'email' => $userData['email'],
                'account_type' => 'admin',
                'password' => $password,
                'status' => $index < 8 ? 'Active' : 'Inactive',
                'email_verified_at' => $now,
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(rand(1, 30)),
                'updated_at' => $now,
            ]);

            $roleUserRows[] = [
                'user_id' => $userId,
                'role_id' => $userData['role_id'],
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('role_user')->insert($roleUserRows);
    }

    protected function seedActivityLogs(): void
    {
        $now = now();

        DB::table('activity_log')->truncate();

        $logs = [
            ['log_name' => 'user',    'event' => 'created',  'description' => 'User Rahim Ahmed was created',     'causer_id' => 1, 'subject_type' => 'App\\Models\\User', 'subject_id' => 2, 'days_ago' => 25],
            ['log_name' => 'role',    'event' => 'created',  'description' => 'Role Admin was created',            'causer_id' => 1, 'subject_type' => 'App\\Models\\Role', 'subject_id' => 2, 'days_ago' => 28],
            ['log_name' => 'role',    'event' => 'created',  'description' => 'Role Editor was created',           'causer_id' => 1, 'subject_type' => 'App\\Models\\Role', 'subject_id' => 3, 'days_ago' => 28],
            ['log_name' => 'user',    'event' => 'created',  'description' => 'User Sarah Johnson was created',    'causer_id' => 1, 'subject_type' => 'App\\Models\\User', 'subject_id' => 3, 'days_ago' => 20],
            ['log_name' => 'user',    'event' => 'updated',  'description' => 'User Rahim Ahmed was updated',      'causer_id' => 2, 'subject_type' => 'App\\Models\\User', 'subject_id' => 2, 'days_ago' => 15],
            ['log_name' => 'user',    'event' => 'created',  'description' => 'User Karim Hassan was created',     'causer_id' => 1, 'subject_type' => 'App\\Models\\User', 'subject_id' => 4, 'days_ago' => 18],
            ['log_name' => 'role',    'event' => 'updated',  'description' => 'Role Editor was updated',           'causer_id' => 1, 'subject_type' => 'App\\Models\\Role', 'subject_id' => 3, 'days_ago' => 10],
            ['log_name' => 'setting', 'event' => 'updated',  'description' => 'Settings (general) were updated',   'causer_id' => 1, 'subject_type' => null,                'subject_id' => null, 'days_ago' => 7],
            ['log_name' => 'user',    'event' => 'created',  'description' => 'User Emily Chen was created',       'causer_id' => 2, 'subject_type' => 'App\\Models\\User', 'subject_id' => 5, 'days_ago' => 12],
            ['log_name' => 'user',    'event' => 'created',  'description' => 'User Fatima Ali was created',       'causer_id' => 1, 'subject_type' => 'App\\Models\\User', 'subject_id' => 9, 'days_ago' => 5],
            ['log_name' => 'role',    'event' => 'permission_updated', 'description' => 'Role Moderator permissions were updated', 'causer_id' => 1, 'subject_type' => 'App\\Models\\Role', 'subject_id' => 4, 'days_ago' => 8],
            ['log_name' => 'user',    'event' => 'updated',  'description' => 'User Maria Garcia was updated',     'causer_id' => 1, 'subject_type' => 'App\\Models\\User', 'subject_id' => 7, 'days_ago' => 3],
        ];

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                'log_name' => $log['log_name'],
                'description' => $log['description'],
                'subject_type' => $log['subject_type'],
                'subject_id' => $log['subject_id'],
                'event' => $log['event'],
                'causer_type' => 'App\\Models\\User',
                'causer_id' => $log['causer_id'],
                'properties' => json_encode([]),
                'batch_uuid' => null,
                'created_at' => $now->copy()->subDays($log['days_ago']),
                'updated_at' => $now->copy()->subDays($log['days_ago']),
            ];
        }

        DB::table('activity_log')->insert($rows);
    }
}
