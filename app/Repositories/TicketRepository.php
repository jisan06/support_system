<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Traits\SoftDeleteTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketRepository extends BaseRepository implements TicketRepositoryInterface
{
    use SoftDeleteTrait;

    public function __construct(Ticket $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a ticket ensuring customer ownership when accessed by a customer.
     */
    public function findForUser(int|string $id, User $user): Ticket
    {
        $query = $this->model->newQuery();

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        return $query->with(['customer', 'assignedTo'])->findOrFail($id);
    }

    /**
     * Get paginated tickets for user with optional filters.
     */
    public function getTicketsForUser(User $user, array|int $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (is_int($filters)) {
            $perPage = $filters;
            $filters = [];
        }

        $query = $this->model->newQuery()->with(['customer', 'assignedTo']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        if (!empty($filters['status']) && is_string($filters['status']) && $filters['status'] !== 'all') {
            $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];
            $statusVal = strtolower(trim($filters['status']));
            if (in_array($statusVal, $allowedStatuses, true)) {
                $query->where('status', $statusVal);
            }
        }

        if (!empty($filters['priority']) && is_string($filters['priority']) && $filters['priority'] !== 'all') {
            $allowedPriorities = ['low', 'medium', 'high', 'urgent'];
            $priorityVal = strtolower(trim($filters['priority']));
            if (in_array($priorityVal, $allowedPriorities, true)) {
                $query->where('priority', $priorityVal);
            }
        }

        if (!empty($filters['department']) && is_string($filters['department'])) {
            $query->where('department', trim($filters['department']));
        }

        $dateFrom = $filters['date_from'] ?? $filters['start_date'] ?? null;
        $dateTo = $filters['date_to'] ?? $filters['end_date'] ?? null;

        $validDateFrom = null;
        if (!empty($dateFrom) && is_string($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) && strtotime($dateFrom) !== false) {
            $validDateFrom = $dateFrom;
        }

        $validDateTo = null;
        if (!empty($dateTo) && is_string($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) && strtotime($dateTo) !== false) {
            $validDateTo = $dateTo;
        }

        if ($validDateFrom && $validDateTo && $validDateFrom > $validDateTo) {
            // Impossible date range (start date greater than end date): return zero records safely
            $query->whereRaw('1 = 0');
        } else {
            if ($validDateFrom) {
                $query->whereDate('created_at', '>=', $validDateFrom);
            }
            if ($validDateTo) {
                $query->whereDate('created_at', '<=', $validDateTo);
            }
        }

        if (!empty($filters['search']) && is_string($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        return $query->latest('last_reply_at')->latest('id')->paginate($perPage);
    }

    /**
     * Get ticket summary counts for a customer (Total, Open, In Progress, Resolved, Closed).
     * Uses a single aggregated database-level query for optimal performance.
     *
     * @return array{total: int, open: int, in_progress: int, resolved: int, closed: int}
     */
    public function getCustomerTicketCounts(User $customer): array
    {
        $counts = $this->model->newQuery()
            ->forCustomer($customer)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'open' THEN 1 END) as `open`,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as `in_progress`,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as `resolved`,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as `closed`
            ")
            ->first();

        return [
            'total'       => (int) ($counts->total ?? 0),
            'open'        => (int) ($counts->open ?? 0),
            'in_progress' => (int) ($counts->in_progress ?? 0),
            'resolved'    => (int) ($counts->resolved ?? 0),
            'closed'      => (int) ($counts->closed ?? 0),
        ];
    }
}

