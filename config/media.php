<?php

return [
    'default_driver' => env('MEDIA_DRIVER', 'local'),

    'drivers' => [
        'local' => [
            'disk' => 'public',
        ],
        // 's3' => ['disk' => 's3'],
        // 'google' => ['disk' => 'google'],
    ],

    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf', 'doc', 'docx', 'mp4'],
    'max_size_kb' => 10240,
    'path_prefix' => 'media',
];
