<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SystemMonitoringController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuSortController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'bn', 'ar', 'es'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

Route::middleware(['auth', 'demo', 'permission'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::resourceWithTrash('users', UserController::class);
        Route::resourceWithTrash('customers', CustomerController::class);

        Route::get('roles/{role}/permission', [RoleController::class, 'permission'])->name('roles.permission');
        Route::post('roles/{role}/permission', [RoleController::class, 'updatePermission'])->name('roles.permission_update');
        Route::resourceWithTrash('roles', RoleController::class);

        Route::delete('media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('media.bulk_destroy');
        Route::resource('media', MediaController::class)->only(['index', 'store', 'destroy']);

        Route::get('settings/{group?}', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');

        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{fileName}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('backups/{fileName}', [BackupController::class, 'destroy'])->name('backups.destroy');
        Route::post('backups/{fileName}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::post('backups-clean', [BackupController::class, 'clean'])->name('backups.clean');

        Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('menu-sort', [MenuSortController::class, 'index'])->name('menu-sort.index');
        Route::post('menu-sort', [MenuSortController::class, 'update'])->name('menu-sort.update');

        Route::get('system-monitoring', [SystemMonitoringController::class, 'index'])->name('system-monitoring.index');
        Route::post('system-monitoring/clear-cache', [SystemMonitoringController::class, 'clearCache'])->name('system-monitoring.clear-cache');
        Route::post('system-monitoring/clear-logs', [SystemMonitoringController::class, 'clearLogs'])->name('system-monitoring.clear-logs');

        Route::get('update', [UpdateController::class, 'index'])->name('update.index');
        Route::post('update/check', [UpdateController::class, 'check'])->name('update.check');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_read');
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::post('notifications-bulk', [NotificationController::class, 'bulkDestroy'])->name('notifications.bulk_delete');
        Route::post('notifications/send-test', [NotificationController::class, 'sendTest'])->name('notifications.send_test');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activity_log}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
        Route::delete('activity-logs/{activity_log}', [ActivityLogController::class, 'destroy'])->name('activity-logs.destroy');
        Route::delete('activity-logs-bulk', [ActivityLogController::class, 'bulkDestroy'])->name('activity-logs.bulk_delete');
        Route::delete('activity-logs-clear', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');
    });

require __DIR__ . '/auth.php';
require __DIR__ . '/install.php';
