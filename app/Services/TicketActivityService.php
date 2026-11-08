<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TicketActivityService
{
    /**
     * Get a merged, sorted activity timeline for a ticket.
     *
     * @param Ticket $ticket
     * @param User   $user   The requesting user (used for permission checks).
     * @param int    $perPage Items per page.
     * @return LengthAwarePaginator
     */
    public function getTimeline(Ticket $ticket, User $user, int $perPage = 20): LengthAwarePaginator
    {
        // Load ticket activities (log_name = 'ticket')
        $ticketActivities = ActivityLog::query()
            ->where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->with(['causer', 'subject'])
            ->get();

        // Load all message IDs for the ticket (single query)
        $messageIds = $ticket->messages()->pluck('id');

        $messageActivities = ActivityLog::query()
            ->where('subject_type', TicketMessage::class)
            ->whereIn('subject_id', $messageIds)
            ->with(['causer', 'subject'])
            ->get();

        // Merge collections
        $activities = $ticketActivities->concat($messageActivities);

        // Permission: hide internal‑note activities for users without the permission.
        // Uses the project's registered permission slug 'internal_note_ticket' (not 'view_internal_notes').
        if (! $user->hasPermissionTo('internal_note_ticket')) {
            $activities = $activities->reject(function ($activity) {
                // Hide internal‑note activities for users without permission.
                // TicketMessage activities use log_name 'ticket_message' and have a subject model.
                if ($activity->log_name === 'ticket_message' && $activity->relationLoaded('subject')) {
                    $subject = $activity->subject;
                    if (method_exists($subject, 'isInternalNote') && $subject->isInternalNote()) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Sort newest first
        $activities = $activities->sortByDesc('created_at')->values();

        // Simple manual pagination (since we have a collection, not a query builder)
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paged = $activities->slice(($page - 1) * $perPage, $perPage)->values();
        return new LengthAwarePaginator(
            $paged,
            $activities->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}

