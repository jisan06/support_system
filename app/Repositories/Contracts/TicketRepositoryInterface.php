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
     * Get tickets for user (scoped to customer if customer account) with optional filters.
     *
     * @param array|int $filters
     */
    public function getTicketsForUser(User $user, array|int $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get ticket summary counts for a customer (Total, Open, In Progress, Resolved, Closed).
     *
     * @return array{total: int, open: int, in_progress: int, resolved: int, closed: int}
     */
    public function getCustomerTicketCounts(User $customer): array;

    /**
     * Get available ticket categories for creation and validation.
     *
     * @return array<int, string>
     */
    public function getAvailableCategories(): array;

    /**
     * Get all ticket categories including configured and any existing values in database.
     *
     * @return array<int, string>
     */
    public function getAllCategories(): array;
}

