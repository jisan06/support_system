@extends('admin.layouts.master')
@section('title', trans_choice('menu.ticket', 2))

@section('content')
<div class="row item-list-wrap">
    <div class="col-12">
        <div class="card">
            <x-table.header
                :title="trans_choice('menu.ticket', 2)"
                icon="bx bx-support"
                :tabs="[
                    ['label' => 'All Tickets', 'route' => 'tickets.index', 'icon' => 'bx bx-list-ul'],
                    ['label' => 'Import Tickets', 'route' => 'tickets.import.index', 'icon' => 'bx bx-upload', 'permission' => 'create_ticket'],
                ]"
                :add-button="['label'=> __('permission.create_ticket'), 'route'=>'tickets.create', 'icon'=>'fas fa-plus-circle', 'permission' => 'create_ticket']"
            />

            {{-- Filter & Search Form --}}
            <div class="card-body border-bottom bg-light bg-opacity-25 py-3">
                <form method="GET" action="{{ route('tickets.index') }}" id="adminTicketFilterForm">
                    <div class="row g-2">
                        {{-- Search Input --}}
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted">
                                    <i class="bx bx-search"></i>
                                </span>
                                <input type="text"
                                    name="search"
                                    class="form-control form-control-sm"
                                    placeholder="Ticket #, subject, customer..."
                                    value="{{ request('search') }}"
                                >
                            </div>
                        </div>

                        {{-- Status Filter --}}
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority Filter --}}
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Priority</label>
                            <select name="priority" class="form-select form-select-sm">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $key => $label)
                                    <option value="{{ $key }}" {{ request('priority') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category Filter --}}
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ (request('category') == $cat || request('department') == $cat) ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SLA Status Filter --}}
                        <div class="col-6 col-md-3 col-lg-3">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">SLA Status</label>
                            <select name="sla_status" class="form-select form-select-sm">
                                <option value="">All SLA Statuses</option>
                                @foreach($slaStatuses as $key => $label)
                                    <option value="{{ $key }}" {{ request('sla_status') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assigned Agent Filter --}}
                        <div class="col-6 col-md-3 col-lg-3">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Assigned Agent</label>
                            <select name="assigned_to" class="form-select form-select-sm">
                                <option value="">All Agents</option>
                                <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>-- Unassigned --</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ request('assigned_to') == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Customer Filter --}}
                        <div class="col-6 col-md-3 col-lg-3">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Customer</label>
                            <select name="customer_id" class="form-select form-select-sm">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} ({{ $customer->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Created Date Range --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label font-size-12 text-muted fw-semibold mb-1">Created Date Range</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="date"
                                    name="date_from"
                                    class="form-control form-control-sm"
                                    value="{{ request('date_from') }}"
                                    title="From Date"
                                >
                                <span class="text-muted font-size-12">&ndash;</span>
                                <input type="date"
                                    name="date_to"
                                    class="form-control form-control-sm"
                                    value="{{ request('date_to') }}"
                                    title="To Date"
                                >
                            </div>
                        </div>

                        {{-- Filter Actions --}}
                        <div class="col-12 col-md-6 col-lg-2 d-flex align-items-end justify-content-end gap-2 mt-2 mt-lg-0">
                            @if(request()->hasAny(['search', 'status', 'priority', 'category', 'department', 'assigned_to', 'customer_id', 'sla_status', 'date_from', 'date_to']))
                                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-light border text-muted">
                                    <i class="bx bx-reset me-1"></i> Reset
                                </a>
                            @endif
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bx bx-filter-alt me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Ticket #</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Assigned Agent</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>SLA</th>
                                <th>Last Activity</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('tickets.show', $ticket->id) }}" class="fw-bold text-primary">
                                            {{ $ticket->ticket_number }}
                                        </a>
                                    </td>
                                    <td class="fw-medium">{{ $ticket->subject }}</td>
                                    <td>
                                        <div>
                                            <span class="fw-medium text-dark">{{ $ticket->customer?->name ?? 'N/A' }}</span>
                                            @if($ticket->customer?->email)
                                                <small class="text-muted d-block font-size-11">{{ $ticket->customer->email }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $ticket->department ?: 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($ticket->assignedTo)
                                            <span class="text-dark">{{ $ticket->assignedTo->name }}</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusBadge = match($ticket->status) {
                                                'open' => 'bg-info',
                                                'in_progress' => 'bg-warning',
                                                'resolved' => 'bg-success',
                                                'closed' => 'bg-secondary',
                                                default => 'bg-primary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusBadge }} text-uppercase">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $priorityBadge = match($ticket->priority) {
                                                'urgent' => 'bg-danger',
                                                'high' => 'bg-warning text-dark',
                                                'medium' => 'bg-primary',
                                                'low' => 'bg-light text-dark',
                                                default => 'bg-info'
                                            };
                                        @endphp
                                        <span class="badge {{ $priorityBadge }} text-uppercase">{{ $ticket->priority }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $ticket->sla_status->badgeClass() }}">
                                            {{ $ticket->sla_status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-conversation me-1"></i> View Thread
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="bx bx-inbox display-4 d-block mb-2 text-muted"></i>
                                        No tickets found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($tickets->hasPages())
                <div class="card-footer bg-white border-top-0 d-flex justify-content-end py-3">
                    {{ $tickets->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

