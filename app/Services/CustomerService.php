<?php

namespace App\Services;

use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\Traits\BulkActionTrait;
use App\Services\Traits\SoftDeleteTrait;

class CustomerService extends BaseService
{
    use SoftDeleteTrait, BulkActionTrait;

    public function __construct(CustomerRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getActive()
    {
        return $this->repository->getActive();
    }
}

