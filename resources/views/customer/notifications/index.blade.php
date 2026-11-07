@extends('customer.layouts.master')
@section('title', 'Ticket Notifications')

@push('styles')
<style>
    .notif-hero-card {
        background: linear-gradient(135deg, #f8faff 0%, #eef2f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 24px;
    }
    .notif-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 12px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 8px rgba(18, 38, 63, 0.02);
    }
    .notif-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 14px rgba(18, 38, 63, 0.06);
    }
    .notif-card.is-unread {
        background: #f8fbff;
        border-left: 4px solid #2563eb;
    }
    .notif-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .notif-filter-pill {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: all 0.15s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="customer-notifications-container">

    {{-- Hero & Navigation Header --}}
    <div class="notif-hero-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0 font-size-13">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Notifications</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-dark mb-0">Ticket Notifications</h3>
                    @if($unreadCount > 0)
                        <span class="badge bg-danger rounded-pill font-size-12 px-2 py-1">{{ $unreadCount }} Unread</span>
                    @endif
                </div>
                <p class="text-muted font-size-14 mb-0 mt-1">Real-time alerts for staff replies, status updates, and resolution milestones on your tickets.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($unreadCount > 0)
                    <form action="{{ route('customer.notifications.markAllRead') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary px-3 py-2 rounded-3 font-size-13 fw-semibold d-inline-flex align-items-center">
                            <i class="bx bx-check-double me-1 font-size-16"></i> Mark All as Read
                        </button>
                    </form>
                @endif
                <a href="{{ route('customer.tickets.index') }}" class="btn btn-light px-3 py-2 rounded-3 font-size-13">
                    <i class="bx bx-receipt me-1"></i> My Tickets
                </a>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('customer.notifications.index', ['filter' => 'all']) }}"
               class="notif-filter-pill {{ $filter === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-white text-secondary border' }}">
                All Notifications <span class="badge {{ $filter === 'all' ? 'bg-white text-primary' : 'bg-light text-dark' }} rounded-pill ms-2 font-size-11">{{ $totalCount }}</span>
            </a>
            <a href="{{ route('customer.notifications.index', ['filter' => 'unread']) }}"
               class="notif-filter-pill {{ $filter === 'unread' ? 'bg-primary text-white shadow-sm' : 'bg-white text-secondary border' }}">
                Unread Only <span class="badge {{ $filter === 'unread' ? 'bg-white text-primary' : 'bg-light text-dark' }} rounded-pill ms-2 font-size-11">{{ $unreadCount }}</span>
            </a>
        </div>
        <div class="text-muted font-size-13">
            Showing {{ $notifications->firstItem() ?? 0 }}-{{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} alerts
        </div>
    </div>

    {{-- Notifications List --}}
    @if($notifications->count() > 0)
        <div class="notifications-list mb-4">
            @foreach($notifications as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    $notifType = $notification->data['type'] ?? 'info';
                    $title = $notification->data['title'] ?? 'Ticket Notification';
                    $message = $notification->data['message'] ?? '';
                    $targetUrl = $notification->data['url'] ?? route('customer.tickets.index');

                    // Determine icon and color based on notification type
                    $iconClass = 'bx bx-bell';
                    $boxClass = 'bg-primary bg-opacity-10 text-primary';

                    if ($notifType === 'success' || str_contains(strtolower($title), 'resolved')) {
                        $iconClass = 'bx bx-check-circle';
                        $boxClass = 'bg-success bg-opacity-10 text-success';
                    } elseif ($notifType === 'warning') {
                        $iconClass = 'bx bx-error-circle';
                        $boxClass = 'bg-warning bg-opacity-10 text-warning';
                    } elseif ($notifType === 'danger') {
                        $iconClass = 'bx bx-x-circle';
                        $boxClass = 'bg-danger bg-opacity-10 text-danger';
                    } elseif (str_contains(strtolower($title), 'reply')) {
                        $iconClass = 'bx bx-message-rounded-dots';
                        $boxClass = 'bg-info bg-opacity-10 text-info';
                    } elseif (str_contains(strtolower($title), 'closed')) {
                        $iconClass = 'bx bx-archive';
                        $boxClass = 'bg-secondary bg-opacity-10 text-secondary';
                    }
                @endphp

                <div class="notif-card {{ $isUnread ? 'is-unread' : '' }}">
                    <div class="d-flex align-items-start gap-3">
                        <div class="notif-icon-box {{ $boxClass }}">
                            <i class="{{ $iconClass }}"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                <h6 class="fw-bold text-dark mb-0 font-size-14">
                                    {{ $title }}
                                    @if($isUnread)
                                        <span class="badge bg-primary rounded-pill font-size-11 ms-1">New</span>
                                    @endif
                                </h6>
                                <span class="text-muted font-size-12 d-flex align-items-center">
                                    <i class="bx bx-time-five me-1"></i> {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-muted font-size-13 mb-2">{{ $message }}</p>

                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('customer.notifications.open', $notification->id) }}" class="btn btn-sm btn-primary px-3 rounded-2 font-size-12 d-inline-flex align-items-center">
                                    <i class="bx bx-arrow-to-right me-1"></i> View Ticket
                                </a>

                                @if($isUnread)
                                    <form action="{{ route('customer.notifications.read', $notification->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary px-2 rounded-2 font-size-12">
                                            Mark as Read
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center">
            {{ $notifications->links('pagination::bootstrap-5') }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 72px; height: 72px;">
                    <i class="bx bx-bell-off font-size-36"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">No Notifications Found</h5>
                <p class="text-muted font-size-14 mb-4">
                    @if($filter === 'unread')
                        You have no unread notifications right now.
                    @else
                        You don't have any ticket notifications yet. We'll alert you whenever your tickets are updated.
                    @endif
                </p>
                @if($filter === 'unread')
                    <a href="{{ route('customer.notifications.index', ['filter' => 'all']) }}" class="btn btn-outline-primary px-4 py-2 rounded-3 font-size-13">
                        View All Notifications
                    </a>
                @else
                    <a href="{{ route('customer.tickets.index') }}" class="btn btn-primary px-4 py-2 rounded-3 font-size-13">
                        <i class="bx bx-receipt me-1"></i> Go to My Tickets
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

