<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, all destructive actions (create, update, delete) are
    | blocked and demo credentials are shown on the login page.
    | Set DEMO_MODE=true in your .env to activate.
    |
    */

    'enabled' => env('DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials displayed on the login page when demo mode is active.
    |
    */

    'credentials' => [
        [
            'role'     => 'Super Admin',
            'email'    => null, // filled from the installed super admin
            'password' => null, // super admin password is set during install
        ],
        [
            'role'     => 'Admin',
            'email'    => 'admin@demo.com',
            'password' => '12345678',
        ],
        [
            'role'     => 'Editor',
            'email'    => 'editor@demo.com',
            'password' => '12345678',
        ],
        [
            'role'     => 'Moderator',
            'email'    => 'moderator@demo.com',
            'password' => '12345678',
        ],
        [
            'role'     => 'Viewer',
            'email'    => 'viewer@demo.com',
            'password' => '12345678',
        ],
    ],

];