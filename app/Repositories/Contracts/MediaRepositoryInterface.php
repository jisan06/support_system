<?php

namespace App\Repositories\Contracts;

interface MediaRepositoryInterface extends BaseRepositoryInterface
{
    public function findByMediable(string $mediableType, int $mediableId, ?string $collection = null);

    public function deleteByMediable(string $mediableType, int $mediableId, ?string $collection = null): int;
}
