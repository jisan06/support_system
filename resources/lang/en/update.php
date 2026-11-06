<?php

return [
    'title'                 => 'Update',
    'installed_version'     => 'Installed Version',
    'update_status'         => 'Update Status',
    'check_now'             => 'Check for Update',
    'up_to_date'            => 'You are up to date!',
    'running_latest'        => 'You are running the latest version.',
    'new_version_available' => 'Version v:version is available!',
    'you_are_running'       => 'You are currently running v:version.',
    'view_details'          => 'View Details',
    'check_failed'          => 'Could not check for updates.',
    'not_configured'        => 'Update checking is not configured',
    'not_configured_hint'   => 'The update server URL is not set. Please contact the application provider for update information.',
    'how_to_update'         => 'How to Update',
    'step_backup'           => 'Back up your database and files',
    'step_download'         => 'Download the latest version',
    'step_replace'          => 'Replace the project files (keep your <code>.env</code> file)',
    'step_migrate'          => 'Run <code>php artisan migrate --force</code>',
    'step_build'            => 'Run <code>npm install && npm run build</code>',
    'step_clear'            => 'Clear caches: <code>php artisan optimize:clear</code>',
];
