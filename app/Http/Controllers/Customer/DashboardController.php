<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected CustomerDashboardService $dashboardService;

    public function __construct(CustomerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the customer portal dashboard.
     */
    public function index(Request $request): View
    {
        $data = $this->dashboardService->getDashboardData($request->user());

        return view('customer.dashboard', $data);
    }
}

