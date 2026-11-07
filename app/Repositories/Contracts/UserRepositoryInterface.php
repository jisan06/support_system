<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive();

    public function getActiveAdmins(): Collection;

    public function getActiveCustomers(): Collection;

    public function findAdmin(int|string $id): User;

    public function findCustomer(int|string $id): User;

    public function restore($id);

    public function forceDelete($id);

    public function bulkDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function bulkForceDelete(array $ids): int;
}
