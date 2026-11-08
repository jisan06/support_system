@extends('customer.layouts.master')
@section('title', 'My Support Tickets')

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
                Track, search, and filter all your support tickets and communications.
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
    <a href="{{ route('customer.tickets.index', array_merge(request()->except('page'), ['status' => 'waiting_for_customer'])) }}"
       class="status-tab {{ request('status') === 'waiting_for_customer' ? 'active' : '' }}">
        <span>Waiting for You</span>
        <span class="badge-count">{{ $statusCounts['waiting_for_customer'] ?? 0 }}</span>
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

{{-- Filter Error Messages --}}
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-3 p-3" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class="bx bx-error-circle font-size-18 me-2 text-danger"></i>
            <span class="fw-bold font-size-13">Please correct the following filter issues:</span>
        </div>
        <ul class="mb-0 ps-4 font-size-12">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Comprehensive Search & Filter Controls --}}
<div class="filter-card">
    <form action="{{ route('customer.tickets.index') }}" method="GET">
        {{-- Preserve active status tab filter if not explicitly overridden --}}
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="row g-2 align-items-center">
            {{-- Search by ticket number or subject --}}
            <div class="col-12 col-md-4 col-lg-4">
                <label class="form-label font-size-11 text-muted fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bx bx-search font-size-14"></i>
                    </span>
                    <input type="text"
                        name="search"
                        value="{{ old('search', request('search')) }}"
                        class="form-control form-control-sm border-start-0 @error('search') is-invalid @enderror"
                        placeholder="Ticket # or Subject..."
                    >
                </div>
            </div>

            {{-- Filter by Department --}}
            <div class="col-6 col-md-3 col-lg-3">
                <label class="form-label font-size-11 text-muted fw-semibold mb-1">Department</label>
                <select name="department" class="form-select form-select-sm @error('department') is-invalid @enderror">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ old('department', request('department')) == $dept ? 'selected' : '' }}>
                            {{ $dept }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter by Priority --}}
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label font-size-11 text-muted fw-semibold mb-1">Priority</label>
                <select name="priority" class="form-select form-select-sm @error('priority') is-invalid @enderror">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $val => $label)
                        <option value="{{ $val }}" {{ old('priority', request('priority')) === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range: From & To --}}
            <div class="col-6 col-md-3 col-lg-3">
                <label class="form-label font-size-11 text-muted fw-semibold mb-1">Date Range</label>
                <div class="d-flex align-items-center gap-1">
                    <input type="date"
                        name="date_from"
                        value="{{ old('date_from', request('date_from')) }}"
                        class="form-control form-control-sm @error('date_from') is-invalid @enderror"
                        title="From Date"
                    >
                    <span class="text-muted font-size-11">&ndash;</span>
                    <input type="date"
                        name="date_to"
                        value="{{ old('date_to', request('date_to')) }}"
                        class="form-control form-control-sm @error('date_to') is-invalid @enderror"
                        title="To Date"
                    >
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="col-12 d-flex justify-content-end gap-2 pt-2 border-top mt-2">
                @if(request()->hasAny(['search', 'priority', 'status', 'department', 'date_from', 'date_to']))
                    <a href="{{ route('customer.tickets.index') }}" class="btn btn-sm btn-light border rounded-pill px-3 text-muted">
                        <i class="bx bx-reset me-1"></i> Clear Filters
                    </a>
                @endif
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">
                    <i class="bx bx-filter-alt me-1"></i> Apply Filters
                </button>
            </div>
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
                        <span class="fw-bold font-size-13 text-primary font-monospace">#{{ $ticket->ticket_number }}</span>

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
                @if(request()->hasAny(['search', 'status', 'priority', 'department', 'date_from', 'date_to']))
                    We couldn't find any tickets matching your filter criteria. Try adjusting or clearing your filters.
                @else
                    You have not submitted any support tickets yet. Need help? Our dedicated support team is ready to assist.
                @endif
            </p>
            @if(request()->hasAny(['search', 'status', 'priority', 'department', 'date_from', 'date_to']))
                <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-secondary rounded-pill px-4 font-size-13">
                    <i class="bx bx-reset me-1"></i> Clear All Filters
                </a>
            @else
                <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary rounded-pill px-4 py-2 font-size-13 fw-semibold">
                    <i class="bx bx-plus me-1"></i> Create Your First Ticket
                </a>
            @endif
        </div>
    @endforelse

    {{-- Pagination with Preserved Filters --}}
    @if($tickets->hasPages())
        <div class="p-3 bg-white border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
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
