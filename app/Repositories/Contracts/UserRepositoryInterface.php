<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive();

    public function getActiveAdmins();

    public function getActiveCustomers();

    public function restore($id);

    public function forceDelete($id);

    public function bulkDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function bulkForceDelete(array $ids): int;
}