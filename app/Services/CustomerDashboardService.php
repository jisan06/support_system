<?php

namespace App\Services;

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
     *     counts: array{total: int, open: int, resolved: int},
     *     customer: User
     * }
     */
    public function getDashboardData(User $customer): array
    {
        return [
            'counts'   => $this->ticketRepository->getCustomerTicketCounts($customer),
            'customer' => $customer,
        ];
    }
}

