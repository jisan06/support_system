<?php

namespace App\Services;

use App\Enums\TicketMessageType;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repositories\Contracts\TicketMessageRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\Traits\SoftDeleteTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketMessageService extends BaseService
{
    use SoftDeleteTrait;

    protected TicketRepositoryInterface $ticketRepository;

    public function __construct(
        TicketMessageRepositoryInterface $repository,
        TicketRepositoryInterface $ticketRepository
    ) {
        parent::__construct($repository);
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Get paginated conversation history for a ticket.
     * Enforces customer ownership and internal note visibility.
     *
     * @throws AuthorizationException
     */
    public function getConversation(Ticket $ticket, User $user, int $perPage = 20): LengthAwarePaginator
    {
        $this->authorizeTicketAccess($ticket, $user);

        return $this->repository->getMessagesForTicket($ticket, $user, $perPage);
    }

    /**
     * Add a public reply to the ticket.
     *
     * @throws AuthorizationException
     */
    public function addReply(Ticket $ticket, User $sender, string $content): TicketMessage
    {
        $this->authorizeTicketAccess($ticket, $sender);

        return $this->transaction(function () use ($ticket, $sender, $content) {
            $message = $this->repository->createMessage($ticket, [
                'user_id' => $sender->id,
                'type'    => TicketMessageType::REPLY->value,
                'content' => $content,
            ]);

            $ticket->update(['last_reply_at' => now()]);

            return $message;
        });
    }

    /**
     * Add an internal note to the ticket.
     * Customers are strictly prohibited from creating internal notes.
     *
     * @throws AuthorizationException
     */
    public function addInternalNote(Ticket $ticket, User $sender, string $content): TicketMessage
    {
        // Enforce account_type: Only internal staff can add internal notes
        if (!$sender->isAdmin()) {
            throw new AuthorizationException('Customers are not permitted to create internal notes.');
        }

        return $this->transaction(function () use ($ticket, $sender, $content) {
            return $this->repository->createMessage($ticket, [
                'user_id' => $sender->id,
                'type'    => TicketMessageType::INTERNAL_NOTE->value,
                'content' => $content,
            ]);
        });
    }

    /**
     * Authorize access to a ticket.
     *
     * @throws AuthorizationException
     */
    protected function authorizeTicketAccess(Ticket $ticket, User $user): void
    {
        if ($user->isCustomer() && (int) $ticket->customer_id !== (int) $user->id) {
            throw new AuthorizationException('You are not authorized to access this ticket.');
        }
    }
}

