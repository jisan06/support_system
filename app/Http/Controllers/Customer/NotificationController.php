<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    protected NotificationService $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of notifications for the authenticated customer.
     */
    public function index(Request $request): View
    {
        $customer = $request->user();
        $filter = $request->query('filter', 'all');

        $query = ($filter === 'unread')
            ? $customer->unreadNotifications()
            : $customer->notifications();

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = $customer->unreadNotifications()->count();
        $totalCount = $customer->notifications()->count();

        return view('customer.notifications.index', compact('notifications', 'unreadCount', 'totalCount', 'filter'));
    }

    /**
     * Mark a specific notification as read.
     * Enforces customer ownership: customers can only mark their own notifications as read.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read for the authenticated customer.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications have been marked as read.');
    }

    /**
     * Open a notification: marks as read and redirects to destination URL.
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $targetUrl = $notification->data['url'] ?? route('customer.notifications.index');

        return redirect()->to($targetUrl);
    }
}

