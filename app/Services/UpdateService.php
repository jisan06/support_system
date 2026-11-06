<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UpdateService
{
    protected const CACHE_KEY = 'app_update_check';
    protected const CACHE_HOURS = 12;

    /**
     * Check if update checking is configured.
     */
    public function isEnabled(): bool
    {
        return !empty(config('app-version.update_url'));
    }

    /**
     * Get the current application version.
     */
    public function getCurrentVersion(): string
    {
        return config('app-version.version', '1.0.0');
    }

    /**
     * Check for available updates (cached).
     */
    public function checkForUpdate(): array
    {
        $currentVersion = $this->getCurrentVersion();

        return Cache::remember(self::CACHE_KEY, 60 * 60 * self::CACHE_HOURS, function () use ($currentVersion) {
            return $this->fetchRemoteVersion($currentVersion);
        });
    }

    /**
     * Force check for updates (bypass cache).
     */
    public function forceCheck(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->checkForUpdate();
    }

    /**
     * Fetch the latest version from the remote server.
     */
    protected function fetchRemoteVersion(string $currentVersion): array
    {
        $default = [
            'update_available' => false,
            'current_version' => $currentVersion,
            'latest_version' => $currentVersion,
            'error' => null,
        ];

        $updateUrl = config('app-version.update_url');

        if (empty($updateUrl)) {
            return $default;
        }

        try {
            $response = Http::timeout(10)->get($updateUrl);

            if ($response->failed()) {
                $default['error'] = 'Could not reach the update server.';
                return $default;
            }

            $data = $response->json();

            if (!isset($data['version'])) {
                $default['error'] = 'Invalid response from update server.';
                return $default;
            }

            return [
                'update_available' => version_compare($data['version'], $currentVersion, '>'),
                'current_version' => $currentVersion,
                'latest_version' => $data['version'],
                'error' => null,
            ];
        } catch (\Exception $e) {
            $default['error'] = 'Could not connect to the update server.';
            return $default;
        }
    }
}
