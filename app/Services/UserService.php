<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Traits\BulkActionTrait;
use App\Services\Traits\SoftDeleteTrait;
use Illuminate\Database\Eloquent\Collection;

class UserService extends BaseService
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getActiveAdmins(): Collection
    {
        return $this->repository->getActiveAdmins();
    }

    public function getActiveCustomers(): Collection
    {
        return $this->repository->getActiveCustomers();
    }

    public function findAdmin(int|string $id): User
    {
        return $this->repository->findAdmin($id);
    }

    public function findCustomer(int|string $id): User
    {
        return $this->repository->findCustomer($id);
    }

    public function createAdmin(array $data): User
    {
        $data['account_type'] = User::TYPE_ADMIN;
        return $this->repository->create($data);
    }

    public function createCustomer(array $data): User
    {
        $data['account_type'] = User::TYPE_CUSTOMER;
        return $this->repository->create($data);
    }
}
