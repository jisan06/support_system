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
    protected SlaService $slaService;

    public function __construct(
        TicketRepositoryInterface $repository,
        TicketMessageService $messageService,
        SlaService $slaService
    ) {
        parent::__construct($repository);
        $this->messageService = $messageService;
        $this->slaService = $slaService;
    }

    public function findForUser(int|string $id, User $user): Ticket
    {
        return $this->repository->findForUser($id, $user);
    }

    public function getTicketsForUser(User $user, array|int $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTicketsForUser($user, $filters, $perPage);
    }

    /**
     * Get configured ticket categories for creation and validation.
     *
     * @return array<int, string>
     */
    public function getAvailableCategories(): array
    {
        return $this->repository->getAvailableCategories();
    }

    /**
     * Get all ticket categories including configured and existing values in database.
     *
     * @return array<int, string>
     */
    public function getAllCategories(): array
    {
        return $this->repository->getAllCategories();
    }

    /**
     * Create a ticket with an initial message and optional attachments.
     * Reuses existing conversation and attachment pipeline.
     */
    public function createTicket(array $data, User $creator, array $attachments = [], array $mediaIds = []): Ticket
    {
        return $this->transaction(function () use ($data, $creator, $attachments, $mediaIds) {
            $ticketNumber = $this->generateTicketNumber();

            $initialStatus = !empty($data['status']) ? strtolower(trim($data['status'])) : 'open';
            $priority = $data['priority'] ?? 'medium';
            $now = now();

            // Calculate initial SLA deadlines so they are recorded on the initial created activity event
            $tempTicket = new Ticket(['priority' => $priority]);
            $firstResponseDue = $this->slaService->getCalculationService()->calculateFirstResponseDeadline($tempTicket, $now);
            $resolutionDue = $this->slaService->getCalculationService()->calculateResolutionDeadline($tempTicket, $now);

            $ticket = $this->repository->create([
                'ticket_number'         => $ticketNumber,
                'customer_id'           => $data['customer_id'],
                'assigned_to'           => $data['assigned_to'] ?? null,
                'subject'               => $data['subject'],
                'status'                => $initialStatus,
                'priority'              => $priority,
                'department'            => $data['department'] ?? null,
                'first_response_due_at' => $firstResponseDue,
                'resolution_due_at'     => $resolutionDue,
                'resolved_at'           => $initialStatus === 'resolved' ? $now : null,
                'last_reply_at'         => $now,
            ]);

            // Ensure SLA deadlines are initialized (noop if already populated)
            $this->slaService->applySlaToTicket($ticket);

            // Create opening message and store any attachments / media (mark as initial creation)
            $mediaIds = !empty($mediaIds) ? $mediaIds : ($data['media_ids'] ?? []);
            $this->messageService->addReply($ticket, $creator, $data['description'], $attachments, $mediaIds, isInitial: true);

            return $ticket;
        });
    }

    /**
     * Update ticket status and handle SLA resolution milestone.
     */
    public function updateStatus(Ticket $ticket, string $status): Ticket
    {
        return $this->transaction(function () use ($ticket, $status) {
            $oldStatus = $ticket->status;
            $updatedTicket = $this->slaService->handleStatusChange($ticket, $status);

            if ($oldStatus !== $status) {
                $this->notifyCustomerOnStatusChange($updatedTicket, $status);
            }

            return $updatedTicket;
        });
    }

    /**
     * Notify the ticket owner when ticket status changes.
     */
    protected function notifyCustomerOnStatusChange(Ticket $ticket, string $newStatus): void
    {
        $customer = $ticket->customer ?? User::find($ticket->customer_id);

        if (!$customer) {
            return;
        }

        $statusLabels = [
            'open'                 => 'Open',
            'in_progress'          => 'In Progress',
            'waiting_for_customer' => 'Waiting for Customer',
            'resolved'             => 'Resolved',
            'closed'               => 'Closed',
        ];

        $label = $statusLabels[$newStatus] ?? ucfirst($newStatus);

        if ($newStatus === 'resolved') {
            $title = "Ticket #{$ticket->ticket_number} Resolved";
            $message = "Your ticket \"{$ticket->subject}\" has been marked as resolved.";
            $type = 'success';
        } elseif ($newStatus === 'closed') {
            $title = "Ticket #{$ticket->ticket_number} Closed";
            $message = "Your ticket \"{$ticket->subject}\" has been closed.";
            $type = 'info';
        } else {
            $title = "Ticket #{$ticket->ticket_number} Status Updated";
            $message = "Your ticket \"{$ticket->subject}\" status was updated to {$label}.";
            $type = 'info';
        }

        notify(
            $customer,
            $title,
            $message,
            $type,
            route('customer.tickets.show', $ticket)
        );
    }

    /**
     * Update ticket priority and recalculate pending SLA deadlines.
     */
    public function updatePriority(Ticket $ticket, string $newPriority): Ticket
    {
        return $this->transaction(function () use ($ticket, $newPriority) {
            return $this->slaService->recalculateSlaOnPriorityChange($ticket, $newPriority);
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
