<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a ticket ensuring customer ownership when queried by customer.
     */
    public function findForUser(int|string $id, User $user): Ticket;

    /**
     * Get tickets for user (scoped to customer if customer account).
     */
    public function getTicketsForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get ticket summary counts for a customer (Total, Open, Resolved).
     *
     * @return array{total: int, open: int, resolved: int}
     */
    public function getCustomerTicketCounts(User $customer): array;
}

