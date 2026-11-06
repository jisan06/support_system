<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\UpdateService;

class DashboardController extends Controller
{
    public function index(DashboardService $service, UpdateService $updateService)
    {
        return view('admin.dashboard.index', [
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
