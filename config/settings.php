<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'general' => [
        'label' => 'setting.general',
        'icon' => 'bx bx-cog',
        'fields' => [
            'site_name' => [
                'type' => 'text',
                'label' => 'setting.site_name',
                'placeholder' => 'setting.site_name_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 6,
            ],
            'site_tagline' => [
                'type' => 'text',
                'label' => 'setting.site_tagline',
                'placeholder' => 'setting.site_tagline_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 6,
            ],
            'site_logo_light' => [
                'type' => 'image',
                'label' => 'setting.site_logo_light',
                'rules' => 'nullable|integer',
                'col' => 3,
            ],
            'site_logo_dark' => [
                'type' => 'image',
                'label' => 'setting.site_logo_dark',
                'rules' => 'nullable|integer',
                'col' => 3,
            ],
            'site_logo_small' => [
                'type' => 'image',
                'label' => 'setting.site_logo_small',
                'rules' => 'nullable|integer',
                'col' => 3,
            ],
            'favicon' => [
                'type' => 'image',
                'label' => 'setting.favicon',
                'rules' => 'nullable|integer',
                'col' => 3,
            ],
            'admin_logo_variant' => [
                'type' => 'select',
                'label' => 'setting.admin_logo_variant',
                'rules' => 'nullable|string|in:light,dark',
                'options' => [
                    'light' => 'Use Light Logo',
                    'dark' => 'Use Dark Logo',
                ],
                'col' => 4,
            ],
            'auth_logo_variant' => [
                'type' => 'select',
                'label' => 'setting.auth_logo_variant',
                'rules' => 'nullable|string|in:light,dark',
                'options' => [
                    'light' => 'Use Light Logo',
                    'dark' => 'Use Dark Logo',
                ],
                'default' => 'dark',
                'col' => 4,
            ],
            'multi_language' => [
                'type' => 'select',
                'label' => 'setting.multi_language',
                'rules' => 'nullable|string|in:on,off',
                'options' => [
                    'off' => 'Off',
                    'on' => 'On',
                ],
                'default' => 'off',
                'col' => 4,
            ],
            'default_language' => [
                'type' => 'select',
                'label' => 'setting.default_language',
                'rules' => 'nullable|string|max:10',
                'options' => 'languages',
                'col' => 4,
            ],
            'timezone' => [
                'type' => 'select',
                'label' => 'setting.timezone',
                'rules' => 'nullable|string|max:255',
                'options' => 'timezones',
                'default' => 'Asia/Dhaka',
                'col' => 4,
            ],
            'date_format' => [
                'type' => 'select',
                'label' => 'setting.date_format',
                'rules' => 'nullable|string|max:50',
                'options' => 'date_formats',
                'col' => 4,
            ],
            'time_format' => [
                'type' => 'select',
                'label' => 'setting.time_format',
                'rules' => 'nullable|string|max:50',
                'options' => 'time_formats',
                'col' => 4,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    */
    'seo' => [
        'label' => 'setting.seo',
        'icon' => 'bx bx-search-alt',
        'fields' => [
            'meta_title' => [
                'type' => 'text',
                'label' => 'setting.meta_title',
                'placeholder' => 'setting.meta_title_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 12,
            ],
            'meta_description' => [
                'type' => 'textarea',
                'label' => 'setting.meta_description',
                'placeholder' => 'setting.meta_description_placeholder',
                'rules' => 'nullable|string|max:500',
                'col' => 12,
            ],
            'meta_keywords' => [
                'type' => 'textarea',
                'label' => 'setting.meta_keywords',
                'placeholder' => 'setting.meta_keywords_placeholder',
                'rules' => 'nullable|string|max:500',
                'col' => 12,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact / Company Info
    |--------------------------------------------------------------------------
    */
    'contact' => [
        'label' => 'setting.contact',
        'icon' => 'bx bx-phone',
        'fields' => [
            'company_name' => [
                'type' => 'text',
                'label' => 'setting.company_name',
                'placeholder' => 'setting.company_name_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 6,
            ],
            'primary_email' => [
                'type' => 'email',
                'label' => 'setting.primary_email',
                'placeholder' => 'setting.primary_email_placeholder',
                'rules' => 'nullable|email|max:255',
                'col' => 6,
            ],
            'support_email' => [
                'type' => 'email',
                'label' => 'setting.support_email',
                'placeholder' => 'setting.support_email_placeholder',
                'rules' => 'nullable|email|max:255',
                'col' => 6,
            ],
            'phone' => [
                'type' => 'text',
                'label' => 'setting.phone',
                'placeholder' => 'setting.phone_placeholder',
                'rules' => 'nullable|string|max:50',
                'col' => 6,
            ],
            'address_street' => [
                'type' => 'text',
                'label' => 'setting.address_street',
                'placeholder' => 'setting.address_street_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 12,
            ],
            'address_city' => [
                'type' => 'text',
                'label' => 'setting.address_city',
                'placeholder' => 'setting.address_city_placeholder',
                'rules' => 'nullable|string|max:100',
                'col' => 6,
            ],
            'address_state' => [
                'type' => 'text',
                'label' => 'setting.address_state',
                'placeholder' => 'setting.address_state_placeholder',
                'rules' => 'nullable|string|max:100',
                'col' => 6,
            ],
            'address_country' => [
                'type' => 'text',
                'label' => 'setting.address_country',
                'placeholder' => 'setting.address_country_placeholder',
                'rules' => 'nullable|string|max:100',
                'col' => 6,
            ],
            'address_zip' => [
                'type' => 'text',
                'label' => 'setting.address_zip',
                'placeholder' => 'setting.address_zip_placeholder',
                'rules' => 'nullable|string|max:20',
                'col' => 6,
            ],
            'google_map_url' => [
                'type' => 'textarea',
                'label' => 'setting.google_map_url',
                'placeholder' => 'setting.google_map_url_placeholder',
                'rules' => 'nullable|string|max:1000',
                'col' => 12,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email / SMTP Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        'label' => 'setting.email',
        'icon' => 'bx bx-envelope',
        'fields' => [
            'mail_mailer' => [
                'type' => 'select',
                'label' => 'setting.mail_mailer',
                'rules' => 'nullable|string|in:smtp,sendmail,mailgun,ses,postmark,log',
                'options' => [
                    'smtp' => 'SMTP',
                    'sendmail' => 'Sendmail',
                    'mailgun' => 'Mailgun',
                    'ses' => 'Amazon SES',
                    'postmark' => 'Postmark',
                    'log' => 'Log (Testing)',
                ],
                'default' => 'smtp',
                'col' => 4,
            ],
            'mail_host' => [
                'type' => 'text',
                'label' => 'setting.mail_host',
                'placeholder' => 'setting.mail_host_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 4,
            ],
            'mail_port' => [
                'type' => 'text',
                'label' => 'setting.mail_port',
                'placeholder' => 'setting.mail_port_placeholder',
                'rules' => 'nullable|string|max:10',
                'col' => 4,
            ],
            'mail_username' => [
                'type' => 'text',
                'label' => 'setting.mail_username',
                'placeholder' => 'setting.mail_username_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 6,
            ],
            'mail_password' => [
                'type' => 'text',
                'label' => 'setting.mail_password',
                'placeholder' => 'setting.mail_password_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 6,
            ],
            'mail_encryption' => [
                'type' => 'select',
                'label' => 'setting.mail_encryption',
                'rules' => 'nullable|string|in:tls,ssl,null',
                'options' => [
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                    'null' => 'None',
                ],
                'default' => 'tls',
                'col' => 4,
            ],
            'mail_from_address' => [
                'type' => 'email',
                'label' => 'setting.mail_from_address',
                'placeholder' => 'setting.mail_from_address_placeholder',
                'rules' => 'nullable|email|max:255',
                'col' => 4,
            ],
            'mail_from_name' => [
                'type' => 'text',
                'label' => 'setting.mail_from_name',
                'placeholder' => 'setting.mail_from_name_placeholder',
                'rules' => 'nullable|string|max:255',
                'col' => 4,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA & Business Hours Settings
    |--------------------------------------------------------------------------
    */
    'sla' => [
        'label' => 'setting.sla',
        'fields' => [
            'business_timezone' => [
                'type' => 'select',
                'label' => 'setting.business_timezone',
                'rules' => 'nullable|string|max:255',
                'options' => 'timezones',
                'default' => 'UTC',
                'col' => 4,
            ],
            'business_working_days' => [
                'type' => 'select',
                'label' => 'setting.business_working_days',
                'rules' => 'nullable|string|max:50',
                'options' => [
                    '1,2,3,4,5' => 'Monday - Friday',
                    '0,1,2,3,4' => 'Sunday - Thursday',
                    '1,2,3,4,5,6' => 'Monday - Saturday',
                    '0,1,2,3,4,5,6' => 'Every Day (7 Days)',
                ],
                'default' => '1,2,3,4,5',
                'col' => 4,
            ],
            'business_start_time' => [
                'type' => 'time',
                'label' => 'setting.business_start_time',
                'rules' => 'nullable|string',
                'default' => '09:00',
                'col' => 2,
            ],
            'business_end_time' => [
                'type' => 'time',
                'label' => 'setting.business_end_time',
                'rules' => 'nullable|string',
                'default' => '17:00',
                'col' => 2,
            ],
            'business_holidays' => [
                'type' => 'textarea',
                'label' => 'setting.business_holidays',
                'placeholder' => 'setting.business_holidays_placeholder',
                'rules' => 'nullable|string|max:2000',
                'col' => 12,
            ],
            // Urgent Priority SLA Targets (minutes)
            'sla_urgent_first_response' => [
                'type' => 'number',
                'label' => 'setting.sla_urgent_first_response',
                'rules' => 'nullable|integer|min:1',
                'default' => 60,
                'col' => 3,
            ],
            'sla_urgent_resolution' => [
                'type' => 'number',
                'label' => 'setting.sla_urgent_resolution',
                'rules' => 'nullable|integer|min:1',
                'default' => 240,
                'col' => 3,
            ],
            // High Priority SLA Targets (minutes)
            'sla_high_first_response' => [
                'type' => 'number',
                'label' => 'setting.sla_high_first_response',
                'rules' => 'nullable|integer|min:1',
                'default' => 120,
                'col' => 3,
            ],
            'sla_high_resolution' => [
                'type' => 'number',
                'label' => 'setting.sla_high_resolution',
                'rules' => 'nullable|integer|min:1',
                'default' => 480,
                'col' => 3,
            ],
            // Medium Priority SLA Targets (minutes)
            'sla_medium_first_response' => [
                'type' => 'number',
                'label' => 'setting.sla_medium_first_response',
                'rules' => 'nullable|integer|min:1',
                'default' => 240,
                'col' => 3,
            ],
            'sla_medium_resolution' => [
                'type' => 'number',
                'label' => 'setting.sla_medium_resolution',
                'rules' => 'nullable|integer|min:1',
                'default' => 1440,
                'col' => 3,
            ],
            // Low Priority SLA Targets (minutes)
            'sla_low_first_response' => [
                'type' => 'number',
                'label' => 'setting.sla_low_first_response',
                'rules' => 'nullable|integer|min:1',
                'default' => 480,
                'col' => 3,
            ],
            'sla_low_resolution' => [
                'type' => 'number',
                'label' => 'setting.sla_low_resolution',
                'rules' => 'nullable|integer|min:1',
                'default' => 2880,
                'col' => 3,
            ],
            // Automatic Escalation Configuration
            'sla_escalation_enabled' => [
                'type' => 'select',
                'label' => 'setting.sla_escalation_enabled',
                'rules' => 'nullable|string|in:1,0',
                'options' => [
                    '1' => 'Enabled',
                    '0' => 'Disabled',
                ],
                'default' => '1',
                'col' => 4,
            ],
            'sla_escalation_level_1_action' => [
                'type' => 'select',
                'label' => 'setting.sla_escalation_level_1_action',
                'rules' => 'nullable|string|in:notify,reassign,notify_and_reassign',
                'options' => [
                    'notify' => 'Notify Assigned Staff',
                    'reassign' => 'Reassign to Higher-Level Staff',
                    'notify_and_reassign' => 'Notify & Reassign',
                ],
                'default' => 'notify',
                'col' => 4,
            ],
            'sla_escalation_level_2_action' => [
                'type' => 'select',
                'label' => 'setting.sla_escalation_level_2_action',
                'rules' => 'nullable|string|in:notify,reassign,notify_and_reassign',
                'options' => [
                    'notify' => 'Notify Assigned Staff',
                    'reassign' => 'Reassign to Higher-Level Staff',
                    'notify_and_reassign' => 'Notify & Reassign',
                ],
                'default' => 'notify_and_reassign',
                'col' => 4,
            ],
            'sla_escalation_level_1_minutes' => [
                'type' => 'number',
                'label' => 'setting.sla_escalation_level_1_minutes',
                'rules' => 'nullable|integer|min:0',
                'default' => 0,
                'col' => 6,
            ],
            'sla_escalation_level_2_minutes' => [
                'type' => 'number',
                'label' => 'setting.sla_escalation_level_2_minutes',
                'rules' => 'nullable|integer|min:1',
                'default' => 60,
                'col' => 6,
            ],
        ],
    ],

];
