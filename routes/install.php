<?php

use App\Http\Controllers\InstallController;
use App\Http\Middleware\RedirectIfInstalled;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', RedirectIfInstalled::class])
    ->prefix('install')
    ->group(function () {
        // Step views
        Route::get('/',            [InstallController::class, 'check'])->name('install.check');
        Route::get('/setup',       [InstallController::class, 'setup'])->name('install.setup');
        Route::get('/finish',      [InstallController::class, 'finish'])->name('install.finish');

        // AJAX endpoints
        Route::post('/test-database',   [InstallController::class, 'testDatabase'])->name('install.test-database');
        Route::post('/run',             [InstallController::class, 'runInstall'])->name('install.run');
    });
