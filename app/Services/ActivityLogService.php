<?php

namespace App\Services;

use App\Repositories\Contracts\ActivityLogRepositoryInterface;

class ActivityLogService extends BaseService
{
    public function __construct(ActivityLogRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->transaction(fn () => $this->repository->bulkDelete($ids));
    }

    public function deleteAll(): bool
    {
        return $this->transaction(fn () => $this->repository->deleteAll());
    }
}