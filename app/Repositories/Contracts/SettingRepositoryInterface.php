<?php

namespace App\Repositories\Contracts;

interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all settings for a given group as key => value pairs.
     */
    public function getByGroup(string $group): array;

    /**
     * Upsert multiple settings for a group.
     */
    public function upsertGroup(string $group, array $data): void;

    /**
     * Get all settings as key => value pairs.
     */
    public function getAllKeyed(): array;
}