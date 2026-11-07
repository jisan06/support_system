<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\Traits\SoftDeleteTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketService extends BaseService
{
    use SoftDeleteTrait;

    public function __construct(TicketRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function findForUser(int|string $id, User $user): Ticket
    {
        return $this->repository->findForUser($id, $user);
    }

    public function getTicketsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTicketsForUser($user, $perPage);
    }
}

