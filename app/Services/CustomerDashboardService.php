<?php

namespace App\Services;

use App\Enums\SlaStatus;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;

class CustomerDashboardService
{
    protected TicketRepositoryInterface $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Get dashboard summary data for the authenticated customer.
     *
     * @return array{
     *     customer: User,
     *     counts: array{total: int, open: int, in_progress: int, resolved: int, closed: int},
     *     recentTickets: \Illuminate\Database\Eloquent\Collection,
     *     recentNotifications: \Illuminate\Database\Eloquent\Collection,
     *     unreadNotificationsCount: int,
     *     slaOverview: array{approachingCount: int, breachedCount: int, attentionTickets: \Illuminate\Support\Collection}
     * }
     */
    public function getDashboardData(User $customer): array
    {
        // 1. Single database-level aggregation query for all 5 summary counts
        $counts = $this->ticketRepository->getCustomerTicketCounts($customer);

        // 2. Recent tickets (latest 5 created or updated)
        $recentTickets = $customer->tickets()
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        // 3. Recent notifications (latest 5)
        $recentNotifications = $customer->notifications()
            ->latest()
            ->take(5)
            ->get();

        $unreadNotificationsCount = $customer->unreadNotifications()->count();

        // 4. SLA Overview: approaching breach and breached active tickets
        $activeSlaTickets = $customer->tickets()
            ->whereIn('status', ['open', 'in_progress'])
            ->where(function ($query) {
                $query->whereNotNull('first_response_due_at')
                      ->orWhereNotNull('resolution_due_at');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $approachingTickets = $activeSlaTickets->filter(function ($t) {
            return $t->sla_status === SlaStatus::APPROACHING_BREACH;
        });

        $breachedTickets = $activeSlaTickets->filter(function ($t) {
            return $t->sla_status === SlaStatus::BREACHED;
        });

        return [
            'customer'                 => $customer,
            'counts'                   => $counts,
            'recentTickets'            => $recentTickets,
            'recentNotifications'      => $recentNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'slaOverview'              => [
                'approachingCount' => $approachingTickets->count(),
                'breachedCount'    => $breachedTickets->count(),
                'attentionTickets' => $breachedTickets->concat($approachingTickets)->take(5),
            ],
        ];
    }
}
