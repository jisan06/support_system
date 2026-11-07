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
     * Get paginated tickets for user.
     */
    public function getTicketsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['customer', 'assignedTo']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        return $query->latest('last_reply_at')->latest('id')->paginate($perPage);
    }
}

