@extends('admin.layouts.master')
@section('title', trans_choice('menu.ticket', 2))

@section('content')
<div class="row item-list-wrap">
    <div class="col-12">
        <div class="card">
            <x-table.header
                :title="trans_choice('menu.ticket', 2)"
                icon="bx bx-support"
                :add-button="['label'=> __('permission.create_ticket'), 'route'=>'tickets.create', 'icon'=>'fas fa-plus-circle', 'permission' => 'create_ticket']"
            />
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Ticket #</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Priority</th>
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
                                        <span class="text-muted">{{ $ticket->customer?->name ?? 'N/A' }}</span>
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
                                    <td colspan="7" class="text-center py-5 text-muted">
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
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

