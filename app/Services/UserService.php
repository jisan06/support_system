<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Traits\BulkActionTrait;
use App\Services\Traits\SoftDeleteTrait;

class UserService extends BaseService
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getActiveAdmins()
    {
        return $this->repository->getActiveAdmins();
    }

    public function getActiveCustomers()
    {
        return $this->repository->getActiveCustomers();
    }
}