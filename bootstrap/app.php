<?php

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\DemoMode;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Helpers\ResponseHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\AppServiceProvider::class,
    ])
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        using: function () {
            require base_path('routes/macros.php');

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
            CheckInstalled::class,
        ]);

        $middleware->alias([
            'permission' => CheckPermission::class,
            'demo' => DemoMode::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return ResponseHelper::error(__('common.unauthorized'), 403);
            }

            return redirect()->back()->with('error', __('common.unauthorized'));
        });

        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() && !($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException)) {
                return ResponseHelper::error($e->getMessage(), 500);
            }
        });

        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })->create();
