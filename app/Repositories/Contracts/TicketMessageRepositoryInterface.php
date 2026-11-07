<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get paginated messages for a ticket visible to the given user.
     */
    public function getMessagesForTicket(Ticket $ticket, User $user, int $perPage = 20): LengthAwarePaginator;

    /**
     * Create a conversation message for a ticket.
     */
    public function createMessage(Ticket $ticket, array $data): TicketMessage;
}

