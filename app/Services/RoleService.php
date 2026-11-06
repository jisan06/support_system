<?php

namespace App\Services;

use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\Traits\BulkActionTrait;
use App\Services\Traits\SoftDeleteTrait;

class RoleService extends BaseService
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(RoleRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getActiveRoles()
    {
        return $this->repository->getActive(auth()->user()->roleLevel());
    }

    public function permission($role, array $data)
    {
        return $this->transaction(
            fn () => $this->repository->permission($role, $data)
        );
    }
}