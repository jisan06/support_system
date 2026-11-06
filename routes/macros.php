<?php

use Illuminate\Support\Facades\Route;

// ✅ Register the macro FIRST
Route::macro('resourceWithTrash', function ($name, $controller) {
    Route::get("$name/trash", [$controller, 'trash'])->name("$name.trash");
    Route::post("$name/bulk-delete", [$controller, 'bulkDestroy'])->name("$name.bulk_delete");

    Route::post("$name/{id}/restore", [$controller, 'restore'])->name("$name.restore");
    Route::post("$name/bulk-restore", [$controller, 'bulkRestore'])->name("$name.bulk_restore");

    Route::delete("$name/{id}/force-delete", [$controller, 'forceDelete'])->name("$name.force-delete");
    Route::post("$name/bulk-force-delete", [$controller, 'bulkForceDelete'])->name("$name.bulk_force_delete");
    Route::resource($name, $controller);
});
