<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

class SlaService
{
    protected SlaCalculationService $calculationService;

    public function __construct(SlaCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Get underlying calculation service instance.
     */
    public function getCalculationService(): SlaCalculationService
    {
        return $this->calculationService;
    }

    /**
     * Calculate and apply initial SLA deadlines to a ticket based on its priority.
     */
    public function applySlaToTicket(Ticket $ticket, ?Carbon $createdAt = null): Ticket
    {
        if ($ticket->first_response_due_at !== null && $ticket->resolution_due_at !== null) {
            return $ticket;
        }

        $fromTime = $createdAt ?? $ticket->created_at ?? now();

        $firstResponseDue = $this->calculationService->calculateFirstResponseDeadline($ticket, $fromTime);
        $resolutionDue = $this->calculationService->calculateResolutionDeadline($ticket, $fromTime);

        $ticket->update([
            'first_response_due_at' => $firstResponseDue,
            'resolution_due_at'     => $resolutionDue,
        ]);

        return $ticket;
    }

    /**
     * Record first response on a ticket when an authorized internal staff member replies publicly.
     * Internal notes and initial creation messages do NOT count.
     */
    public function recordFirstResponse(Ticket $ticket, User $user): void
    {
        if ($ticket->first_responded_at === null && $user->isAdmin()) {
            $ticket->update(['first_responded_at' => now()]);
        }
    }

    /**
     * Record resolution milestone when ticket status transitions to 'resolved'.
     * Closed and resolved are distinct statuses and are not treated interchangeably.
     */
    public function recordResolution(Ticket $ticket): void
    {
        if ($ticket->resolved_at === null && $ticket->status === 'resolved') {
            $ticket->update(['resolved_at' => now()]);
        }
    }

    /**
     * Handle ticket status changes and keep resolution timestamps consistent.
     */
    public function handleStatusChange(Ticket $ticket, string $newStatus): Ticket
    {
        $oldStatus = $ticket->status;
        $ticket->status = $newStatus;

        if ($newStatus === 'resolved' && $ticket->resolved_at === null) {
            $ticket->resolved_at = now();
        } elseif ($oldStatus === 'resolved' && $newStatus !== 'resolved') {
            // Reopened from resolved
            $ticket->resolved_at = null;
        }

        $ticket->save();

        return $ticket;
    }

    /**
     * Recalculate pending SLA deadlines when ticket priority changes.
     * Historical response and resolution timestamps are strictly preserved.
     */
    public function recalculateSlaOnPriorityChange(Ticket $ticket, string $newPriority): Ticket
    {
        $newPriority = strtolower($newPriority);

        if (!in_array($newPriority, ['low', 'medium', 'high', 'urgent'], true)) {
            throw new InvalidArgumentException("Invalid ticket priority [{$newPriority}].");
        }

        if ($ticket->priority === $newPriority) {
            return $ticket;
        }

        $ticket->priority = $newPriority;
        $fromTime = $ticket->created_at ?? now();

        $updates = ['priority' => $newPriority];

        // Recalculate first response deadline only if first response has not yet occurred
        if ($ticket->first_responded_at === null) {
            $updates['first_response_due_at'] = $this->calculationService->calculateFirstResponseDeadline($ticket, $fromTime);
        }

        // Recalculate resolution deadline only if ticket has not yet been resolved
        if ($ticket->resolved_at === null) {
            $updates['resolution_due_at'] = $this->calculationService->calculateResolutionDeadline($ticket, $fromTime);
        }

        $ticket->update($updates);

        return $ticket;
    }

    /**
     * Determine first response SLA status.
     */
    public function getFirstResponseStatus(Ticket $ticket, ?Carbon $now = null): SlaStatus
    {
        if ($ticket->first_response_due_at === null) {
            return SlaStatus::ON_TRACK;
        }

        // If first response already recorded
        if ($ticket->first_responded_at !== null) {
            return $ticket->first_responded_at->gt($ticket->first_response_due_at)
                ? SlaStatus::BREACHED
                : SlaStatus::COMPLETED;
        }

        $now = $now ?? now();

        // If current time passed the deadline
        if ($now->gt($ticket->first_response_due_at)) {
            return SlaStatus::BREACHED;
        }

        // Check for approaching breach
        $targets = $this->calculationService->getTargetsForPriority($ticket->priority);
        $totalMinutes = $targets['first_response'];
        $warningPercentage = (float) setting('sla_warning_threshold_percentage', 80);
        $warningMinutes = (int) setting('sla_approaching_breach_minutes', 0);

        $thresholdRemainingMinutes = $warningMinutes > 0
            ? $warningMinutes
            : (int) round($totalMinutes * (1 - ($warningPercentage / 100)));

        $remainingMinutes = $now->diffInMinutes($ticket->first_response_due_at, false);

        if ($remainingMinutes <= $thresholdRemainingMinutes) {
            return SlaStatus::APPROACHING_BREACH;
        }

        return SlaStatus::ON_TRACK;
    }

    /**
     * Determine resolution SLA status.
     */
    public function getResolutionStatus(Ticket $ticket, ?Carbon $now = null): SlaStatus
    {
        if ($ticket->resolution_due_at === null) {
            return SlaStatus::ON_TRACK;
        }

        // If resolution timestamp recorded
        if ($ticket->resolved_at !== null) {
            return $ticket->resolved_at->gt($ticket->resolution_due_at)
                ? SlaStatus::BREACHED
                : SlaStatus::COMPLETED;
        }

        $now = $now ?? now();

        if ($now->gt($ticket->resolution_due_at)) {
            return SlaStatus::BREACHED;
        }

        $targets = $this->calculationService->getTargetsForPriority($ticket->priority);
        $totalMinutes = $targets['resolution'];
        $warningPercentage = (float) setting('sla_warning_threshold_percentage', 80);
        $warningMinutes = (int) setting('sla_approaching_breach_minutes', 0);

        $thresholdRemainingMinutes = $warningMinutes > 0
            ? $warningMinutes
            : (int) round($totalMinutes * (1 - ($warningPercentage / 100)));

        $remainingMinutes = $now->diffInMinutes($ticket->resolution_due_at, false);

        if ($remainingMinutes <= $thresholdRemainingMinutes) {
            return SlaStatus::APPROACHING_BREACH;
        }

        return SlaStatus::ON_TRACK;
    }

    /**
     * Determine overall ticket SLA status.
     */
    public function getOverallStatus(Ticket $ticket, ?Carbon $now = null): SlaStatus
    {
        $responseStatus = $this->getFirstResponseStatus($ticket, $now);
        $resolutionStatus = $this->getResolutionStatus($ticket, $now);

        // If either milestone is breached -> BREACHED
        if ($responseStatus === SlaStatus::BREACHED || $resolutionStatus === SlaStatus::BREACHED) {
            return SlaStatus::BREACHED;
        }

        // If ticket is resolved and neither milestone breached -> COMPLETED
        if ($ticket->status === 'resolved' || $ticket->resolved_at !== null) {
            return SlaStatus::COMPLETED;
        }

        // If either milestone is approaching breach -> APPROACHING_BREACH
        if ($responseStatus === SlaStatus::APPROACHING_BREACH || $resolutionStatus === SlaStatus::APPROACHING_BREACH) {
            return SlaStatus::APPROACHING_BREACH;
        }

        return SlaStatus::ON_TRACK;
    }
}
