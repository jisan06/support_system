@extends('admin.layouts.master')
@section('title', 'Dashboard')
@section('content')

{{-- Update Notification --}}
@if($updateInfo && $updateInfo['update_available'])
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bx bx-revision font-size-22 me-2"></i>
            <div class="flex-grow-1">
                <strong>{{ __('update.new_version_available', ['version' => $updateInfo['latest_version']]) }}</strong>
                {{ __('update.you_are_running', ['version' => $updateInfo['current_version']]) }}
            </div>
            <a href="{{ route('update.index') }}" class="btn btn-sm btn-warning ms-3">
                <i class="bx bx-right-arrow-alt me-1"></i> {{ __('update.view_details') }}
            </a>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
@endif

@if(!empty($canViewTickets) && !empty($ticketStats))
{{-- Support Ticket Reporting Header & Date Filter --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card mb-2">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1"><i class="bx bx-support text-primary me-1"></i> Support Ticket Overview &amp; Reporting</h5>
                        <p class="text-muted font-size-12 mb-0">Real-time ticket volume, status breakdown, agent workload, and SLA performance.</p>
                    </div>
                    {{-- Date Range Filter Form --}}
                    <form action="{{ route('admin.dashboard') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('admin.dashboard', ['range' => 'all']) }}" class="btn {{ $dateFilter['range'] === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">All Time</a>
                            <a href="{{ route('admin.dashboard', ['range' => 'today']) }}" class="btn {{ $dateFilter['range'] === 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">Today</a>
                            <a href="{{ route('admin.dashboard', ['range' => '7days']) }}" class="btn {{ $dateFilter['range'] === '7days' ? 'btn-primary' : 'btn-outline-secondary' }}">Last 7 Days</a>
                            <a href="{{ route('admin.dashboard', ['range' => '30days']) }}" class="btn {{ $dateFilter['range'] === '30days' ? 'btn-primary' : 'btn-outline-secondary' }}">Last 30 Days</a>
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <input type="hidden" name="range" value="custom">
                            <input type="date" name="start_date" value="{{ $dateFilter['start_date'] ?? '' }}" class="form-control form-control-sm" style="max-width: 135px;" placeholder="From">
                            <span class="text-muted">-</span>
                            <input type="date" name="end_date" value="{{ $dateFilter['end_date'] ?? '' }}" class="form-control form-control-sm" style="max-width: 135px;" placeholder="To">
                            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                            @if($dateFilter['range'] !== 'all')
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-danger" title="Clear Filter"><i class="bx bx-x"></i></a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Ticket Stat Widgets Row 1 --}}
<div class="row">
    {{-- Total Tickets --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Total Tickets</p>
                        <h4 class="mb-0">{{ number_format($ticketStats['total']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-primary align-self-center">
                        <span class="avatar-title"><i class="bx bx-conversation font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('tickets.index') }}" class="text-primary font-size-12">
                        View Tickets <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Open Tickets --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Open Tickets</p>
                        <h4 class="mb-0 text-warning">{{ number_format($ticketStats['open']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-warning align-self-center">
                        <span class="avatar-title"><i class="bx bx-envelope-open font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="text-warning font-size-12">
                        View Open <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- In Progress Tickets --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">In Progress</p>
                        <h4 class="mb-0 text-info">{{ number_format($ticketStats['in_progress']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-info align-self-center">
                        <span class="avatar-title"><i class="bx bx-loader-circle font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('tickets.index', ['status' => 'in_progress']) }}" class="text-info font-size-12">
                        View In Progress <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Resolved Tickets --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Resolved Tickets</p>
                        <h4 class="mb-0 text-success">{{ number_format($ticketStats['resolved']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-success align-self-center">
                        <span class="avatar-title"><i class="bx bx-check-circle font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('tickets.index', ['status' => 'resolved']) }}" class="text-success font-size-12">
                        View Resolved <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Ticket Stat Widgets Row 2 --}}
<div class="row">
    {{-- Closed Tickets --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Closed Tickets</p>
                        <h4 class="mb-0 text-secondary">{{ number_format($ticketStats['closed']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-secondary align-self-center">
                        <span class="avatar-title"><i class="bx bx-archive font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('tickets.index', ['status' => 'closed']) }}" class="text-secondary font-size-12">
                        View Closed <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Unassigned Tickets --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Unassigned Tickets</p>
                        <h4 class="mb-0 {{ $ticketStats['unassigned'] > 0 ? 'text-danger' : 'text-dark' }}">{{ number_format($ticketStats['unassigned']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-{{ $ticketStats['unassigned'] > 0 ? 'danger' : 'light' }} align-self-center">
                        <span class="avatar-title {{ $ticketStats['unassigned'] > 0 ? 'text-white' : 'text-muted' }}"><i class="bx bx-user-x font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="font-size-12 text-muted">Awaiting assignment</span>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Approaching --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">SLA Approaching</p>
                        <h4 class="mb-0 text-warning">{{ number_format($ticketStats['sla_approaching']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-warning align-self-center">
                        <span class="avatar-title"><i class="bx bx-alarm-exclamation font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="font-size-12 text-muted">Near deadline warning</span>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Breached --}}
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">SLA Breached</p>
                        <h4 class="mb-0 text-danger">{{ number_format($ticketStats['sla_breached']) }}</h4>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-danger align-self-center">
                        <span class="avatar-title"><i class="bx bx-error-circle font-size-24"></i></span>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="font-size-12 text-danger">Exceeded resolution targets</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!empty($ticketInsights))
{{-- Ticket Insights: Volume by Status and Priority --}}
<div class="row">
    {{-- Volume by Status --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-pie-chart-alt me-1"></i> Ticket Volume by Status</h4>
            </div>
            <div class="card-body">
                @foreach($ticketInsights['volume_by_status'] as $sKey => $sData)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-medium font-size-13 text-capitalize">{{ $sData['label'] }}</span>
                            <span class="font-size-13 text-muted">{{ number_format($sData['count']) }} ({{ $sData['percentage'] }}%)</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $sData['color'] }}" role="progressbar" style="width: {{ $sData['percentage'] }}%;" aria-valuenow="{{ $sData['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Volume by Priority --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-bar-chart-alt-2 me-1"></i> Ticket Volume by Priority</h4>
            </div>
            <div class="card-body">
                @foreach($ticketInsights['volume_by_priority'] as $pKey => $pData)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="badge bg-{{ $pData['color'] }} bg-opacity-10 text-{{ $pData['color'] }} me-1 text-capitalize">{{ $pData['label'] }}</span>
                            </div>
                            <span class="font-size-13 text-muted">{{ number_format($pData['count']) }} ({{ $pData['percentage'] }}%)</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $pData['color'] }}" role="progressbar" style="width: {{ $pData['percentage'] }}%;" aria-valuenow="{{ $pData['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Departments & Agent Workload --}}
<div class="row">
    {{-- Tickets Grouped by Department --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-buildings me-1"></i> Tickets Grouped by Department</h4>
            </div>
            <div class="card-body p-0">
                @if(count($ticketInsights['by_department']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light font-size-12">
                                <tr>
                                    <th class="ps-3">Department</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-end pe-3">Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticketInsights['by_department'] as $dept)
                                    <tr>
                                        <td class="ps-3 fw-medium font-size-13">{{ $dept['name'] }}</td>
                                        <td class="text-center"><span class="badge bg-primary rounded-pill font-size-12 px-2 py-1">{{ $dept['count'] }}</span></td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $dept['percentage'] }}%;"></div>
                                                </div>
                                                <span class="font-size-12 text-muted" style="min-width: 32px;">{{ $dept['percentage'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-4 mb-0">No department ticket data available.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Agent Workload Overview --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-user-check me-1"></i> Agent Workload Overview</h4>
            </div>
            <div class="card-body p-0">
                @if(count($ticketInsights['agent_workload']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light font-size-12">
                                <tr>
                                    <th class="ps-3">Agent</th>
                                    <th class="text-center">Active (Open/Progress)</th>
                                    <th class="text-center">Resolved</th>
                                    <th class="text-end pe-3">Total Assigned</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticketInsights['agent_workload'] as $agent)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title rounded-circle bg-light text-primary fw-bold font-size-12">
                                                        {{ strtoupper(substr($agent->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="fw-medium font-size-13">{{ $agent->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $agent->open_assigned > 0 ? 'bg-warning text-dark' : 'bg-light text-muted' }} rounded-pill px-2 py-1 font-size-11">
                                                {{ $agent->open_assigned }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $agent->resolved_assigned > 0 ? 'bg-success' : 'bg-light text-muted' }} rounded-pill px-2 py-1 font-size-11">
                                                {{ $agent->resolved_assigned }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 fw-semibold font-size-13">
                                            {{ $agent->total_assigned }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-4 mb-0">No active support agents found.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Recent Support Tickets & Recently Resolved --}}
<div class="row">
    {{-- Recent Tickets --}}
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-list-ul me-1"></i> Recent Support Tickets</h4>
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if(count($ticketInsights['recent_tickets']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light font-size-12">
                                <tr>
                                    <th class="ps-3">Ticket #</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticketInsights['recent_tickets'] as $rTicket)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('tickets.show', $rTicket) }}" class="fw-semibold text-primary font-size-12">
                                                #{{ $rTicket->ticket_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('tickets.show', $rTicket) }}" class="text-dark font-size-13 text-truncate d-block" style="max-width: 220px;" title="{{ $rTicket->subject }}">
                                                {{ $rTicket->subject }}
                                            </a>
                                            <small class="text-muted font-size-11">{{ $rTicket->customer?->name }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $sColor = match($rTicket->status) {
                                                    'open' => 'warning', 'in_progress' => 'info',
                                                    'resolved' => 'success', 'closed' => 'secondary',
                                                    default => 'light'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $sColor }} text-capitalize font-size-11">{{ str_replace('_', ' ', $rTicket->status) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $pColor = match($rTicket->priority) {
                                                    'urgent' => 'danger', 'high' => 'warning',
                                                    'medium' => 'primary', 'low' => 'secondary',
                                                    default => 'muted'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $pColor }} bg-opacity-10 text-{{ $pColor }} text-capitalize font-size-11">{{ $rTicket->priority }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $rTicket->assignedTo?->name ?? 'Unassigned' }}</small>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('tickets.show', $rTicket) }}" class="btn btn-sm btn-light py-0 px-2 font-size-12">
                                                <i class="bx bx-show align-middle"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-4 mb-0">No tickets found for this period.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Recently Resolved Tickets --}}
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-check-double text-success me-1"></i> Recently Resolved Tickets</h4>
                <a href="{{ route('tickets.index', ['status' => 'resolved']) }}" class="btn btn-sm btn-outline-success font-size-12">View Resolved</a>
            </div>
            <div class="card-body p-0">
                @if(count($ticketInsights['recently_resolved']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light font-size-12">
                                <tr>
                                    <th class="ps-3">Ticket</th>
                                    <th>Resolved</th>
                                    <th class="text-end pe-3">Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticketInsights['recently_resolved'] as $resTicket)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('tickets.show', $resTicket) }}" class="fw-semibold text-primary font-size-12 d-block">
                                                #{{ $resTicket->ticket_number }}
                                            </a>
                                            <span class="text-dark font-size-12 text-truncate d-block" style="max-width: 170px;" title="{{ $resTicket->subject }}">
                                                {{ $resTicket->subject }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted" title="{{ $resTicket->resolved_at ?? $resTicket->updated_at }}">
                                                {{ ($resTicket->resolved_at ?? $resTicket->updated_at)->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td class="text-end pe-3">
                                            <small class="text-muted">{{ $resTicket->assignedTo?->name ?? 'Unassigned' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-4 mb-0">No resolved tickets yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endif

{{-- Recent Activity --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-history me-1"></i> Recent Activity</h4>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if(count($recentActivities) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">User</th>
                                    <th>Event</th>
                                    <th>Description</th>
                                    <th class="text-end pe-3">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentActivities as $activity)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-medium">{{ $activity['causer'] }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $colors = [
                                                    'created' => 'success', 'updated' => 'info',
                                                    'deleted' => 'warning', 'restored' => 'primary',
                                                    'force_deleted' => 'danger',
                                                ];
                                                $color = $colors[$activity['event']] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst($activity['event']) }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $activity['description'] }}</small></td>
                                        <td class="text-end pe-3">
                                            <small class="text-muted" title="{{ $activity['date'] }}">{{ $activity['time'] }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-info-circle font-size-24"></i>
                        <p class="mt-2 mb-0">No recent activity.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection