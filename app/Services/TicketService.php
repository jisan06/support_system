<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Services\Traits\SoftDeleteTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TicketService extends BaseService
{
    use SoftDeleteTrait;

    protected TicketMessageService $messageService;

    public function __construct(
        TicketRepositoryInterface $repository,
        TicketMessageService $messageService
    ) {
        parent::__construct($repository);
        $this->messageService = $messageService;
    }

    public function findForUser(int|string $id, User $user): Ticket
    {
        return $this->repository->findForUser($id, $user);
    }

    public function getTicketsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTicketsForUser($user, $perPage);
    }

    /**
     * Create a ticket with an initial message and optional attachments.
     * Reuses existing conversation and attachment pipeline.
     */
    public function createTicket(array $data, User $creator, array $attachments = [], array $mediaIds = []): Ticket
    {
        return $this->transaction(function () use ($data, $creator, $attachments, $mediaIds) {
            $ticketNumber = $this->generateTicketNumber();

            $ticket = $this->repository->create([
                'ticket_number' => $ticketNumber,
                'customer_id'   => $data['customer_id'],
                'assigned_to'   => $data['assigned_to'] ?? null,
                'subject'       => $data['subject'],
                'status'        => 'open',
                'priority'      => $data['priority'] ?? 'medium',
                'department'    => $data['department'] ?? null,
                'last_reply_at' => now(),
            ]);

            // Create opening message and store any attachments / media
            $mediaIds = !empty($mediaIds) ? $mediaIds : ($data['media_ids'] ?? []);
            $this->messageService->addReply($ticket, $creator, $data['description'], $attachments, $mediaIds);

            return $ticket;
        });
    }

    /**
     * Generate unique ticket number automatically.
     */
    public function generateTicketNumber(): string
    {
        do {
            $number = 'TCK-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Ticket::where('ticket_number', $number)->exists());

        return $number;
    }
}
