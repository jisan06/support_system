<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'demo'])->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('admin/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('login', fn () => redirect()->route('login'));
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
