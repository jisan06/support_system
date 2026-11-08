<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleUserTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('role_user')->delete();
        
        \DB::table('role_user')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 1,
                'role_id' => 1,
                'created_by' => 1,
                'created_at' => '2026-03-17 11:49:13',
                'updated_at' => '2026-03-17 11:49:13',
            ),
            1 => 
            array (
                'id' => 164,
                'user_id' => 408,
                'role_id' => 164,
                'created_by' => 1,
                'created_at' => '2026-11-08 06:16:05',
                'updated_at' => '2026-11-08 06:16:05',
            ),
            2 => 
            array (
                'id' => 165,
                'user_id' => 2,
                'role_id' => 164,
                'created_by' => 1,
                'created_at' => '2026-11-08 10:46:19',
                'updated_at' => '2026-11-08 10:46:19',
            ),
        ));
        
        
    }
}