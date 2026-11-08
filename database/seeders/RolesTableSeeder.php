<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => '{"en": "Super Admin"}',
                'level' => 1,
                'status' => 'Active',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:47:51',
                'updated_at' => '2026-03-17 11:47:51',
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 164,
                'name' => '{"en": "Support Agent"}',
                'level' => 4,
                'status' => 'Active',
                'created_by' => 1,
                'created_at' => '2026-11-08 06:15:19',
                'updated_at' => '2026-11-08 06:15:19',
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}