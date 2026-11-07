@extends('customer.layouts.master')
@section('title', 'Ticket #' . $ticket->ticket_number)

@push('styles')
<style>
    .customer-ticket-show-header {
        background: linear-gradient(135deg, #f8faff 0%, #edf2f9 100%);
        border-radius: 14px;
        padding: 24px 28px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .conversation-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(18, 38, 63, 0.03);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .message-item {
        padding: 22px 24px;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .message-item:last-child {
        border-bottom: none;
    }
    .message-agent {
        background-color: #f8fbff;
        border-left: 3px solid #3b82f6;
    }
    .message-customer {
        background-color: #ffffff;
    }
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.06);
    }
    .avatar-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .avatar-agent {
        background-color: #e0f2fe;
        color: #0284c7;
    }
    .avatar-customer {
        background-color: #eff6ff;
        color: #3b82f6;
    }
    .message-bubble {
        color: #334155;
        font-size: 14px;
        line-height: 1.65;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .attachment-card {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 12.5px;
        color: #1e293b;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .attachment-card:hover {
        background: #f8fafc;
        border-color: #3b82f6;
        color: #2563eb;
    }
    .sidebar-widget {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(18, 38, 63, 0.03);
        padding: 20px;
        margin-bottom: 20px;
    }
    .sidebar-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 9px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 13px;
    }
    .sidebar-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .status-pill-show {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
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
    .form-control-modern {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        color: #1e293b;
        transition: all 0.2s ease-in-out;
    }
    .form-control-modern:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
</style>
@endpush

@section('content')
{{-- Ticket Overview Header --}}
<div class="customer-ticket-show-header">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 font-size-13">
                <li class="breadcrumb-item">
                    <a href="{{ route('customer.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('customer.tickets.index') }}" class="text-decoration-none text-muted">My Tickets</a>
                </li>
                <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">
                    #{{ $ticket->ticket_number }}
                </li>
            </ol>
        </nav>
        <a href="{{ route('customer.tickets.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-size-13">
            <i class="bx bx-arrow-back me-1"></i> Back to My Tickets
        </a>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-white text-primary border font-size-12 px-2 py-1 fw-bold">
                    #{{ $ticket->ticket_number }}
                </span>
                <span class="status-pill-show status-{{ $ticket->status }}">
                    {{ str_replace('_', ' ', $ticket->status) }}
                </span>
                @php
                    $dotColor = match($ticket->priority) {
                        'urgent' => 'bg-danger',
                        'high'   => 'bg-warning',
                        'medium' => 'bg-info',
                        default  => 'bg-success',
                    };
                @endphp
                <span class="badge bg-light text-dark border font-size-11 px-2 py-1">
                    <span class="d-inline-block rounded-circle me-1 {{ $dotColor }}" style="width: 7px; height: 7px;"></span>
                    {{ ucfirst($ticket->priority) }} Priority
                </span>

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

            <h4 class="fw-bold text-dark mb-1">{{ $ticket->subject }}</h4>
            <div class="font-size-12 text-muted">
                <span><i class="bx bx-calendar me-1"></i>Created {{ $ticket->created_at->format('M d, Y \a\t h:i A') }}</span>
                <span class="mx-2">&bull;</span>
                <span><i class="bx bx-time me-1"></i>Last active {{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Left: Conversation Timeline (8 cols) --}}
    <div class="col-lg-8">
        {{-- Conversation Card --}}
        <div class="conversation-card">
            <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-conversation font-size-20 text-primary"></i>
                    <h6 class="fw-bold text-dark mb-0 font-size-15">Public Conversation</h6>
                </div>
                <span class="badge bg-light text-muted border font-size-12">
                    {{ $messages->total() }} {{ \Illuminate\Support\Str::plural('message', $messages->total()) }}
                </span>
            </div>

            <div class="messages-stream">
                @forelse($messages as $message)
                    @php
                        $isAgent = $message->user && $message->user->isAdmin();
                        $senderName = $message->user?->name ?? ($isAgent ? 'Support Specialist' : 'Customer');
                        $avatarUrl = $message->user ? $message->user->getFirstMediaUrl('avatar') : null;
                    @endphp
                    <div class="message-item {{ $isAgent ? 'message-agent' : 'message-customer' }}">
                        <div class="d-flex align-items-start gap-3">
                            {{-- Avatar --}}
                            <div class="avatar-circle {{ $isAgent ? 'avatar-agent' : 'avatar-customer' }}" title="{{ $senderName }}">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $senderName }}">
                                @elseif($isAgent)
                                    <i class="bx bx-headphone font-size-18"></i>
                                @else
                                    {{ strtoupper(substr($senderName, 0, 1)) }}
                                @endif
                            </div>

                            {{-- Message Content --}}
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold font-size-14 text-dark">{{ $senderName }}</span>
                                        @if($isAgent)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-size-11 px-2 py-0">
                                                Support Team
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border font-size-11 px-2 py-0">
                                                You
                                            </span>
                                        @endif
                                    </div>
                                    <small class="text-muted font-size-12">
                                        {{ $message->created_at->format('M d, Y \a\t h:i A') }} ({{ $message->created_at->diffForHumans() }})
                                    </small>
                                </div>

                                {{-- Body --}}
                                <div class="message-bubble mt-2">
                                    {{ $message->content }}
                                </div>

                                {{-- Public Attachments --}}
                                @if($message->attachments && $message->attachments->count() > 0)
                                    <div class="mt-3 pt-2 border-top border-light-subtle">
                                        <span class="font-size-12 fw-semibold text-muted d-block mb-2">
                                            <i class="bx bx-paperclip me-1"></i> Attachments:
                                        </span>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($message->attachments as $attachment)
                                                @php
                                                    $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                                                    $icon = match($ext) {
                                                        'pdf' => 'bxs-file-pdf text-danger',
                                                        'jpg', 'jpeg', 'png', 'webp', 'gif' => 'bxs-file-image text-primary',
                                                        'doc', 'docx' => 'bxs-file-doc text-info',
                                                        'zip', 'rar' => 'bxs-file-archive text-warning',
                                                        default => 'bxs-file text-secondary',
                                                    };
                                                    $sizeKb = round($attachment->size / 1024);
                                                    $sizeFormatted = $sizeKb > 1024 ? round($sizeKb / 1024, 1) . ' MB' : $sizeKb . ' KB';
                                                @endphp
                                                <a href="{{ route('customer.tickets.attachments.show', [$ticket->id, $message->id, $attachment->id]) }}"
                                                   target="_blank"
                                                   class="attachment-card"
                                                   title="Click to view/download {{ $attachment->file_name }}">
                                                    <i class="bx {{ $icon }} font-size-18"></i>
                                                    <span class="fw-medium text-truncate" style="max-width: 200px;">{{ $attachment->file_name }}</span>
                                                    <span class="text-muted font-size-11">({{ $sizeFormatted }})</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        No messages recorded yet for this ticket.
                    </div>
                @endforelse
            </div>

            @if($messages->hasPages())
                <div class="p-3 bg-white border-top d-flex justify-content-end">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>

        {{-- Reply Form (if ticket is active/open) --}}
        @if($ticket->status !== 'closed')
            <div class="conversation-card p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bx bx-reply font-size-20 text-primary"></i>
                    <h6 class="fw-bold text-dark mb-0 font-size-15">Reply to Support Team</h6>
                </div>

                <form action="{{ route('customer.tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <textarea
                            name="content"
                            rows="4"
                            class="form-control form-control-modern @error('content') is-invalid @enderror"
                            placeholder="Type your message or answer here..."
                            minlength="2"
                            required
                        >{{ old('content') }}</textarea>
                        @error('content')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-size-13 fw-semibold text-dark mb-1">
                            <i class="bx bx-paperclip me-1 text-primary"></i> Attach Files <span class="text-muted fw-normal font-size-12">(Optional)</span>
                        </label>
                        <input
                            type="file"
                            name="attachments[]"
                            class="form-control form-control-sm @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                            multiple
                        >
                        <div class="font-size-11 text-muted mt-1">
                            Attach up to {{ config('ticket.attachments.max_files', 5) }} files (Max 10MB each).
                        </div>
                        @error('attachments')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                        @error('attachments.*')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 font-size-13 fw-semibold shadow-sm">
                            <i class="bx bx-paper-plane me-1"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="alert alert-secondary border rounded-3 p-3 text-center text-muted font-size-13">
                <i class="bx bx-lock-alt me-1"></i> This ticket is closed. If you require further assistance, please create a new ticket.
            </div>
        @endif
    </div>

    {{-- Right: Ticket Info & Meta Sidebar (4 cols) --}}
    <div class="col-lg-4">
        {{-- Ticket Metadata Details --}}
        <div class="sidebar-widget">
            <h6 class="fw-bold text-dark mb-3 font-size-15">
                <i class="bx bx-info-circle me-1 text-primary"></i> Ticket Information
            </h6>

            <div class="sidebar-row">
                <span class="text-muted">Ticket Number</span>
                <span class="fw-bold text-dark font-size-13">#{{ $ticket->ticket_number }}</span>
            </div>

            <div class="sidebar-row">
                <span class="text-muted">Status</span>
                <span class="status-pill-show status-{{ $ticket->status }}">
                    {{ str_replace('_', ' ', $ticket->status) }}
                </span>
            </div>

            <div class="sidebar-row">
                <span class="text-muted">Priority</span>
                <span class="text-capitalize fw-semibold text-dark">{{ $ticket->priority }}</span>
            </div>

            @if($ticket->department)
                <div class="sidebar-row">
                    <span class="text-muted">Department</span>
                    <span class="fw-medium text-dark">{{ $ticket->department }}</span>
                </div>
            @endif

            <div class="sidebar-row">
                <span class="text-muted">Submitted</span>
                <span class="text-dark">{{ $ticket->created_at->format('M d, Y') }}</span>
            </div>

            <div class="sidebar-row">
                <span class="text-muted">Last Activity</span>
                <span class="text-dark">{{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</span>
            </div>

            <div class="sidebar-row">
                <span class="text-muted">Assigned Specialist</span>
                <div class="d-flex align-items-center gap-2">
                    @if($ticket->assignedTo)
                        @php $agentAvatar = $ticket->assignedTo->getFirstMediaUrl('avatar'); @endphp
                        @if($agentAvatar)
                            <img src="{{ $agentAvatar }}" alt="{{ $ticket->assignedTo->name }}" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                        @else
                            <span class="avatar-circle avatar-agent" style="width: 24px; height: 24px; font-size: 11px;">
                                <i class="bx bx-headphone" style="font-size: 12px;"></i>
                            </span>
                        @endif
                        <span class="text-dark fw-medium">{{ $ticket->assignedTo->name }}</span>
                    @else
                        <span class="text-dark fw-medium">Support Team</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- SLA Tracking Card --}}
        <div class="sidebar-widget">
            <h6 class="fw-bold text-dark mb-3 font-size-15">
                <i class="bx bx-stopwatch me-1 text-primary"></i> SLA Guarantee
            </h6>

            <div class="sidebar-row">
                <span class="text-muted">Current SLA</span>
                <span class="badge {{ $slaBadgeStyle }} font-size-11">{{ $sla->label() }}</span>
            </div>

            @if($ticket->first_response_due_at)
                <div class="sidebar-row">
                    <span class="text-muted">First Response Due</span>
                    <span class="font-size-12 text-dark">{{ $ticket->first_response_due_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif

            @if($ticket->first_responded_at)
                <div class="sidebar-row">
                    <span class="text-muted">First Responded</span>
                    <span class="font-size-12 text-success fw-medium">{{ $ticket->first_responded_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif

            @if($ticket->resolution_due_at)
                <div class="sidebar-row">
                    <span class="text-muted">Resolution Due</span>
                    <span class="font-size-12 text-dark">{{ $ticket->resolution_due_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif

            @if($ticket->resolved_at)
                <div class="sidebar-row">
                    <span class="text-muted">Resolved Date</span>
                    <span class="font-size-12 text-success fw-medium">{{ $ticket->resolved_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif
        </div>

        {{-- Helpful Note Card --}}
        <div class="p-3 rounded-3 bg-white border border-light-subtle font-size-12 text-muted">
            <div class="d-flex align-items-center gap-2 mb-1 text-dark fw-semibold">
                <i class="bx bx-shield-check text-success font-size-16"></i>
                <span>Private & Secure</span>
            </div>
            <span>All messages and attachments exchanged here are confidential between you and the customer support team.</span>
        </div>
    </div>
</div>
@endsection

