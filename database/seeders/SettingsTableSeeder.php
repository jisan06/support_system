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
                'value' => 'TicketFlow',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-11-07 10:58:24',
            ),
            1 => 
            array (
                'id' => 2,
                'group' => 'general',
                'key' => 'site_tagline',
                'value' => '24/7 SUpport',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-11-07 10:58:09',
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
                'value' => 'off',
                'created_by' => 1,
                'created_at' => '2026-03-17 11:50:58',
                'updated_at' => '2026-11-07 12:30:52',
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
            12 => 
            array (
                'id' => 13,
                'group' => 'general',
                'key' => 'auth_logo_variant',
                'value' => 'dark',
                'created_by' => 1,
                'created_at' => '2026-11-07 10:58:09',
                'updated_at' => '2026-11-07 10:58:09',
            ),
            13 => 
            array (
                'id' => 14,
                'group' => 'sla',
                'key' => 'business_timezone',
                'value' => 'Asia/Dhaka',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            14 => 
            array (
                'id' => 15,
                'group' => 'sla',
                'key' => 'business_working_days',
                'value' => '0,1,2,3,4',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            15 => 
            array (
                'id' => 16,
                'group' => 'sla',
                'key' => 'business_start_time',
                'value' => '09:00',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            16 => 
            array (
                'id' => 17,
                'group' => 'sla',
                'key' => 'business_end_time',
                'value' => '18:00',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 11:48:10',
            ),
            17 => 
            array (
                'id' => 18,
                'group' => 'sla',
                'key' => 'business_holidays',
                'value' => NULL,
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            18 => 
            array (
                'id' => 19,
                'group' => 'sla',
                'key' => 'sla_urgent_first_response',
                'value' => '60',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            19 => 
            array (
                'id' => 20,
                'group' => 'sla',
                'key' => 'sla_urgent_resolution',
                'value' => '240',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            20 => 
            array (
                'id' => 21,
                'group' => 'sla',
                'key' => 'sla_high_first_response',
                'value' => '120',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            21 => 
            array (
                'id' => 22,
                'group' => 'sla',
                'key' => 'sla_high_resolution',
                'value' => '480',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            22 => 
            array (
                'id' => 23,
                'group' => 'sla',
                'key' => 'sla_medium_first_response',
                'value' => '240',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            23 => 
            array (
                'id' => 24,
                'group' => 'sla',
                'key' => 'sla_medium_resolution',
                'value' => '1440',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            24 => 
            array (
                'id' => 25,
                'group' => 'sla',
                'key' => 'sla_low_first_response',
                'value' => '480',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            25 => 
            array (
                'id' => 26,
                'group' => 'sla',
                'key' => 'sla_low_resolution',
                'value' => '2880',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            26 => 
            array (
                'id' => 27,
                'group' => 'sla',
                'key' => 'sla_escalation_enabled',
                'value' => '1',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            27 => 
            array (
                'id' => 28,
                'group' => 'sla',
                'key' => 'sla_escalation_level_1_action',
                'value' => 'notify',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            28 => 
            array (
                'id' => 29,
                'group' => 'sla',
                'key' => 'sla_escalation_level_2_action',
                'value' => 'notify_and_reassign',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            29 => 
            array (
                'id' => 30,
                'group' => 'sla',
                'key' => 'sla_escalation_level_1_minutes',
                'value' => '0',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
            30 => 
            array (
                'id' => 31,
                'group' => 'sla',
                'key' => 'sla_escalation_level_2_minutes',
                'value' => '60',
                'created_by' => 1,
                'created_at' => '2026-11-08 10:52:24',
                'updated_at' => '2026-11-08 10:52:24',
            ),
        ));
        
        
    }
}