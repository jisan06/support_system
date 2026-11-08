@extends('customer.layouts.master')
@section('title', 'Customer Dashboard')

@push('styles')
<style>
    /* Customer Portal Dashboard Styling */
    .dashboard-hero-card {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        border-radius: 14px;
        color: #ffffff;
        padding: 28px 32px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
    }
    .stat-card-modern {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        text-decoration: none !important;
        display: block;
        height: 100%;
        box-shadow: 0 2px 8px rgba(18, 38, 63, 0.02);
    }
    .stat-card-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(18, 38, 63, 0.08);
        border-color: #cbd5e1;
    }
    .stat-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .dashboard-panel-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(18, 38, 63, 0.03);
        overflow: hidden;
    }
    .customer-hero-badge {
        display: inline-flex;
        align-items: center;
        background-color: #ffffff !important;
        color: #1e40af !important;
        padding: 5px 14px;
        border-radius: 50rem;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.2px;
        line-height: 1.4;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .customer-hero-badge i,
    .customer-hero-badge span {
        color: #1e40af !important;
    }
    .assistance-card {
        background: linear-gradient(135deg, #f8faff 0%, #edf2f9 100%);
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        margin-bottom: 0 !important;
    }
    .dashboard-panel-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
        background-color: #fafbfd;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dashboard-panel-body {
        padding: 20px 22px;
    }
    .ticket-row-item {
        padding: 14px 16px;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
        margin-bottom: 10px;
        background-color: #ffffff;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .ticket-row-item:last-child {
        margin-bottom: 0;
    }
    .ticket-row-item:hover {
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    .notification-item-card {
        padding: 12px 14px;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
        margin-bottom: 10px;
        transition: background-color 0.15s ease;
    }
    .notification-item-card:last-child {
        margin-bottom: 0;
    }
    .notification-item-card.unread {
        background-color: #f8fbff;
        border-left: 3px solid #2563eb;
    }
</style>
@endpush

@section('content')
<div class="customer-dashboard-container">

    {{-- Welcome Hero Banner with Quick Actions --}}
    <div class="dashboard-hero-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="customer-hero-badge">
                    <i class="bx bx-check-shield me-1 font-size-14" style="color: #1e40af !important;"></i>
                    <span style="color: #1e40af !important; font-weight: 700;">Customer Support Hub</span>
                </div>
                <h3 class="fw-bold mb-1 text-white">Welcome back, {{ $customer->name }}!</h3>
                <p class="mb-0 text-white-50 font-size-14" style="max-width: 650px;">
                    Track ticket progress, view SLA guarantees, and get assistance from our dedicated support team in real-time.
                </p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="{{ route('customer.tickets.create') }}" class="btn btn-light px-3 py-2 rounded-3 font-size-14 fw-semibold text-primary d-inline-flex align-items-center shadow-sm">
                    <i class="bx bx-plus-circle me-1 font-size-16"></i> Create New Ticket
                </a>
                <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-light px-3 py-2 rounded-3 font-size-14 d-inline-flex align-items-center">
                    <i class="bx bx-receipt me-1 font-size-16"></i> View All Tickets
                </a>
            </div>
        </div>
    </div>

    {{-- Ticket Summary Statistics (5 Status Cards) --}}
    <div class="row g-3 mb-4">
        {{-- Total Tickets --}}
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('customer.tickets.index') }}" class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-12 text-uppercase fw-semibold">Total Tickets</span>
                        <h3 class="fw-bold text-dark my-1">{{ $counts['total'] }}</h3>
                        <small class="text-muted font-size-11">All time</small>
                    </div>
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bx bx-receipt"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Open --}}
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('customer.tickets.index', ['status' => 'open']) }}" class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-12 text-uppercase fw-semibold">Open Tickets</span>
                        <h3 class="fw-bold text-warning my-1">{{ $counts['open'] }}</h3>
                        <small class="text-muted font-size-11">Awaiting review</small>
                    </div>
                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i class="bx bx-time-five"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- In Progress --}}
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('customer.tickets.index', ['status' => 'in_progress']) }}" class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-12 text-uppercase fw-semibold">In Progress</span>
                        <h3 class="fw-bold text-info my-1">{{ $counts['in_progress'] }}</h3>
                        <small class="text-muted font-size-11">Being worked on</small>
                    </div>
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="bx bx-loader-circle"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Resolved --}}
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('customer.tickets.index', ['status' => 'resolved']) }}" class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-12 text-uppercase fw-semibold">Resolved Tickets</span>
                        <h3 class="fw-bold text-success my-1">{{ $counts['resolved'] }}</h3>
                        <small class="text-muted font-size-11">Solutions delivered</small>
                    </div>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bx bx-check-circle"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Closed --}}
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('customer.tickets.index', ['status' => 'closed']) }}" class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-12 text-uppercase fw-semibold">Closed Tickets</span>
                        <h3 class="fw-bold text-secondary my-1">{{ $counts['closed'] }}</h3>
                        <small class="text-muted font-size-11">Completed tickets</small>
                    </div>
                    <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
                        <i class="bx bx-archive"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- SLA Overview Alert Section --}}
    @if($slaOverview['approachingCount'] > 0 || $slaOverview['breachedCount'] > 0)
        <div class="card border-0 mb-4 shadow-sm" style="border-radius: 12px; border-left: 4px solid {{ $slaOverview['breachedCount'] > 0 ? '#dc3545' : '#f59e0b' }} !important; background: #fffcf8;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle {{ $slaOverview['breachedCount'] > 0 ? 'bg-danger text-white' : 'bg-warning text-white' }} d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 20px;">
                            <i class="bx {{ $slaOverview['breachedCount'] > 0 ? 'bx-error-circle' : 'bx-alarm-exclamation' }}"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1 font-size-14">SLA Milestone Attention Needed</h6>
                            <p class="text-muted font-size-13 mb-0">
                                You have
                                @if($slaOverview['breachedCount'] > 0)
                                    <strong class="text-danger">{{ $slaOverview['breachedCount'] }} breached</strong>
                                @endif
                                @if($slaOverview['breachedCount'] > 0 && $slaOverview['approachingCount'] > 0)
                                    and
                                @endif
                                @if($slaOverview['approachingCount'] > 0)
                                    <strong class="text-warning">{{ $slaOverview['approachingCount'] }} approaching deadline</strong>
                                @endif
                                {{ \Illuminate\Support\Str::plural('ticket', $slaOverview['approachingCount'] + $slaOverview['breachedCount']) }} requiring support team escalation.
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('customer.tickets.index') }}" class="btn btn-sm btn-outline-dark px-3 rounded-pill font-size-12">
                            View Affected Tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 mb-4 shadow-sm" style="border-radius: 12px; background: #f0fdf4; border-left: 4px solid #16a34a !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-check-shield text-success font-size-22"></i>
                        <span class="font-size-13 text-dark fw-medium">All SLA Guarantees On Track: Your support requests are progressing smoothly within guaranteed milestone targets.</span>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 font-size-11">Healthy</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Content Grid: Recent Tickets & Notifications --}}
    <div class="row g-4 mb-4 align-items-start">
        {{-- Left: Recently Created or Updated Tickets --}}
        <div class="col-lg-7">
            <div class="dashboard-panel-card">
                <div class="dashboard-panel-header">
                    <div>
                        <h5 class="fw-bold text-dark mb-0 font-size-15">Recent Support Tickets</h5>
                        <small class="text-muted font-size-12">Latest tickets submitted or updated</small>
                    </div>
                    <a href="{{ route('customer.tickets.index') }}" class="btn btn-sm btn-outline-primary px-3 rounded-3 font-size-12">
                        View All ({{ $counts['total'] }})
                    </a>
                </div>
                <div class="dashboard-panel-body">
                    @if($recentTickets->count() > 0)
                        <div class="ticket-list">
                            @foreach($recentTickets as $ticket)
                                @php
                                    $statusBadgeClass = match($ticket->status) {
                                        'open'                 => 'bg-warning text-dark',
                                        'in_progress'          => 'bg-info text-white',
                                        'waiting_for_customer' => 'bg-primary text-white',
                                        'resolved'             => 'bg-success text-white',
                                        'closed'               => 'bg-secondary text-white',
                                        default                => 'bg-light text-dark',
                                    };
                                    $priorityBadgeClass = match($ticket->priority) {
                                        'urgent' => 'text-danger fw-bold',
                                        'high'   => 'text-warning fw-semibold',
                                        'medium' => 'text-primary',
                                        default  => 'text-muted',
                                    };
                                    $sla = $ticket->sla_status;
                                @endphp
                                <div class="ticket-row-item">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ route('customer.tickets.show', $ticket) }}" class="fw-bold text-primary font-size-13 text-decoration-none">
                                                #{{ $ticket->ticket_number }}
                                            </a>
                                            <span class="badge {{ $statusBadgeClass }} rounded-pill font-size-11 text-capitalize px-2 py-1">
                                                {{ str_replace('_', ' ', $ticket->status) }}
                                            </span>
                                            <span class="font-size-11 {{ $priorityBadgeClass }} text-capitalize">
                                                <i class="bx bxs-circle font-size-8 me-1 align-middle"></i>{{ $ticket->priority }}
                                            </span>
                                        </div>
                                        <span class="badge {{ $sla->badgeClass() }} rounded-pill font-size-10 px-2 py-1">
                                            SLA: {{ $sla->label() }}
                                        </span>
                                    </div>
                                    <h6 class="text-dark font-size-14 fw-semibold mb-1 text-truncate">
                                        <a href="{{ route('customer.tickets.show', $ticket) }}" class="text-dark text-decoration-none">
                                            {{ $ticket->subject }}
                                        </a>
                                    </h6>
                                    <div class="d-flex align-items-center justify-content-between text-muted font-size-12 mt-2 pt-1 border-top">
                                        <span>
                                            <i class="bx bx-time-five me-1 align-middle"></i> Updated {{ $ticket->updated_at->diffForHumans() }}
                                        </span>
                                        <a href="{{ route('customer.tickets.show', $ticket) }}" class="text-primary text-decoration-none font-size-12 fw-medium">
                                            Open Ticket <i class="bx bx-chevron-right align-middle"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bx bx-receipt text-muted font-size-48 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">No Tickets Yet</h6>
                            <p class="text-muted font-size-13 mb-3">You haven't submitted any support requests.</p>
                            <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary btn-sm px-3 rounded-3">
                                <i class="bx bx-plus-circle me-1"></i> Submit First Ticket
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Recent Notifications & Quick Help --}}
        <div class="col-lg-5 d-flex flex-column gap-4">
            {{-- Recent Notifications Card --}}
            <div class="dashboard-panel-card">
                <div class="dashboard-panel-header">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0 font-size-15">Recent Alerts</h5>
                        @if($unreadNotificationsCount > 0)
                            <span class="badge bg-danger rounded-pill font-size-11 px-2">{{ $unreadNotificationsCount }} New</span>
                        @endif
                    </div>
                    <a href="{{ route('customer.notifications.index') }}" class="btn btn-sm btn-outline-secondary px-2 rounded-3 font-size-12">
                        View All
                    </a>
                </div>
                <div class="dashboard-panel-body">
                    @if($recentNotifications->count() > 0)
                        <div class="notifications-list">
                            @foreach($recentNotifications as $notification)
                                @php
                                    $isUnread = $notification->read_at === null;
                                    $notifTitle = $notification->data['title'] ?? 'Ticket Notification';
                                    $notifMsg = $notification->data['message'] ?? '';
                                    $openUrl = route('customer.notifications.open', $notification->id);
                                @endphp
                                <div class="notification-item-card {{ $isUnread ? 'unread' : '' }}">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                        <a href="{{ $openUrl }}" class="fw-semibold text-dark font-size-13 text-decoration-none text-truncate">
                                            {{ $notifTitle }}
                                        </a>
                                        <span class="text-muted font-size-11 flex-shrink-0">
                                            {{ $notification->created_at->diffForHumans(null, true) }}
                                        </span>
                                    </div>
                                    <p class="text-muted font-size-12 mb-0 text-truncate">{{ $notifMsg }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-bell-off text-muted font-size-36 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1 font-size-13">No New Notifications</h6>
                            <p class="text-muted font-size-12 mb-0">We will notify you here when staff reply to your tickets.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Help Box --}}
            <div class="card assistance-card border-0 shadow-sm p-4 text-center">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 50px; height: 50px; margin: 0 auto; font-size: 24px;">
                    <i class="bx bx-support"></i>
                </div>
                <h5 class="fw-bold text-dark font-size-15 mb-1">Need Immediate Assistance?</h5>
                <p class="text-muted font-size-13 mb-3">Our support team is standing by to assist with your technical inquiries.</p>
                <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary px-3 py-2 rounded-3 font-size-13 fw-semibold">
                    <i class="bx bx-plus-circle me-1"></i> Open a Ticket
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
