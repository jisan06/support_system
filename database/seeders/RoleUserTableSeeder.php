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
        ));
        
        
    }
}