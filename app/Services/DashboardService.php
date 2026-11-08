<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardService
{
    public function getWidgets(): array
    {
        return [
            [
                'label' => 'Total Users',
                'value' => User::count(),
                'icon'  => 'bx bx-user',
                'color' => 'primary',
                'route' => 'users.index',
            ],
            [
                'label' => 'Total Roles',
                'value' => Role::count(),
                'icon'  => 'bx bx-shield',
                'color' => 'success',
                'route' => 'roles.index',
            ],
            [
                'label' => 'Media Files',
                'value' => Media::count(),
                'icon'  => 'bx bx-image',
                'color' => 'info',
                'route' => 'media.index',
            ],
            [
                'label' => 'Activities',
                'value' => Activity::count(),
                'icon'  => 'bx bx-history',
                'color' => 'warning',
                'route' => 'activity-logs.index',
            ],
        ];
    }

    public function getRecentActivities(int $limit = 8): array
    {
        return Activity::with('causer')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'description' => $a->description,
                'causer'      => $a->causer?->name ?? 'System',
                'event'       => $a->event,
                'time'        => $a->created_at->diffForHumans(),
                'date'        => $a->created_at->format('M d, Y H:i'),
            ])
            ->toArray();
    }

    public function getUserRegistrationChart(): array
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'label' => $date->format('M'),
                'count' => User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ]);
        }

        return [
            'labels' => $months->pluck('label')->toArray(),
            'data'   => $months->pluck('count')->toArray(),
        ];
    }

    public function getActivityByEventChart(): array
    {
        $events = Activity::select('event', DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        return [
            'labels' => $events->pluck('event')->map(fn ($e) => ucfirst($e))->toArray(),
            'data'   => $events->pluck('count')->toArray(),
        ];
    }

    public function getUsersByStatusChart(): array
    {
        $statuses = User::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => $statuses->pluck('status')->toArray(),
            'data'   => $statuses->pluck('count')->toArray(),
        ];
    }

    public function getActivityTrendChart(): array
    {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days->push([
                'label' => $date->format('d M'),
                'count' => Activity::whereDate('created_at', $date->toDateString())->count(),
            ]);
        }

        return [
            'labels' => $days->pluck('label')->toArray(),
            'data'   => $days->pluck('count')->toArray(),
        ];
    }

    /**
     * Parse date range filter from request.
     * Supports 'all', 'today', '7days' / 'last_7_days', '30days' / 'last_30_days', and custom ranges.
     */
    public function parseDateFilter(Request $request): array
    {
        $range = $request->query('range', 'all');
        $from = null;
        $to = null;

        if ($range === 'today') {
            $from = now()->startOfDay();
            $to = now()->endOfDay();
        } elseif ($range === '7days' || $range === 'last_7_days') {
            $from = now()->subDays(6)->startOfDay();
            $to = now()->endOfDay();
        } elseif ($range === '30days' || $range === 'last_30_days') {
            $from = now()->subDays(29)->startOfDay();
            $to = now()->endOfDay();
        } elseif ($range === 'custom' || $request->filled('start_date') || $request->filled('date_from')) {
            $range = 'custom';
            $startDateStr = $request->query('start_date') ?? $request->query('date_from');
            $endDateStr = $request->query('end_date') ?? $request->query('date_to') ?? now()->toDateString();

            if ($startDateStr) {
                try {
                    $from = Carbon::parse($startDateStr)->startOfDay();
                    $to = Carbon::parse($endDateStr)->endOfDay();
                    if ($from->gt($to)) {
                        [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                    }
                } catch (\Exception $e) {
                    $from = null;
                    $to = null;
                    $range = 'all';
                }
            }
        } else {
            $range = 'all';
        }

        return [
            'range'      => $range,
            'from'       => $from,
            'to'         => $to,
            'start_date' => $from?->format('Y-m-d'),
            'end_date'   => $to?->format('Y-m-d'),
        ];
    }

    /**
     * Get useful ticket statistics aggregated at the database level.
     */
    public function getTicketStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Ticket::query();
        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $stats = (clone $query)->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'open' THEN 1 END) as `open`,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'waiting_for_customer' THEN 1 END) as waiting_for_customer,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
            COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed,
            COUNT(CASE WHEN assigned_to IS NULL THEN 1 END) as unassigned
        ")->first();

        // SLA calculation using existing SlaService
        $activeTickets = (clone $query)->whereIn('status', ['open', 'in_progress', 'waiting_for_customer'])
            ->where(function ($q) {
                $q->whereNotNull('first_response_due_at')
                  ->orWhereNotNull('resolution_due_at');
            })->get();

        $slaApproaching = 0;
        $slaBreached = 0;
        $slaService = app(\App\Services\SlaService::class);

        foreach ($activeTickets as $ticket) {
            $status = $slaService->getOverallStatus($ticket);
            if ($status === \App\Enums\SlaStatus::BREACHED) {
                $slaBreached++;
            } elseif ($status === \App\Enums\SlaStatus::APPROACHING_BREACH) {
                $slaApproaching++;
            }
        }

        return [
            'total'                => (int) ($stats->total ?? 0),
            'open'                 => (int) ($stats->open ?? 0),
            'in_progress'          => (int) ($stats->in_progress ?? 0),
            'waiting_for_customer' => (int) ($stats->waiting_for_customer ?? 0),
            'resolved'             => (int) ($stats->resolved ?? 0),
            'closed'               => (int) ($stats->closed ?? 0),
            'unassigned'           => (int) ($stats->unassigned ?? 0),
            'sla_approaching'      => $slaApproaching,
            'sla_breached'         => $slaBreached,
        ];
    }

    /**
     * Get ticket insights and breakdowns respecting date filters.
     */
    public function getTicketInsights(?Carbon $from = null, ?Carbon $to = null): array
    {
        $baseQuery = Ticket::query();
        if ($from && $to) {
            $baseQuery->whereBetween('created_at', [$from, $to]);
        }

        $total = (clone $baseQuery)->count();

        // 1. Status volume breakdown
        $statusCounts = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $volumeByStatus = [
            'open'                 => ['label' => 'Open', 'count' => $statusCounts['open'] ?? 0, 'color' => 'warning', 'percentage' => $total > 0 ? round((($statusCounts['open'] ?? 0) / $total) * 100) : 0],
            'in_progress'          => ['label' => 'In Progress', 'count' => $statusCounts['in_progress'] ?? 0, 'color' => 'info', 'percentage' => $total > 0 ? round((($statusCounts['in_progress'] ?? 0) / $total) * 100) : 0],
            'waiting_for_customer' => ['label' => 'Waiting for Customer', 'count' => $statusCounts['waiting_for_customer'] ?? 0, 'color' => 'purple', 'percentage' => $total > 0 ? round((($statusCounts['waiting_for_customer'] ?? 0) / $total) * 100) : 0],
            'resolved'             => ['label' => 'Resolved', 'count' => $statusCounts['resolved'] ?? 0, 'color' => 'success', 'percentage' => $total > 0 ? round((($statusCounts['resolved'] ?? 0) / $total) * 100) : 0],
            'closed'               => ['label' => 'Closed', 'count' => $statusCounts['closed'] ?? 0, 'color' => 'secondary', 'percentage' => $total > 0 ? round((($statusCounts['closed'] ?? 0) / $total) * 100) : 0],
        ];

        // 2. Priority volume breakdown
        $priorityCounts = (clone $baseQuery)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $volumeByPriority = [
            'urgent' => ['label' => 'Urgent', 'count' => $priorityCounts['urgent'] ?? 0, 'color' => 'danger', 'percentage' => $total > 0 ? round((($priorityCounts['urgent'] ?? 0) / $total) * 100) : 0],
            'high'   => ['label' => 'High', 'count' => $priorityCounts['high'] ?? 0, 'color' => 'warning', 'percentage' => $total > 0 ? round((($priorityCounts['high'] ?? 0) / $total) * 100) : 0],
            'medium' => ['label' => 'Medium', 'count' => $priorityCounts['medium'] ?? 0, 'color' => 'primary', 'percentage' => $total > 0 ? round((($priorityCounts['medium'] ?? 0) / $total) * 100) : 0],
            'low'    => ['label' => 'Low', 'count' => $priorityCounts['low'] ?? 0, 'color' => 'secondary', 'percentage' => $total > 0 ? round((($priorityCounts['low'] ?? 0) / $total) * 100) : 0],
        ];

        // 3. Tickets grouped by department
        $departments = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(department, ''), 'General Support') as dept_name, COUNT(*) as count")
            ->groupBy('dept_name')
            ->orderByDesc('count')
            ->take(6)
            ->get()
            ->map(fn ($d) => [
                'name'       => $d->dept_name,
                'count'      => (int) $d->count,
                'percentage' => $total > 0 ? round(($d->count / $total) * 100) : 0,
            ]);

        // 4. Agent workload overview
        $agentWorkload = User::admins()
            ->where('status', 'Active')
            ->withCount([
                'assignedTickets as total_assigned' => function ($q) use ($from, $to) {
                    if ($from && $to) $q->whereBetween('created_at', [$from, $to]);
                },
                'assignedTickets as open_assigned' => function ($q) use ($from, $to) {
                    $q->whereIn('status', ['open', 'in_progress', 'waiting_for_customer']);
                    if ($from && $to) $q->whereBetween('created_at', [$from, $to]);
                },
                'assignedTickets as resolved_assigned' => function ($q) use ($from, $to) {
                    $q->where('status', 'resolved');
                    if ($from && $to) $q->whereBetween('created_at', [$from, $to]);
                },
            ])
            ->orderByDesc('open_assigned')
            ->orderByDesc('total_assigned')
            ->take(6)
            ->get();

        // 5. Recent tickets
        $recentTickets = (clone $baseQuery)
            ->with(['customer', 'assignedTo'])
            ->latest()
            ->take(6)
            ->get();

        // 6. Recently resolved tickets
        $recentlyResolved = Ticket::with(['customer', 'assignedTo'])
            ->where('status', 'resolved')
            ->when($from && $to, fn ($q) => $q->whereBetween('updated_at', [$from, $to]))
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        return [
            'total'              => $total,
            'volume_by_status'   => $volumeByStatus,
            'volume_by_priority' => $volumeByPriority,
            'by_department'      => $departments,
            'agent_workload'     => $agentWorkload,
            'recent_tickets'     => $recentTickets,
            'recently_resolved'  => $recentlyResolved,
        ];
    }
}