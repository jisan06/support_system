<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\MenuSortService;
use Illuminate\Http\Request;

class MenuSortController extends Controller
{
    protected MenuSortService $service;

    public function __construct(MenuSortService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $menu = config('admin_menu');
        $savedOrder = $this->service->getSavedOrder();

        // Apply saved order for display
        $sortedMenu = $this->service->getSortedMenu();

        return view('admin.menu-sort.index', [
            'menu' => $sortedMenu,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.top' => 'present|array',
        ]);

        $order = $request->input('order');

        // If top is empty, clear the saved order (reset)
        if (empty($order['top'])) {
            $this->service->saveOrder([]);
        } else {
            $this->service->saveOrder($order);
        }

        return ResponseHelper::success([], __('menu_sort.order_saved'));
    }
}