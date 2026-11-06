<?php

return [
    'title'                 => 'Actualización',
    'installed_version'     => 'Versión Instalada',
    'update_status'         => 'Estado de Actualización',
    'check_now'             => 'Buscar Actualización',
    'up_to_date'            => '¡Estás actualizado!',
    'running_latest'        => 'Estás usando la última versión.',
    'new_version_available' => '¡Versión v:version disponible!',
    'you_are_running'       => 'Actualmente estás usando v:version.',
    'view_details'          => 'Ver Detalles',
    'check_failed'          => 'No se pudo verificar actualizaciones.',
    'not_configured'        => 'La verificación de actualizaciones no está configurada',
    'not_configured_hint'   => 'La URL del servidor de actualizaciones no está configurada. Contacte al proveedor de la aplicación para obtener información de actualizaciones.',
    'how_to_update'         => 'Cómo Actualizar',
    'step_backup'           => 'Haz una copia de seguridad de tu base de datos y archivos',
    'step_download'         => 'Descarga la última versión',
    'step_replace'          => 'Reemplaza los archivos del proyecto (mantén tu archivo <code>.env</code>)',
    'step_migrate'          => 'Ejecuta <code>php artisan migrate --force</code>',
    'step_build'            => 'Ejecuta <code>npm install && npm run build</code>',
    'step_clear'            => 'Limpia cachés: <code>php artisan optimize:clear</code>',
];
