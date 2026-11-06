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
                'status' => 'Active',
                'email_verified_at' => NULL,
                'password' => '$2y$12$3Fkk.VtPVPnF0XPZzwQefOozYN8eJtwwD5FCxtSlSAZqq3oLr4g5i',
                'remember_token' => NULL,
                'created_at' => '2026-03-17 11:35:03',
                'updated_at' => '2026-03-17 11:35:03',
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}