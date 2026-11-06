<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('settings')->delete();
        
        \DB::table('settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'The Admin',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            1 => 
            array (
                'id' => 2,
                'group' => 'general',
                'key' => 'site_tagline',
                'value' => 'Starter Admin',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            2 =>
            array (
                'id' => 3,
                'group' => 'general',
                'key' => 'site_logo_light',
                'value' => NULL,
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            3 =>
            array (
                'id' => 4,
                'group' => 'general',
                'key' => 'site_logo_dark',
                'value' => NULL,
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            4 =>
            array (
                'id' => 5,
                'group' => 'general',
                'key' => 'site_logo_small',
                'value' => NULL,
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            5 =>
            array (
                'id' => 6,
                'group' => 'general',
                'key' => 'favicon',
                'value' => NULL,
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            6 => 
            array (
                'id' => 7,
                'group' => 'general',
                'key' => 'admin_logo_variant',
                'value' => 'light',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:52:46',
            ),
            7 => 
            array (
                'id' => 8,
                'group' => 'general',
                'key' => 'multi_language',
                'value' => 'on',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 12:32:17',
            ),
            8 => 
            array (
                'id' => 9,
                'group' => 'general',
                'key' => 'default_language',
                'value' => 'en',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 12:32:17',
            ),
            9 => 
            array (
                'id' => 10,
                'group' => 'general',
                'key' => 'timezone',
                'value' => 'Asia/Dhaka',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            10 => 
            array (
                'id' => 11,
                'group' => 'general',
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
            11 => 
            array (
                'id' => 12,
                'group' => 'general',
                'key' => 'time_format',
                'value' => 'h:i A',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-03-17 11:50:58',
            ),
        ));
        
        
    }
}