<?php

namespace App\Repositories\Contracts;

interface ActivityLogRepositoryInterface extends BaseRepositoryInterface
{
    public function bulkDelete(array $ids): int;

    public function deleteAll(): bool;
}