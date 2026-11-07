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
                'updated_at' => '2026-11-07 10:44:14',
            ),
            1 => 
            array (
                'id' => 2,
                'role_id' => 1,
                'slug' => 'create_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            2 => 
            array (
                'id' => 3,
                'role_id' => 1,
                'slug' => 'edit_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            3 => 
            array (
                'id' => 4,
                'role_id' => 1,
                'slug' => 'delete_user',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            4 => 
            array (
                'id' => 5,
                'role_id' => 1,
                'slug' => 'view_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            5 => 
            array (
                'id' => 6,
                'role_id' => 1,
                'slug' => 'create_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            6 => 
            array (
                'id' => 7,
                'role_id' => 1,
                'slug' => 'edit_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            7 => 
            array (
                'id' => 8,
                'role_id' => 1,
                'slug' => 'delete_role',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            8 => 
            array (
                'id' => 9,
                'role_id' => 1,
                'slug' => 'view_activity_log',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            9 => 
            array (
                'id' => 10,
                'role_id' => 1,
                'slug' => 'delete_activity_log',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            10 => 
            array (
                'id' => 11,
                'role_id' => 1,
                'slug' => 'view_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            11 => 
            array (
                'id' => 12,
                'role_id' => 1,
                'slug' => 'create_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            12 => 
            array (
                'id' => 13,
                'role_id' => 1,
                'slug' => 'restore_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            13 => 
            array (
                'id' => 14,
                'role_id' => 1,
                'slug' => 'delete_backup',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            14 => 
            array (
                'id' => 15,
                'role_id' => 1,
                'slug' => 'view_system_monitoring',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            15 => 
            array (
                'id' => 16,
                'role_id' => 1,
                'slug' => 'view_notification',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            16 => 
            array (
                'id' => 17,
                'role_id' => 1,
                'slug' => 'delete_notification',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            17 => 
            array (
                'id' => 18,
                'role_id' => 1,
                'slug' => 'view_setting_general',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            18 => 
            array (
                'id' => 19,
                'role_id' => 1,
                'slug' => 'edit_setting_general',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            19 => 
            array (
                'id' => 20,
                'role_id' => 1,
                'slug' => 'view_setting_seo',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            20 => 
            array (
                'id' => 21,
                'role_id' => 1,
                'slug' => 'edit_setting_seo',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            21 => 
            array (
                'id' => 22,
                'role_id' => 1,
                'slug' => 'view_setting_contact',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            22 => 
            array (
                'id' => 23,
                'role_id' => 1,
                'slug' => 'edit_setting_contact',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            23 => 
            array (
                'id' => 24,
                'role_id' => 1,
                'slug' => 'view_setting_email',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            24 => 
            array (
                'id' => 25,
                'role_id' => 1,
                'slug' => 'edit_setting_email',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            25 => 
            array (
                'id' => 26,
                'role_id' => 1,
                'slug' => 'view_media',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            26 => 
            array (
                'id' => 27,
                'role_id' => 1,
                'slug' => 'create_media',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            27 => 
            array (
                'id' => 28,
                'role_id' => 1,
                'slug' => 'delete_media',
                'created_at' => '2026-03-17 11:48:37',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            28 => 
            array (
                'id' => 29,
                'role_id' => 1,
                'slug' => 'view_customer',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            29 => 
            array (
                'id' => 30,
                'role_id' => 1,
                'slug' => 'create_customer',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            30 => 
            array (
                'id' => 31,
                'role_id' => 1,
                'slug' => 'edit_customer',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            31 => 
            array (
                'id' => 32,
                'role_id' => 1,
                'slug' => 'delete_customer',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            32 => 
            array (
                'id' => 33,
                'role_id' => 1,
                'slug' => 'view_ticket',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            33 => 
            array (
                'id' => 34,
                'role_id' => 1,
                'slug' => 'create_ticket',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            34 => 
            array (
                'id' => 35,
                'role_id' => 1,
                'slug' => 'reply_ticket',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
            35 => 
            array (
                'id' => 36,
                'role_id' => 1,
                'slug' => 'internal_note_ticket',
                'created_at' => '2026-11-07 10:44:14',
                'updated_at' => '2026-11-07 10:44:14',
            ),
        ));
        
        
    }
}