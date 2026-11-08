<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'created_by' => NULL,
                'name' => '{"en": "Super Admin"}',
                'email' => 'super_admin@gmail.com',
                'account_type' => 'admin',
                'status' => 'Active',
                'email_verified_at' => NULL,
                'password' => '$2y$12$3Fkk.VtPVPnF0XPZzwQefOozYN8eJtwwD5FCxtSlSAZqq3oLr4g5i',
                'remember_token' => NULL,
                'created_at' => '2026-03-17 11:35:03',
                'updated_at' => '2026-03-17 11:35:03',
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'created_by' => 1,
                'name' => '{"en": "Agent 1"}',
                'email' => 'agent1@gmail.com',
                'account_type' => 'admin',
                'status' => 'Active',
                'email_verified_at' => NULL,
                'password' => '$2y$12$nxbmgW80mSatjRBEn2OhtOX3BUWf8dOrK9JW0JFBvPMYaklFpM5ru',
                'remember_token' => NULL,
                'created_at' => '2026-11-08 10:46:19',
                'updated_at' => '2026-11-08 10:46:19',
                'deleted_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'created_by' => 1,
                'name' => '{"en": "Mahedy Hasan"}',
                'email' => 'hasan@gmail.com',
                'account_type' => 'customer',
                'status' => 'Active',
                'email_verified_at' => NULL,
                'password' => '$2y$12$wFt8Scl5VFzUyMe6dXcCtupX6jqJ/zZmUAf4ri0IwXrI0ymafuWte',
                'remember_token' => NULL,
                'created_at' => '2026-11-08 10:47:37',
                'updated_at' => '2026-11-08 10:47:37',
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}