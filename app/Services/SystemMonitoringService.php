<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class SystemMonitoringService
{
    public function getServerInfo(): array
    {
        return [
            'php_version'       => PHP_VERSION,
            'laravel_version'   => app()->version(),
            'server_software'   => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'server_os'         => PHP_OS,
            'server_ip'         => $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()),
            'document_root'     => $_SERVER['DOCUMENT_ROOT'] ?? base_path(),
            'max_upload_size'   => ini_get('upload_max_filesize'),
            'max_post_size'     => ini_get('post_max_size'),
            'max_execution_time'=> ini_get('max_execution_time') . 's',
            'memory_limit'      => ini_get('memory_limit'),
            'timezone'          => config('app.timezone'),
        ];
    }

    public function getDiskUsage(): array
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;

        return [
            'total'      => $this->formatBytes($total),
            'used'       => $this->formatBytes($used),
            'free'       => $this->formatBytes($free),
            'percentage' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
        ];
    }

    public function getStorageSizes(): array
    {
        $items = [];

        $folders = [
            'Uploads'  => storage_path('app/public'),
            'Backups'  => storage_path('app/' . config('backup.backup.name', 'backups')),
            'Logs'     => storage_path('logs'),
            'Cache'    => storage_path('framework/cache'),
            'Sessions' => storage_path('framework/sessions'),
            'Views'    => storage_path('framework/views'),
        ];

        foreach ($folders as $label => $path) {
            $items[] = [
                'label' => $label,
                'size'  => is_dir($path) ? $this->formatBytes($this->directorySize($path)) : '0 B',
                'path'  => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path),
            ];
        }

        return $items;
    }

    public function getDatabaseInfo(): array
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        $dbVersion = 'N/A';
        $dbSize = '0 MB';
        $tableCount = 0;
        $largestTables = [];

        try {
            $versionResult = DB::select("SELECT VERSION() AS version");
            if (!empty($versionResult)) {
                $vRow = array_change_key_case((array) $versionResult[0], CASE_LOWER);
                $dbVersion = $vRow['version'] ?? 'N/A';
            }

            if (!empty($dbConfig['database'])) {
                $sizeResult = DB::select("SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                    FROM information_schema.tables
                    WHERE table_schema = ?", [$dbConfig['database']]);
                if (!empty($sizeResult)) {
                    $sRow = array_change_key_case((array) $sizeResult[0], CASE_LOWER);
                    $dbSize = ($sRow['size_mb'] ?? 0) . ' MB';
                }

                $tableCountResult = DB::select("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ?", [$dbConfig['database']]);
                if (!empty($tableCountResult)) {
                    $cRow = array_change_key_case((array) $tableCountResult[0], CASE_LOWER);
                    $tableCount = (int) ($cRow['cnt'] ?? 0);
                }

                $tablesResult = DB::select("SELECT
                    table_name,
                    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    table_rows
                    FROM information_schema.tables
                    WHERE table_schema = ?
                    ORDER BY (data_length + index_length) DESC
                    LIMIT 5", [$dbConfig['database']]);

                foreach ($tablesResult as $table) {
                    $tableData = array_change_key_case((array) $table, CASE_LOWER);
                    $largestTables[] = (object) [
                        'table_name' => $tableData['table_name'] ?? 'N/A',
                        'table_rows' => (int) ($tableData['table_rows'] ?? 0),
                        'size_mb'    => $tableData['size_mb'] ?? '0.00',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Gracefully handle database query errors or non-MySQL drivers
        }

        return [
            'driver'         => $connection,
            'version'        => $dbVersion,
            'database'       => $dbConfig['database'] ?? 'N/A',
            'host'           => $dbConfig['host'] ?? 'N/A',
            'size'           => $dbSize,
            'tables'         => $tableCount,
            'largest_tables' => $largestTables,
        ];
    }

    public function getLaravelHealth(): array
    {
        return [
            [
                'label'  => 'Environment',
                'value'  => config('app.env'),
                'status' => config('app.env') === 'production' ? 'success' : 'warning',
            ],
            [
                'label'  => 'Debug Mode',
                'value'  => config('app.debug') ? 'ON' : 'OFF',
                'status' => config('app.debug') ? 'danger' : 'success',
            ],
            [
                'label'  => 'Cache Driver',
                'value'  => config('cache.default'),
                'status' => 'info',
            ],
            [
                'label'  => 'Session Driver',
                'value'  => config('session.driver'),
                'status' => 'info',
            ],
            [
                'label'  => 'Queue Driver',
                'value'  => config('queue.default'),
                'status' => 'info',
            ],
            [
                'label'  => 'Mail Driver',
                'value'  => config('mail.default'),
                'status' => 'info',
            ],
            [
                'label'  => 'Config Cached',
                'value'  => file_exists(base_path('bootstrap/cache/config.php')) ? 'Yes' : 'No',
                'status' => file_exists(base_path('bootstrap/cache/config.php')) ? 'success' : 'warning',
            ],
            [
                'label'  => 'Routes Cached',
                'value'  => file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'Yes' : 'No',
                'status' => file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'success' : 'warning',
            ],
            [
                'label'  => 'Maintenance Mode',
                'value'  => app()->isDownForMaintenance() ? 'ON' : 'OFF',
                'status' => app()->isDownForMaintenance() ? 'danger' : 'success',
            ],
        ];
    }

    public function getLogInfo(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return ['size' => '0 B', 'entries' => []];
        }

        $size = $this->formatBytes(filesize($logPath));

        // Get last 10 error lines
        $entries = [];
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - 500);
        $file->seek($startLine);

        $buffer = [];
        while (!$file->eof()) {
            $line = $file->fgets();
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s\w+\.(ERROR|WARNING|CRITICAL):\s(.+)/', $line, $matches)) {
                $buffer[] = [
                    'date'    => $matches[1],
                    'level'   => $matches[2],
                    'message' => \Illuminate\Support\Str::limit($matches[3], 120),
                ];
            }
        }

        $entries = array_slice(array_reverse($buffer), 0, 10);

        return ['size' => $size, 'entries' => $entries];
    }

    public function clearCache(): void
    {
        Cache::flush();
        \Illuminate\Support\Facades\Artisan::call('view:clear');
    }

    public function clearLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }
    }

    protected function directorySize(string $path): int
    {
        $size = 0;

        if (!is_dir($path)) {
            return 0;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

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