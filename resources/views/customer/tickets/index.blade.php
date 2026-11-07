@extends('customer.layouts.master')
@section('title', 'My Support Tickets')

@push('styles')
<style>
    .customer-page-header {
        background: linear-gradient(135deg, #f8faff 0%, #edf2f9 100%);
        border-radius: 14px;
        padding: 24px 28px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .status-tab {
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-tab:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .status-tab.active {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
    }
    .status-tab.active .badge-count {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }
    .badge-count {
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 10px;
        background: #f1f5f9;
        color: #475569;
    }
    .filter-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .ticket-list-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(18, 38, 63, 0.03);
        overflow: hidden;
    }
    .ticket-row {
        padding: 18px 24px;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .ticket-row:last-child {
        border-bottom: none;
    }
    .ticket-row:hover {
        background-color: #f8fafc;
    }
    .status-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }
    .status-open {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }
    .status-in_progress {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }
    .status-resolved {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }
    .status-closed {
        background-color: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .priority-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
    }
    .priority-dot-sm {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }
    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background-color: #f1f5f9;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 16px;
    }
</style>
@endpush

@section('content')
{{-- Modern Header --}}
<div class="customer-page-header">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 font-size-13">
                    <li class="breadcrumb-item">
                        <a href="{{ route('customer.dashboard') }}" class="text-decoration-none text-muted">
                            <i class="bx bx-home-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">My Tickets</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-1">My Support Tickets</h3>
            <p class="text-muted mb-0 font-size-14">
                Track, search, and manage all your submitted support requests and status updates.
            </p>
        </div>
        <div>
            <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary rounded-pill px-4 py-2 font-size-13 fw-semibold shadow-sm">
                <i class="bx bx-plus-circle me-1 align-middle"></i> Create New Ticket
            </a>
        </div>
    </div>
</div>

{{-- Quick Status Filter Tabs --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('customer.tickets.index', array_merge(request()->except(['status', 'page']))) }}"
       class="status-tab {{ empty(request('status')) ? 'active' : '' }}">
        <span>All Tickets</span>
        <span class="badge-count">{{ $statusCounts['all'] ?? 0 }}</span>
    </a>
    <a href="{{ route('customer.tickets.index', array_merge(request()->except('page'), ['status' => 'open'])) }}"
       class="status-tab {{ request('status') === 'open' ? 'active' : '' }}">
        <span>Open</span>
        <span class="badge-count">{{ $statusCounts['open'] ?? 0 }}</span>
    </a>
    <a href="{{ route('customer.tickets.index', array_merge(request()->except('page'), ['status' => 'in_progress'])) }}"
       class="status-tab {{ request('status') === 'in_progress' ? 'active' : '' }}">
        <span>In Progress</span>
        <span class="badge-count">{{ $statusCounts['in_progress'] ?? 0 }}</span>
    </a>
    <a href="{{ route('customer.tickets.index', array_merge(request()->except('page'), ['status' => 'resolved'])) }}"
       class="status-tab {{ request('status') === 'resolved' ? 'active' : '' }}">
        <span>Resolved</span>
        <span class="badge-count">{{ $statusCounts['resolved'] ?? 0 }}</span>
    </a>
    <a href="{{ route('customer.tickets.index', array_merge(request()->except('page'), ['status' => 'closed'])) }}"
       class="status-tab {{ request('status') === 'closed' ? 'active' : '' }}">
        <span>Closed</span>
        <span class="badge-count">{{ $statusCounts['closed'] ?? 0 }}</span>
    </a>
</div>

{{-- Search & Detail Filters Bar --}}
<div class="filter-card">
    <form action="{{ route('customer.tickets.index') }}" method="GET" class="row g-2 align-items-center">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bx bx-search font-size-14"></i>
                </span>
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    class="form-control form-control-sm border-start-0"
                    placeholder="Search by ticket # or subject..."
                >
            </div>
        </div>

        <div class="col-md-3">
            <select name="priority" class="form-select form-select-sm">
                <option value="">All Priorities</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
            </select>
        </div>

        <div class="col-md-4 d-flex gap-2 justify-content-md-end">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="bx bx-filter-alt me-1"></i> Apply Filter
            </button>
            @if(request()->hasAny(['search', 'priority', 'status']))
                <a href="{{ route('customer.tickets.index') }}" class="btn btn-sm btn-light border rounded-pill px-3 text-muted">
                    <i class="bx bx-reset me-1"></i> Clear
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Tickets Listing --}}
<div class="ticket-list-card">
    @forelse($tickets as $ticket)
        <div class="ticket-row">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- Left: Ticket Details --}}
                <div class="flex-grow-1" style="min-width: 280px;">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="fw-bold font-size-13 text-primary">#{{ $ticket->ticket_number }}</span>

                        @if($ticket->department)
                            <span class="badge bg-light text-secondary border font-size-11 fw-normal">
                                <i class="bx bx-folder me-1"></i>{{ $ticket->department }}
                            </span>
                        @endif

                        {{-- Status Pill --}}
                        <span class="status-pill status-{{ $ticket->status }}">
                            {{ str_replace('_', ' ', $ticket->status) }}
                        </span>

                        {{-- Priority Indicator --}}
                        @php
                            $dotColor = match($ticket->priority) {
                                'urgent' => 'bg-danger',
                                'high'   => 'bg-warning',
                                'medium' => 'bg-info',
                                default  => 'bg-success',
                            };
                        @endphp
                        <span class="priority-indicator bg-light text-dark border">
                            <span class="priority-dot-sm {{ $dotColor }}"></span>
                            <span class="text-capitalize font-size-11">{{ $ticket->priority }}</span>
                        </span>

                        {{-- SLA Status --}}
                        @php
                            $sla = $ticket->sla_status;
                            $slaBadgeStyle = match($sla) {
                                \App\Enums\SlaStatus::ON_TRACK           => 'bg-success-subtle text-success border border-success-subtle',
                                \App\Enums\SlaStatus::APPROACHING_BREACH => 'bg-warning-subtle text-warning border border-warning-subtle',
                                \App\Enums\SlaStatus::BREACHED           => 'bg-danger-subtle text-danger border border-danger-subtle',
                                \App\Enums\SlaStatus::COMPLETED          => 'bg-info-subtle text-info border border-info-subtle',
                            };
                        @endphp
                        <span class="badge {{ $slaBadgeStyle }} font-size-11 fw-semibold">
                            <i class="bx bx-stopwatch me-1"></i>SLA: {{ $sla->label() }}
                        </span>
                    </div>

                    {{-- Subject --}}
                    <h6 class="mb-1 fw-bold">
                        <a href="{{ route('customer.tickets.show', $ticket->id) }}" class="text-dark text-decoration-none">
                            {{ $ticket->subject }}
                        </a>
                    </h6>

                    {{-- Metadata Footer --}}
                    <div class="d-flex flex-wrap align-items-center gap-3 font-size-12 text-muted">
                        <span>
                            <i class="bx bx-calendar me-1"></i>Created {{ $ticket->created_at->format('M d, Y \a\t h:i A') }}
                        </span>
                        <span>
                            <i class="bx bx-time me-1"></i>Last activity: {{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : $ticket->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                {{-- Right: Action --}}
                <div>
                    <a href="{{ route('customer.tickets.show', $ticket->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 font-size-13">
                        View Details <i class="bx bx-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bx bx-receipt"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Support Tickets Found</h5>
            <p class="text-muted font-size-14 mb-3" style="max-width: 440px; margin: 0 auto;">
                @if(request()->hasAny(['search', 'status', 'priority']))
                    We couldn't find any tickets matching your filter criteria. Try clearing your filters.
                @else
                    You have not submitted any support tickets yet. Need help? Our dedicated support team is ready to assist.
                @endif
            </p>
            @if(request()->hasAny(['search', 'status', 'priority']))
                <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-secondary rounded-pill px-4 font-size-13">
                    <i class="bx bx-reset me-1"></i> Clear Filters
                </a>
            @else
                <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary rounded-pill px-4 py-2 font-size-13 fw-semibold">
                    <i class="bx bx-plus me-1"></i> Create Your First Ticket
                </a>
            @endif
        </div>
    @endforelse

    {{-- Pagination --}}
    @if($tickets->hasPages())
        <div class="p-3 bg-white border-top d-flex justify-content-between align-items-center">
            <span class="font-size-12 text-muted">
                Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }} of {{ $tickets->total() }} tickets
            </span>
            <div>
                {{ $tickets->links() }}
            </div>
        </div>
    @endif
</div>
@endsection

