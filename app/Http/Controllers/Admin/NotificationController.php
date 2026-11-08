<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationActionRequest;
use App\Services\NotificationService;
use App\DataTables\NotificationsDataTable;

class NotificationController extends Controller
{
    protected NotificationService $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function index(NotificationsDataTable $dataTable)
    {
        $data = [
            'page_type'       => 'index',
            'bulk_actions'    => [
                (object)['value' => 'trash', 'label' => __('common.delete')]
            ],
            'bulk_delete_url' => route('notifications.bulk_delete'),
            'unread_count'    => $this->service->getUnreadCount(),
        ];

        return $dataTable->render('admin.notification.index', $data);
    }

    public function markAsRead(string $id)
    {
        $this->service->markAsRead($id);
        return ResponseHelper::success([], __('notification.marked_as_read'));
    }

    /**
     * Open a notification: marks as read and redirects to destination URL.
     */
    public function open(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $targetUrl = $notification->data['url'] ?? route('notifications.index');

        return redirect()->to($targetUrl);
    }

    public function markAllAsRead()
    {
        $this->service->markAllAsRead();
        return ResponseHelper::success([], __('notification.all_marked_as_read'));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return ResponseHelper::success([], __('common.deleted'));
    }

    public function bulkDestroy(BulkNotificationActionRequest $request)
    {
        $this->service->bulkDelete($request->validated('ids'));
        return ResponseHelper::success([], __('common.deleted'));
    }

    public function sendTest()
    {
        $this->service->sendTestNotification();
        return ResponseHelper::success([], __('notification.test_sent'));
    }

    /**
     * AJAX endpoint for real-time polling.
     */
    public function poll()
    {
        return response()->json($this->service->getPollingData());
    }
}
