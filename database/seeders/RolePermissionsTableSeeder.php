<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolePermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('role_permissions')->delete();
        
        \DB::table('role_permissions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'role_id' => 1,
                'slug' => 'view_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            1 => 
            array (
                'id' => 2,
                'role_id' => 1,
                'slug' => 'create_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            2 => 
            array (
                'id' => 3,
                'role_id' => 1,
                'slug' => 'edit_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            3 => 
            array (
                'id' => 4,
                'role_id' => 1,
                'slug' => 'delete_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            4 => 
            array (
                'id' => 5,
                'role_id' => 1,
                'slug' => 'view_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            5 => 
            array (
                'id' => 6,
                'role_id' => 1,
                'slug' => 'create_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            6 => 
            array (
                'id' => 7,
                'role_id' => 1,
                'slug' => 'edit_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            7 => 
            array (
                'id' => 8,
                'role_id' => 1,
                'slug' => 'delete_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            8 => 
            array (
                'id' => 9,
                'role_id' => 1,
                'slug' => 'view_activity_log',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            9 => 
            array (
                'id' => 10,
                'role_id' => 1,
                'slug' => 'delete_activity_log',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            10 => 
            array (
                'id' => 11,
                'role_id' => 1,
                'slug' => 'view_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            11 => 
            array (
                'id' => 12,
                'role_id' => 1,
                'slug' => 'create_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            12 => 
            array (
                'id' => 13,
                'role_id' => 1,
                'slug' => 'restore_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            13 => 
            array (
                'id' => 14,
                'role_id' => 1,
                'slug' => 'delete_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            14 => 
            array (
                'id' => 15,
                'role_id' => 1,
                'slug' => 'view_system_monitoring',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            15 => 
            array (
                'id' => 16,
                'role_id' => 1,
                'slug' => 'view_notification',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            16 => 
            array (
                'id' => 17,
                'role_id' => 1,
                'slug' => 'delete_notification',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            17 => 
            array (
                'id' => 18,
                'role_id' => 1,
                'slug' => 'view_setting_general',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            18 => 
            array (
                'id' => 19,
                'role_id' => 1,
                'slug' => 'edit_setting_general',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            19 => 
            array (
                'id' => 20,
                'role_id' => 1,
                'slug' => 'view_setting_seo',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            20 => 
            array (
                'id' => 21,
                'role_id' => 1,
                'slug' => 'edit_setting_seo',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            21 => 
            array (
                'id' => 22,
                'role_id' => 1,
                'slug' => 'view_setting_contact',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            22 => 
            array (
                'id' => 23,
                'role_id' => 1,
                'slug' => 'edit_setting_contact',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            23 => 
            array (
                'id' => 24,
                'role_id' => 1,
                'slug' => 'view_setting_email',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            24 => 
            array (
                'id' => 25,
                'role_id' => 1,
                'slug' => 'edit_setting_email',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            25 => 
            array (
                'id' => 26,
                'role_id' => 1,
                'slug' => 'view_media',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            26 => 
            array (
                'id' => 27,
                'role_id' => 1,
                'slug' => 'create_media',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
            27 => 
            array (
                'id' => 28,
                'role_id' => 1,
                'slug' => 'delete_media',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-03-17 11:48:37',
            ),
        ));
        
        
    }
}