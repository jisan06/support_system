<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\BackupService;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display a listing of all backups.
     */
    public function index()
    {
        $backups = $this->backupService->getAllBackups();

        return view('admin.backup.index', compact('backups'));
    }

    /**
     * Create a new backup.
     */
    public function store()
    {
        $option = request('option', '');

        try {
            $this->backupService->createBackup($option);

            activity('backup')
                ->event('backup_created')
                ->withProperties(['option' => $option ?: 'full'])
                ->log('Backup created: ' . ($option ?: 'full'));

            return redirect()
                ->route('backups.index')
                ->with('success', __('backup.backup_created'));
        } catch (\Exception $e) {
            return redirect()
                ->route('backups.index')
                ->with('error', __('backup.backup_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Download a backup file.
     */
    public function download(string $fileName)
    {
        $path = $this->backupService->getBackupPath($fileName);

        if (!$path) {
            abort(404);
        }

        activity('backup')
            ->event('backup_downloaded')
            ->withProperties(['file' => $fileName])
            ->log('Backup downloaded: ' . $fileName);

        return response()->download($path);
    }

    /**
     * Delete a backup file.
     */
    public function destroy(string $fileName)
    {
        $deleted = $this->backupService->deleteBackup($fileName);

        if (!$deleted) {
            return ResponseHelper::success([], __('backup.delete_failed'), 404);
        }

        activity('backup')
            ->event('backup_deleted')
            ->withProperties(['file' => $fileName])
            ->log('Backup deleted: ' . $fileName);

        return ResponseHelper::success([], __('backup.backup_deleted'));
    }

    /**
     * Restore a backup.
     */
    public function restore(string $fileName)
    {
        try {
            $this->backupService->restoreBackup($fileName);

            activity('backup')
                ->event('backup_restored')
                ->withProperties(['file' => $fileName])
                ->log('Backup restored: ' . $fileName);

            return redirect()
                ->route('backups.index')
                ->with('success', __('backup.backup_restored'));
        } catch (\Exception $e) {
            return redirect()
                ->route('backups.index')
                ->with('error', __('backup.restore_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Clean old backups.
     */
    public function clean()
    {
        try {
            $this->backupService->cleanBackups();

            activity('backup')
                ->event('backup_cleaned')
                ->log('Old backups cleaned');

            return redirect()
                ->route('backups.index')
                ->with('success', __('backup.backups_cleaned'));
        } catch (\Exception $e) {
            return redirect()
                ->route('backups.index')
                ->with('error', __('backup.clean_failed') . ': ' . $e->getMessage());
        }
    }
}