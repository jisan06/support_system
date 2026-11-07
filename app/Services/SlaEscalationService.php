<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SlaEscalationService
{
    protected SlaService $slaService;
    protected BusinessHoursService $businessHours;

    public function __construct(SlaService $slaService, BusinessHoursService $businessHours)
    {
        $this->slaService = $slaService;
        $this->businessHours = $businessHours;
    }

    /**
     * Get escalation rules applicable to a ticket.
     * Rules are configurable per priority and breach conditions.
     *
     * @return array<array{
     *     level: int,
     *     type: string,
     *     trigger_reason: string,
     *     minutes_after_breach: int,
     *     action: string
     * }>
     */
    public function getRulesForTicket(Ticket $ticket): array
    {
        if (!setting('sla_escalation_enabled', true)) {
            return [];
        }

        $priority = strtolower($ticket->priority ?? 'medium');

        $level1Minutes = (int) setting("sla_escalation_level_1_minutes_{$priority}", setting('sla_escalation_level_1_minutes', 0));
        $level1Action = (string) setting("sla_escalation_level_1_action_{$priority}", setting('sla_escalation_level_1_action', 'notify'));

        $defaultLevel2Minutes = match ($priority) {
            'urgent' => 30,
            'high' => 60,
            default => 120,
        };

        $level2Minutes = (int) setting("sla_escalation_level_2_minutes_{$priority}", setting('sla_escalation_level_2_minutes', $defaultLevel2Minutes));
        $level2Action = (string) setting("sla_escalation_level_2_action_{$priority}", setting('sla_escalation_level_2_action', 'notify_and_reassign'));

        return [
            // Level 1: First Response Breach (e.g. 0 min past due - immediate)
            [
                'level' => 1,
                'type' => 'first_response',
                'trigger_reason' => 'first_response_breached',
                'minutes_after_breach' => $level1Minutes,
                'action' => $level1Action,
            ],
            // Level 2: Extended First Response Breach (e.g. 30-120 min past due)
            [
                'level' => 2,
                'type' => 'first_response',
                'trigger_reason' => 'first_response_breached_extended',
                'minutes_after_breach' => $level2Minutes,
                'action' => $level2Action,
            ],
            // Level 1: Resolution Breach
            [
                'level' => 1,
                'type' => 'resolution',
                'trigger_reason' => 'resolution_breached',
                'minutes_after_breach' => $level1Minutes,
                'action' => $level1Action,
            ],
            // Level 2: Extended Resolution Breach
            [
                'level' => 2,
                'type' => 'resolution',
                'trigger_reason' => 'resolution_breached_extended',
                'minutes_after_breach' => $level2Minutes,
                'action' => $level2Action,
            ],
        ];
    }

    /**
     * Determine whether a ticket should be escalated under a specific rule.
     */
    public function shouldEscalate(Ticket $ticket, array $rule, ?Carbon $now = null): bool
    {
        $now = $now ?? now();

        // 1. Exclude closed and resolved tickets
        if (!in_array($ticket->status, ['open', 'in_progress'], true)) {
            return false;
        }

        // 2. Prevent duplicate execution of the exact same level and trigger on this ticket
        $alreadyExecuted = TicketEscalation::where('ticket_id', $ticket->id)
            ->where('level', $rule['level'])
            ->where('trigger_reason', $rule['trigger_reason'])
            ->exists();

        if ($alreadyExecuted) {
            return false;
        }

        // 3. First response breach check
        if ($rule['type'] === 'first_response') {
            if ($ticket->first_responded_at !== null || $ticket->first_response_due_at === null) {
                return false;
            }

            $effectiveDeadline = $ticket->first_response_due_at->copy()->addMinutes($rule['minutes_after_breach']);
            return $now->gte($effectiveDeadline);
        }

        // 4. Resolution breach check
        if ($rule['type'] === 'resolution') {
            if ($ticket->resolved_at !== null || $ticket->resolution_due_at === null) {
                return false;
            }

            $effectiveDeadline = $ticket->resolution_due_at->copy()->addMinutes($rule['minutes_after_breach']);
            return $now->gte($effectiveDeadline);
        }

        return false;
    }

    /**
     * Execute escalation for a ticket with locking, history tracking, notifications, and reassignment.
     */
    public function executeEscalation(Ticket $ticket, array $rule, ?Carbon $now = null): ?TicketEscalation
    {
        $now = $now ?? now();

        return DB::transaction(function () use ($ticket, $rule, $now) {
            // Lock ticket row for update to prevent concurrent duplicate escalation
            $lockedTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->first();
            if (!$lockedTicket) {
                return null;
            }

            // Re-verify conditions inside lock
            if (!$this->shouldEscalate($lockedTicket, $rule, $now)) {
                return null;
            }

            $previousAssigneeId = $lockedTicket->assigned_to;
            $newAssigneeId = $previousAssigneeId;
            $actionTaken = $rule['action'];
            $notifiedUserIds = [];

            // 1. Automatic reassignment to higher-level staff if configured
            if (in_array($actionTaken, ['reassign', 'notify_and_reassign'], true)) {
                $higherStaff = $this->findEligibleHigherLevelStaff($lockedTicket);
                if ($higherStaff) {
                    $newAssigneeId = $higherStaff->id;
                    $lockedTicket->assigned_to = $newAssigneeId;
                } else {
                    // If no eligible higher-level staff exists, preserve assignment and fall back to notify
                    if ($actionTaken === 'reassign') {
                        $actionTaken = 'notify';
                    }
                }
            }

            // Update ticket escalation status and timestamps
            $lockedTicket->escalation_level = max((int) $lockedTicket->escalation_level, (int) $rule['level']);
            $lockedTicket->last_escalated_at = $now;
            $lockedTicket->save();

            // 2. Dispatch notifications
            if (in_array($actionTaken, ['notify', 'notify_and_reassign'], true)) {
                $notifiedUsers = $this->getUsersToNotify($lockedTicket, $newAssigneeId, $previousAssigneeId);
                $notifiedUserIds = $notifiedUsers->pluck('id')->toArray();

                $this->sendEscalationNotification($lockedTicket, $notifiedUsers, $rule, $newAssigneeId !== $previousAssigneeId);
            }

            // 3. Record escalation history in ticket_escalations table
            try {
                $escalation = TicketEscalation::create([
                    'ticket_id'            => $lockedTicket->id,
                    'level'                => $rule['level'],
                    'trigger_reason'       => $rule['trigger_reason'],
                    'previous_assigned_to' => $previousAssigneeId,
                    'new_assigned_to'      => $newAssigneeId,
                    'action_taken'         => $actionTaken,
                    'notified_users'       => $notifiedUserIds,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // Concurrently executed by another worker - safe no-op
                return null;
            }

            // 4. Record audit in activity log
            activity('ticket')
                ->performedOn($lockedTicket)
                ->withProperties([
                    'escalation_level'     => $rule['level'],
                    'trigger_reason'       => $rule['trigger_reason'],
                    'previous_assigned_to' => $previousAssigneeId,
                    'new_assigned_to'      => $newAssigneeId,
                    'action_taken'         => $actionTaken,
                ])
                ->log("Ticket #{$lockedTicket->ticket_number} escalated to Level {$rule['level']} ({$rule['trigger_reason']})");

            return $escalation;
        });
    }

    /**
     * Find eligible higher-level staff for escalation without hardcoding role names.
     * Uses dynamic permissions and role level hierarchy.
     */
    public function findEligibleHigherLevelStaff(Ticket $ticket): ?User
    {
        $currentAssignee = $ticket->assignedTo;
        $currentLevel = $currentAssignee ? $currentAssignee->roleLevel() : 999;

        // Query active admin users
        $candidates = User::where('account_type', User::TYPE_ADMIN)
            ->where('status', 'Active')
            ->when($currentAssignee, fn ($q) => $q->where('id', '!=', $currentAssignee->id))
            ->with(['roles.permissions', 'assignedTickets' => fn ($q) => $q->active()])
            ->get();

        // Filter by permission to handle tickets and higher authority (lower level number)
        $eligible = $candidates->filter(function (User $user) use ($currentLevel) {
            $hasPermission = $user->hasPermissionTo('reply_ticket') || $user->hasPermissionTo('view_ticket');
            $isHigherLevel = $user->roleLevel() < $currentLevel;

            return $hasPermission && $isHigherLevel;
        });

        if ($eligible->isEmpty()) {
            return null;
        }

        // Pick eligible staff with the lowest current active ticket load
        return $eligible->sortBy(fn (User $user) => $user->assignedTickets->count())->first();
    }

    /**
     * Resolve users who must be notified for the escalation.
     *
     * @return Collection<int, User>
     */
    public function getUsersToNotify(Ticket $ticket, ?int $newAssigneeId, ?int $previousAssigneeId): Collection
    {
        $userIds = collect();

        if ($newAssigneeId) {
            $userIds->push($newAssigneeId);
        }

        if ($previousAssigneeId && $previousAssigneeId !== $newAssigneeId) {
            $userIds->push($previousAssigneeId);
        }

        // If ticket has no assignee, notify active staff who have ticket permissions
        if ($userIds->isEmpty()) {
            return User::where('account_type', User::TYPE_ADMIN)
                ->where('status', 'Active')
                ->get()
                ->filter(fn (User $u) => $u->hasPermissionTo('view_ticket') || $u->hasPermissionTo('reply_ticket'));
        }

        return User::whereIn('id', $userIds->unique())->get();
    }

    /**
     * Dispatch notification with failure isolation so failures do not abort the escalation transaction.
     */
    protected function sendEscalationNotification(Ticket $ticket, Collection $users, array $rule, bool $reassigned): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $title = "SLA Escalation Level {$rule['level']}: Ticket #{$ticket->ticket_number}";
        $reasonLabel = ucwords(str_replace('_', ' ', $rule['trigger_reason']));
        $message = "Ticket #{$ticket->ticket_number} ('{$ticket->subject}') has been escalated due to {$reasonLabel}.";

        if ($reassigned) {
            $message .= " The ticket has been reassigned to higher-level staff.";
        }

        try {
            notify(
                $users,
                $title,
                $message,
                'danger',
                route('tickets.show', $ticket->id)
            );
        } catch (Throwable $e) {
            Log::warning("SLA Escalation notification error on ticket #{$ticket->id}: " . $e->getMessage());
        }
    }

    /**
     * Process all active tickets in chunks and execute required escalations.
     *
     * @return array{checked: int, escalated: int, errors: int}
     */
    public function processEscalations(?int $chunkSize = 50, ?Carbon $now = null): array
    {
        $stats = [
            'checked'   => 0,
            'escalated' => 0,
            'errors'    => 0,
        ];

        $now = $now ?? now();

        Ticket::active()
            ->where(function ($q) {
                $q->whereNotNull('first_response_due_at')
                  ->orWhereNotNull('resolution_due_at');
            })
            ->chunkById($chunkSize ?? 50, function ($tickets) use (&$stats, $now) {
                foreach ($tickets as $ticket) {
                    $stats['checked']++;
                    $rules = $this->getRulesForTicket($ticket);

                    foreach ($rules as $rule) {
                        try {
                            if ($this->shouldEscalate($ticket, $rule, $now)) {
                                $this->executeEscalation($ticket, $rule, $now);
                                $stats['escalated']++;
                            }
                        } catch (Throwable $e) {
                            $stats['errors']++;
                            Log::error("Error escalating ticket #{$ticket->id}: " . $e->getMessage());
                        }
                    }
                }
            });

        return $stats;
    }
}
