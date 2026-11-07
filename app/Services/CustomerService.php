<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * CustomerService - Customer-specific domain service.
 * Extends UserService to share consolidated user repository operations.
 */
class CustomerService extends UserService
{
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getActive()
    {
        return $this->getActiveCustomers();
    }
}


