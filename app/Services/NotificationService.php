<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function getUnreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function getLatest(int $limit = 5)
    {
        return Auth::user()->unreadNotifications()->latest()->take($limit)->get();
    }

    public function markAsRead(string $id): void
    {
        Auth::user()->notifications()->findOrFail($id)->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function delete(string $id): bool
    {
        return Auth::user()->notifications()->findOrFail($id)->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            return Auth::user()->notifications()->whereIn('id', $ids)->delete();
        });
    }

    public function sendTestNotification(): void
    {
        notify(
            Auth::user(),
            __('notification.test_title'),
            __('notification.test_message'),
            'info',
            route('notifications.index')
        );
    }

    /**
     * Get unread notifications data for AJAX polling.
     */
    public function getPollingData(): array
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications()->count();
        $latest = $user->unreadNotifications()->latest()->take(5)->get();

        return [
            'count' => $unreadCount,
            'notifications' => $latest->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'title'      => $n->data['title'] ?? '',
                    'message'    => $n->data['message'] ?? '',
                    'type'       => $n->data['type'] ?? 'info',
                    'url'        => $n->data['url'] ?? null,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            }),
        ];
    }
}
