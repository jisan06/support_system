<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\UpdateService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $service, UpdateService $updateService)
    {
        $user = $request->user();
        $canViewTickets = $user && $user->hasPermissionTo('view_ticket');

        $dateFilter = $service->parseDateFilter($request);

        $ticketStats = null;
        $ticketInsights = null;

        if ($canViewTickets) {
            $ticketStats = $service->getTicketStats($dateFilter['from'], $dateFilter['to']);
            $ticketInsights = $service->getTicketInsights($dateFilter['from'], $dateFilter['to']);
        }

        return view('admin.dashboard.index', [
            'canViewTickets'     => $canViewTickets,
            'dateFilter'         => $dateFilter,
            'ticketStats'        => $ticketStats,
            'ticketInsights'     => $ticketInsights,
            'widgets'            => $service->getWidgets(),
            'recentActivities'   => $service->getRecentActivities(),
            'userChart'          => $service->getUserRegistrationChart(),
            'activityEventChart' => $service->getActivityByEventChart(),
            'userStatusChart'    => $service->getUsersByStatusChart(),
            'activityTrend'      => $service->getActivityTrendChart(),
            'updateInfo'         => $updateService->isEnabled() ? $updateService->checkForUpdate() : null,
        ]);
    }
}
