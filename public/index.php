<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Ensure Environment Is Ready (Fresh Install Support)
|--------------------------------------------------------------------------
|
| Like WordPress, we ensure .env and APP_KEY exist before the framework
| boots. This prevents encryption errors on first visit so the
| installer can load cleanly without any developer intervention.
|
*/

$envPath = __DIR__.'/../.env';

if (!file_exists($envPath)) {
    copy(__DIR__.'/../.env.example', $envPath);
}

$envContent = file_get_contents($envPath);

if (preg_match('/^APP_KEY=\s*$/m', $envContent)) {
    $key = 'base64:' . base64_encode(random_bytes(32));
    $envContent = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $envContent);
    file_put_contents($envPath, $envContent);
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
