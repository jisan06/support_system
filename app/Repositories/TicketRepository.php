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
            $allowedStatuses = ['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'];
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

        $category = $filters['category'] ?? $filters['department'] ?? null;
        if (!empty($category) && is_string($category) && $category !== 'all') {
            $query->where('department', trim($category));
        }

        // Assigned agent filter
        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '' && $filters['assigned_to'] !== 'all') {
            if ($filters['assigned_to'] === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif (is_numeric($filters['assigned_to'])) {
                $query->where('assigned_to', (int) $filters['assigned_to']);
            }
        }

        // Customer filter (applicable to internal admin/agent users)
        if (!empty($filters['customer_id']) && is_numeric($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        // SLA Status filter
        if (!empty($filters['sla_status']) && is_string($filters['sla_status']) && $filters['sla_status'] !== 'all') {
            $this->applySlaStatusFilter($query, strtolower(trim($filters['sla_status'])));
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

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhereHas('customer', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        return $query->latest('last_reply_at')->latest('id')->paginate($perPage);
    }

    /**
     * Apply database-level SLA status filtering.
     */
    protected function applySlaStatusFilter($query, string $slaStatus): void
    {
        $now = now();

        $breachedCondition = function ($q) use ($now) {
            $q->where(function ($sub) use ($now) {
                $sub->whereNotNull('first_response_due_at')
                    ->where(function ($sub2) use ($now) {
                        $sub2->where(function ($sub3) {
                            $sub3->whereNotNull('first_responded_at')
                                 ->whereColumn('first_responded_at', '>', 'first_response_due_at');
                        })->orWhere(function ($sub3) use ($now) {
                            $sub3->whereNull('first_responded_at')
                                 ->where('first_response_due_at', '<', $now);
                        });
                    });
            })->orWhere(function ($sub) use ($now) {
                $sub->whereNotNull('resolution_due_at')
                    ->where(function ($sub2) use ($now) {
                        $sub2->where(function ($sub3) {
                            $sub3->whereNotNull('resolved_at')
                                 ->whereColumn('resolved_at', '>', 'resolution_due_at');
                        })->orWhere(function ($sub3) use ($now) {
                            $sub3->whereNull('resolved_at')
                                 ->where('resolution_due_at', '<', $now);
                        });
                    });
            });
        };

        if ($slaStatus === 'breached') {
            $query->where($breachedCondition);
            return;
        }

        if ($slaStatus === 'completed') {
            $query->where(function ($sub) {
                $sub->where('status', 'resolved')
                    ->orWhereNotNull('resolved_at');
            })->whereNot($breachedCondition);
            return;
        }

        $warningMinutes = (int) setting('sla_approaching_breach_minutes', 0);
        $warningPercentage = (float) setting('sla_warning_threshold_percentage', 80);
        $priorities = ['urgent', 'high', 'medium', 'low'];
        $calcService = app(\App\Services\SlaCalculationService::class);

        $approachingCondition = function ($q) use ($now, $breachedCondition, $warningMinutes, $warningPercentage, $priorities, $calcService) {
            $q->whereNotIn('status', ['resolved', 'closed'])
              ->whereNull('resolved_at')
              ->whereNot($breachedCondition)
              ->where(function ($sub) use ($now, $warningMinutes, $warningPercentage, $priorities, $calcService) {
                  foreach ($priorities as $priority) {
                      $targets = $calcService->getTargetsForPriority($priority);

                      $respThresholdMins = $warningMinutes > 0
                          ? $warningMinutes
                          : (int) round($targets['first_response'] * (1 - ($warningPercentage / 100)));

                      $resThresholdMins = $warningMinutes > 0
                          ? $warningMinutes
                          : (int) round($targets['resolution'] * (1 - ($warningPercentage / 100)));

                      $respThresholdTime = $now->copy()->addMinutes($respThresholdMins);
                      $resThresholdTime = $now->copy()->addMinutes($resThresholdMins);

                      $sub->orWhere(function ($pSub) use ($priority, $now, $respThresholdTime, $resThresholdTime) {
                          $pSub->where('priority', $priority)
                               ->where(function ($mSub) use ($now, $respThresholdTime, $resThresholdTime) {
                                   $mSub->where(function ($m1) use ($now, $respThresholdTime) {
                                       $m1->whereNull('first_responded_at')
                                          ->whereNotNull('first_response_due_at')
                                          ->where('first_response_due_at', '>=', $now)
                                          ->where('first_response_due_at', '<=', $respThresholdTime);
                                   })->orWhere(function ($m2) use ($now, $resThresholdTime) {
                                       $m2->whereNull('resolved_at')
                                          ->whereNotNull('resolution_due_at')
                                          ->where('resolution_due_at', '>=', $now)
                                          ->where('resolution_due_at', '<=', $resThresholdTime);
                                   });
                               });
                      });
                  }
              });
        };

        if ($slaStatus === 'approaching_breach') {
            $query->where($approachingCondition);
            return;
        }

        if ($slaStatus === 'on_track') {
            $query->whereNot($breachedCondition)
                  ->where(function ($q) {
                      $q->whereNotIn('status', ['resolved', 'closed'])
                        ->whereNull('resolved_at');
                  })
                  ->whereNot($approachingCondition);
            return;
        }
    }

    /**
     * Get available ticket categories for creation and validation.
     *
     * @return array<int, string>
     */
    public function getAvailableCategories(): array
    {
        return config('ticket.categories', [
            'Technical Support',
            'Billing & Payments',
            'Account & Access',
            'General Inquiry',
        ]);
    }

    /**
     * Get all ticket categories including configured and any existing values in database.
     *
     * @return array<int, string>
     */
    public function getAllCategories(): array
    {
        $default = $this->getAvailableCategories();

        $existing = $this->model->newQuery()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department')
            ->toArray();

        $categories = array_values(array_unique(array_merge($default, $existing)));
        sort($categories);

        return $categories;
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

