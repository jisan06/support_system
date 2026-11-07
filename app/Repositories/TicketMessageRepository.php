<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repositories\Contracts\TicketMessageRepositoryInterface;
use App\Repositories\Traits\SoftDeleteTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketMessageRepository extends BaseRepository implements TicketMessageRepositoryInterface
{
    use SoftDeleteTrait;

    public function __construct(TicketMessage $model)
    {
        parent::__construct($model);
    }

    /**
     * Get paginated messages for a ticket visible to the given user.
     * Prevents N+1 queries by eager loading sender and avatar media.
     */
    public function getMessagesForTicket(Ticket $ticket, User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->where('ticket_id', $ticket->id)
            ->visibleTo($user)
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'name', 'email', 'account_type');
                },
                'user.media',
                'media',
            ])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Create a conversation message for a ticket.
     */
    public function createMessage(Ticket $ticket, array $data): TicketMessage
    {
        $data['ticket_id'] = $ticket->id;

        return $this->model->create($data);
    }
}

