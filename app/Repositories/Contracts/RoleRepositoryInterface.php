<?php

namespace App\Repositories\Contracts;

use App\Models\Role;

interface RoleRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive();

    public function permission(Role $role, array $data);

    public function restore($id);

    public function forceDelete($id);

    public function bulkDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function bulkForceDelete(array $ids): int;
}