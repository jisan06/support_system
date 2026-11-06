<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all settings for a given group as key => value pairs.
     */
    public function getByGroup(string $group): array
    {
        return $this->model
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Upsert multiple settings for a group.
     */
    public function upsertGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            $this->model->updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * Get all settings as key => value pairs.
     */
    public function getAllKeyed(): array
    {
        return $this->model
            ->pluck('value', 'key')
            ->toArray();
    }
}