<?php

return [
    'attachments' => [
        'disk' => env('TICKET_ATTACHMENT_DISK', 'local'),
        'max_files' => (int) env('TICKET_ATTACHMENT_MAX_FILES', 5),
        'max_size_kb' => (int) env('TICKET_ATTACHMENT_MAX_SIZE_KB', 10240), // 10MB
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'webp', 'gif',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'
        ],
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'collection' => 'ticket_attachments',
    ],
    'categories' => [
        'Technical Support',
        'Billing & Payments',
        'Account & Access',
        'General Inquiry',
    ],
];

