<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Media;
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
}