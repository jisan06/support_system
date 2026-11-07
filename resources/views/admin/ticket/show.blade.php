@extends('admin.layouts.master')
@section('title', 'Ticket #' . $ticket->ticket_number)
@section('content')
<div class="row">
    {{-- Ticket Information Header / Sidebar --}}
    <div class="col-xl-4 col-lg-5">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="bx bx-info-circle me-1"></i> Ticket Details</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary bg-soft text-primary font-size-18">
                            <i class="bx bx-support"></i>
                        </span>
                    </div>
                    <div>
                        <h6 class="font-size-15 mb-1">{{ $ticket->subject }}</h6>
                        <span class="badge bg-light text-muted font-size-12">{{ $ticket->ticket_number }}</span>
                    </div>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Status</span>
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
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Priority</span>
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
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Customer</span>
                        <span class="fw-semibold">{{ $ticket->customer?->name ?? 'Unknown' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Customer Email</span>
                        <span>{{ $ticket->customer?->email ?? '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Assigned To</span>
                        <span>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Created</span>
                        <span>{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                    </li>
                </ul>

                <div class="mt-3">
                    <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bx bx-arrow-back me-1"></i> Back to Tickets
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Conversation Thread & Action Composer --}}
    <div class="col-xl-8 col-lg-7">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Conversation History --}}
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bx bx-conversation me-1"></i> Conversation History</h5>
                <span class="badge bg-secondary font-size-12">{{ $messages->total() }} Messages</span>
            </div>
            <div class="card-body p-4" style="background: #fdfdfd;">
                @forelse($messages as $msg)
                    @php
                        $isInternal = $msg->isInternalNote();
                        $isStaffSender = $msg->user?->isAdmin();
                    @endphp

                    <div class="card mb-3 shadow-none border {{ $isInternal ? 'border-warning' : 'border-light-subtle' }}"
                         style="{{ $isInternal ? 'background-color: #fff9e6;' : ($isStaffSender ? 'background-color: #f7f9ff;' : 'background-color: #ffffff;') }}">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs me-2">
                                        <span class="avatar-title rounded-circle {{ $isInternal ? 'bg-warning text-dark' : ($isStaffSender ? 'bg-primary text-white' : 'bg-success text-white') }} font-size-13">
                                            <i class="{{ $isInternal ? 'bx bx-lock-alt' : ($isStaffSender ? 'bx bx-user-check' : 'bx bx-user') }}"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="fw-bold">{{ $msg->user?->name ?? 'System' }}</span>
                                        @if($isInternal)
                                            <span class="badge bg-warning text-dark ms-1">
                                                <i class="bx bx-lock-alt me-1"></i> Internal Note
                                            </span>
                                        @elseif($isStaffSender)
                                            <span class="badge bg-primary text-white ms-1">Staff</span>
                                        @else
                                            <span class="badge bg-success text-white ms-1">Customer</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-muted font-size-12">
                                    <i class="bx bx-time-five me-1"></i>{{ $msg->created_at->format('M d, Y h:i A') }}
                                    <small>({{ $msg->created_at->diffForHumans() }})</small>
                                </span>
                            </div>

                            @if($isInternal)
                                <div class="small text-warning-emphasis mb-2 fw-semibold">
                                    <i class="bx bx-shield-quarter me-1"></i> Private internal note &mdash; visible only to authorized internal staff.
                                </div>
                            @endif

                            <div class="message-content text-break" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $msg->content }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bx bx-chat display-4 d-block mb-2 text-muted"></i>
                        No conversation messages yet.
                    </div>
                @endforelse

                <div class="d-flex justify-content-center mt-3">
                    {{ $messages->links() }}
                </div>
            </div>
        </div>

        {{-- Reply & Internal Note Composer Tabs --}}
        <div class="card">
            <div class="card-header bg-light">
                <ul class="nav nav-tabs card-header-tabs" id="replyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="public-reply-tab" data-bs-toggle="tab" data-bs-target="#public-reply" type="button" role="tab" aria-controls="public-reply" aria-selected="true">
                            <i class="bx bx-reply me-1"></i> Public Reply
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-warning-emphasis" id="internal-note-tab" data-bs-toggle="tab" data-bs-target="#internal-note" type="button" role="tab" aria-controls="internal-note" aria-selected="false">
                            <i class="bx bx-lock-alt me-1"></i> Internal Note
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="replyTabsContent">
                    {{-- Public Reply Form --}}
                    <div class="tab-pane fade show active" id="public-reply" role="tabpanel" aria-labelledby="public-reply-tab">
                        <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST">
                            @csrf
                            <p class="text-muted small mb-2">
                                <i class="bx bx-globe me-1"></i> This message will be sent as a public response and visible to the customer.
                            </p>
                            <div class="mb-3">
                                <textarea name="content" rows="4" class="form-control" placeholder="Write your public reply here..." required minlength="2"></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-send me-1"></i> Send Public Reply
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Internal Note Form --}}
                    <div class="tab-pane fade" id="internal-note" role="tabpanel" aria-labelledby="internal-note-tab">
                        <form action="{{ route('tickets.internal_note', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="alert alert-warning py-2 mb-3 font-size-13">
                                <i class="bx bx-lock-alt me-1"></i> <strong>Confidential:</strong> Internal notes are strictly visible to staff/agents. Customers will never see this message.
                            </div>
                            <div class="mb-3">
                                <textarea name="content" rows="4" class="form-control bg-warning bg-opacity-10 border-warning" placeholder="Write your internal staff note here..." required minlength="2"></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning text-dark fw-semibold">
                                    <i class="bx bx-lock-alt me-1"></i> Save Internal Note
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

