<?php

namespace App\Services;

use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SettingService extends BaseService
{
    private const CACHE_KEY = 'app_settings';

    public function __construct(SettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get all settings for a group as key => value pairs.
     */
    public function getByGroup(string $group): array
    {
        return $this->repository->getByGroup($group);
    }

    /**
     * Update all settings for a group and clear cache.
     */
    public function updateGroup(string $group, array $data): void
    {
        $this->transaction(function () use ($group, $data) {
            $fields = config("settings.{$group}.fields", []);

            $filtered = array_intersect_key($data, $fields);

            $this->repository->upsertGroup($group, $filtered);
        });

        $this->clearCache();
    }

    /**
     * Get a single setting value by key (cached).
     */
    public function get(string $key, $default = null)
    {
        if (!file_exists(storage_path('installed'))) {
            return $default;
        }

        try {
            $settings = Cache::rememberForever(self::CACHE_KEY, function () {
                return $this->repository->getAllKeyed();
            });

            return $settings[$key] ?? $default;
        } catch (\Exception) {
            return $default;
        }
    }

    /**
     * Clear the settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}