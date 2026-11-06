<?php

return [

    [
        'label' => 'Administration',
        'modules' => [
            [
                'label' => 'User',
                'permissions' => [
                    'view_user'   => 'permission.view_user',
                    'create_user' => 'permission.create_user',
                    'edit_user'   => 'permission.edit_user',
                    'delete_user' => 'permission.delete_user',
                ],
            ],
            [
                'label' => 'Role',
                'permissions' => [
                    'view_role'   => 'permission.view_role',
                    'create_role' => 'permission.create_role',
                    'edit_role'   => 'permission.edit_role',
                    'delete_role' => 'permission.delete_role',
                ],
            ],
        ],
    ],

    [
        'label' => 'Content',
        'modules' => [
            [
                'label' => 'Media',
                'permissions' => [
                    'view_media'   => 'permission.view_media',
                    'create_media' => 'permission.create_media',
                    'delete_media' => 'permission.delete_media',
                ],
            ],
        ],
    ],

    [
        'label' => 'System',
        'modules' => [
            [
                'label' => 'Activity Log',
                'permissions' => [
                    'view_activity_log'   => 'permission.view_activity_log',
                    'delete_activity_log' => 'permission.delete_activity_log',
                ],
            ],
            [
                'label' => 'Backup',
                'permissions' => [
                    'view_backup'    => 'permission.view_backup',
                    'create_backup'  => 'permission.create_backup',
                    'restore_backup' => 'permission.restore_backup',
                    'delete_backup'  => 'permission.delete_backup',
                ],
            ],
            [
                'label' => 'System Monitoring',
                'permissions' => [
                    'view_system_monitoring' => 'permission.view_system_monitoring',
                ],
            ],
            [
                'label' => 'Notification',
                'permissions' => [
                    'view_notification'   => 'permission.view_notification',
                    'delete_notification' => 'permission.delete_notification',
                ],
            ],
        ],
    ],

    [
        'label' => 'Settings',
        'modules' => [
            [
                'label' => 'General',
                'permissions' => [
                    'view_setting_general' => 'permission.view_setting_general',
                    'edit_setting_general' => 'permission.edit_setting_general',
                ],
            ],
            [
                'label' => 'SEO',
                'permissions' => [
                    'view_setting_seo' => 'permission.view_setting_seo',
                    'edit_setting_seo' => 'permission.edit_setting_seo',
                ],
            ],
            [
                'label' => 'Contact',
                'permissions' => [
                    'view_setting_contact' => 'permission.view_setting_contact',
                    'edit_setting_contact' => 'permission.edit_setting_contact',
                ],
            ],
            [
                'label' => 'Email',
                'permissions' => [
                    'view_setting_email' => 'permission.view_setting_email',
                    'edit_setting_email' => 'permission.edit_setting_email',
                ],
            ],
        ],
    ],

];
