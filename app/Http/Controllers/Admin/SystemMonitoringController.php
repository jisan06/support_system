<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemMonitoringService;

class SystemMonitoringController extends Controller
{
    protected SystemMonitoringService $service;

    public function __construct(SystemMonitoringService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = [
            'server'     => $this->service->getServerInfo(),
            'disk'       => $this->service->getDiskUsage(),
            'storage'    => $this->service->getStorageSizes(),
            'database'   => $this->service->getDatabaseInfo(),
            'health'     => $this->service->getLaravelHealth(),
            'logs'       => $this->service->getLogInfo(),
        ];

        return view('admin.system-monitoring.index', $data);
    }

    public function clearCache()
    {
        $this->service->clearCache();

        return redirect()->route('system-monitoring.index')
            ->with('success', __('system_monitoring.cache_cleared'));
    }

    public function clearLogs()
    {
        $this->service->clearLogs();

        return redirect()->route('system-monitoring.index')
            ->with('success', __('system_monitoring.logs_cleared'));
    }
}
