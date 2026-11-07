<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Traits\SoftDeleteTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketRepository extends BaseRepository implements TicketRepositoryInterface
{
    use SoftDeleteTrait;

    public function __construct(Ticket $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a ticket ensuring customer ownership when accessed by a customer.
     */
    public function findForUser(int|string $id, User $user): Ticket
    {
        $query = $this->model->newQuery();

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        return $query->with(['customer', 'assignedTo'])->findOrFail($id);
    }

    /**
     * Get paginated tickets for user with optional filters.
     */
    public function getTicketsForUser(User $user, array|int $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (is_int($filters)) {
            $perPage = $filters;
            $filters = [];
        }

        $query = $this->model->newQuery()->with(['customer', 'assignedTo']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        return $query->latest('last_reply_at')->latest('id')->paginate($perPage);
    }

    /**
     * Get ticket summary counts for a customer (Total, Open, Resolved).
     *
     * @return array{total: int, open: int, resolved: int}
     */
    public function getCustomerTicketCounts(User $customer): array
    {
        return [
            'total'    => $this->model->newQuery()->forCustomer($customer)->count(),
            'open'     => $this->model->newQuery()->forCustomer($customer)->where('status', 'open')->count(),
            'resolved' => $this->model->newQuery()->forCustomer($customer)->where('status', 'resolved')->count(),
        ];
    }
}

