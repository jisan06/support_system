<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;

class BackupService
{
    /**
     * Get all backups from the configured disk.
     */
    public function getAllBackups(): array
    {
        $disk = Storage::disk('local');
        $backupName = config('backup.backup.name');
        $files = $disk->files($backupName);

        $backups = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $backups[] = (object) [
                    'path'      => $file,
                    'name'      => basename($file),
                    'size'      => $this->formatBytes($disk->size($file)),
                    'size_raw'  => $disk->size($file),
                    'date'      => date('Y-m-d H:i:s', $disk->lastModified($file)),
                    'timestamp' => $disk->lastModified($file),
                    'disk'      => 'local',
                ];
            }
        }

        // Sort by newest first
        usort($backups, fn ($a, $b) => $b->timestamp <=> $a->timestamp);

        return $backups;
    }

    /**
     * Create a new backup.
     */
    public function createBackup(string $option = ''): int
    {
        $artisan = base_path('artisan');
        $php = PHP_BINARY;

        $command = "\"{$php}\" \"{$artisan}\" backup:run --disable-notifications";

        if ($option === 'only-db') {
            $command .= ' --only-db';
        } elseif ($option === 'only-files') {
            $command .= ' --only-files';
        }

        // Run in background (Windows compatible)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B {$command}", 'r'));
        } else {
            exec("{$command} > /dev/null 2>&1 &");
        }

        return 0;
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $fileName): bool
    {
        $backupName = config('backup.backup.name');
        $path = $backupName . '/' . $fileName;
        $disk = Storage::disk('local');

        if ($disk->exists($path)) {
            return $disk->delete($path);
        }

        return false;
    }

    /**
     * Get backup file path for download.
     */
    public function getBackupPath(string $fileName): ?string
    {
        $backupName = config('backup.backup.name');
        $path = $backupName . '/' . $fileName;
        $disk = Storage::disk('local');

        if ($disk->exists($path)) {
            return $disk->path($path);
        }

        return null;
    }

    /**
     * Restore a database backup from a zip file.
     */
    public function restoreBackup(string $fileName): bool
    {
        $fullPath = $this->getBackupPath($fileName);

        if (!$fullPath) {
            return false;
        }

        $tempDir = storage_path('app/backup-temp/restore-' . time());

        try {
            // Extract the zip
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) !== true) {
                return false;
            }
            $zip->extractTo($tempDir);
            $zip->close();

            // Find the SQL file in extracted contents
            $sqlFile = $this->findSqlFile($tempDir);

            if (!$sqlFile) {
                $this->cleanupDirectory($tempDir);
                return false;
            }

            // Import the SQL file
            $this->importSql($sqlFile);

            // Cleanup temp directory
            $this->cleanupDirectory($tempDir);

            return true;
        } catch (\Exception $e) {
            $this->cleanupDirectory($tempDir);
            throw $e;
        }
    }

    /**
     * Find SQL file recursively in a directory.
     */
    protected function findSqlFile(string $directory): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'sql') {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Import SQL file into the database.
     */
    protected function importSql(string $sqlFilePath): void
    {
        $sql = file_get_contents($sqlFilePath);

        if ($sql === false) {
            throw new \RuntimeException('Could not read SQL file: ' . $sqlFilePath);
        }

        DB::unprepared($sql);
    }

    /**
     * Cleanup a directory recursively.
     */
    protected function cleanupDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            File::deleteDirectory($directory);
        }
    }

    /**
     * Clean old backups using the configured strategy.
     */
    public function cleanBackups(): int
    {
        return Artisan::call('backup:clean', [
            '--disable-notifications' => true,
        ]);
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}