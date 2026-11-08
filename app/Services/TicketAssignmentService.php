<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TicketAssignmentService
{
    public const ACTIVE_STATUSES = ['open', 'in_progress', 'waiting_for_customer'];
    public const ASSIGNMENT_PERMISSION = 'receive_ticket_assignments';

    /**
     * Get all eligible agents (active internal admin users with receive_ticket_assignments permission).
     */
    public function getEligibleAgents(): Collection
    {
        return User::where('account_type', User::TYPE_ADMIN)
            ->where('status', 'Active')
            ->whereHas('roles.permissions', function ($q) {
                $q->where('slug', self::ASSIGNMENT_PERMISSION);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if a user is eligible for ticket assignment.
     */
    public function isEligible(User $user): bool
    {
        return $user->isAdmin()
            && $user->status === 'Active'
            && $user->hasPermissionTo(self::ASSIGNMENT_PERMISSION);
    }

    /**
     * Find the eligible agent with the fewest active tickets (least active workload).
     * Uses deterministic tie-breaking by user ID (ascending) for consistency.
     * Excludes resolved and closed tickets.
     * Returns null if no eligible agents exist.
     */
    public function findLeastActiveAgent(): ?User
    {
        return User::where('account_type', User::TYPE_ADMIN)
            ->where('status', 'Active')
            ->whereHas('roles.permissions', function ($q) {
                $q->where('slug', self::ASSIGNMENT_PERMISSION);
            })
            ->withCount(['assignedTickets as active_tickets_count' => function ($q) {
                $q->whereIn('status', self::ACTIVE_STATUSES);
            }])
            ->orderBy('active_tickets_count', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Auto-assign a newly created ticket to the least active eligible agent.
     * Uses database transaction and lockForUpdate() to prevent concurrent double-assignment.
     * If no eligible agent, leaves ticket unassigned.
     */
    public function autoAssign(Ticket $ticket): ?User
    {
        return DB::transaction(function () use ($ticket) {
            // Lock the ticket row to prevent concurrent assignment
            $freshTicket = Ticket::lockForUpdate()->find($ticket->id);

            if (!$freshTicket) {
                return null;
            }

            // If already assigned, do not overwrite
            if ($freshTicket->assigned_to !== null) {
                return $freshTicket->assignedTo;
            }

            $agent = $this->findLeastActiveAgent();

            if (!$agent) {
                return null;
            }

            $freshTicket->update(['assigned_to' => $agent->id]);

            // Sync ticket instance in memory
            $ticket->assigned_to = $agent->id;
            $ticket->setRelation('assignedTo', $agent);

            return $agent;
        });
    }

    /**
     * Manually assign, reassign, or unassign a ticket.
     * Validates eligibility of the new assignee on server side.
     * Uses database transaction and lockForUpdate() on the ticket row.
     * Prevents silent overwriting if ticket was concurrently assigned.
     *
     * @throws \InvalidArgumentException if the user is not eligible
     * @throws \RuntimeException if ticket was concurrently modified
     */
    public function manualAssign(Ticket $ticket, ?int $assigneeId): Ticket
    {
        return DB::transaction(function () use ($ticket, $assigneeId) {
            $freshTicket = Ticket::lockForUpdate()->findOrFail($ticket->id);

            // Handle concurrency: if ticket was unassigned when viewing, but another agent took it
            if ($ticket->assigned_to === null && $freshTicket->assigned_to !== null && $freshTicket->assigned_to !== $assigneeId) {
                throw new \RuntimeException(
                    'This ticket has already been assigned to another agent.'
                );
            }

            if ($assigneeId !== null) {
                $assignee = User::find($assigneeId);

                if (!$assignee || !$this->isEligible($assignee)) {
                    throw new \InvalidArgumentException(
                        'The selected user is not eligible to receive ticket assignments.'
                    );
                }
            }

            $freshTicket->update(['assigned_to' => $assigneeId]);

            // Sync original in-memory instance
            $ticket->assigned_to = $assigneeId;

            return $freshTicket->fresh();
        });
    }
}
