<?php

namespace App\Repositories\Contracts;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive();

    public function restore($id);

    public function forceDelete($id);

    public function bulkDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function bulkForceDelete(array $ids): int;
}

