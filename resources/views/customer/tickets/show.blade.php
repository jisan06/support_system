@extends('customer.layouts.master')
@section('title', 'Ticket #' . $ticket->ticket_number)

@push('styles')
<style>
    /* ==========================================================================
       Customer Ticket Details - Modern Executive UI
       ========================================================================== */

    /* Header Banner */
    .ticket-header-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 20px 24px;
        box-shadow: 0 2px 12px rgba(18, 38, 63, 0.03);
        margin-bottom: 20px;
    }

    /* Unified Chat Container */
    .chat-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(18, 38, 63, 0.03);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .chat-card-header {
        padding: 14px 20px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-card-body {
        padding: 20px;
        background-color: #f8fafc;
        min-height: 380px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    /* Message Rows */
    .chat-row {
        display: flex;
        width: 100%;
        margin-bottom: 0;
    }
    .chat-row-agent {
        flex-direction: row;
        justify-content: flex-start;
        align-items: flex-start;
    }
    .chat-row-customer {
        flex-direction: row-reverse;
        justify-content: flex-start;
        align-items: flex-start;
    }

    /* Avatars */
    .chat-avatar-wrap {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
        overflow: hidden;
        border: 1.5px solid #ffffff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }
    .chat-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .chat-avatar-agent {
        background: #e0f2fe;
        color: #0284c7;
        margin-right: 10px;
    }
    .chat-avatar-customer {
        background: #2563eb;
        color: #ffffff;
        margin-left: 10px;
    }

    /* Bubble Wrappers */
    .chat-bubble-col {
        max-width: 72%;
        display: flex;
        flex-direction: column;
    }
    @media (max-width: 768px) {
        .chat-bubble-col {
            max-width: 86%;
        }
    }
    .chat-row-agent .chat-bubble-col {
        align-items: flex-start;
    }
    .chat-row-customer .chat-bubble-col {
        align-items: flex-end;
    }

    /* Metadata Lines */
    .chat-meta-bar {
        font-size: 11.5px;
        color: #64748b;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .chat-row-customer .chat-meta-bar {
        flex-direction: row-reverse;
    }

    /* Chat Bubbles - Fit Content & Compact */
    .chat-bubble-box {
        display: inline-block;
        width: fit-content;
        max-width: 100%;
        padding: 10px 15px;
        font-size: 13.5px;
        line-height: 1.55;
        position: relative;
    }
    .chat-message-text {
        white-space: pre-line;
        word-break: break-word;
        margin-bottom: 0;
    }

    /* Agent Bubble (Left) */
    .chat-bubble-agent {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-radius: 4px 14px 14px 14px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
    }

    /* Customer Bubble (Right) */
    .chat-bubble-customer {
        background: #2563eb;
        color: #ffffff;
        border-radius: 14px 4px 14px 14px;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.18);
    }

    /* Attachments Chips */
    .chat-attachment-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 8px;
        padding-top: 8px;
    }
    .chat-bubble-agent .chat-attachment-list {
        border-top: 1px solid #f1f5f9;
    }
    .chat-bubble-customer .chat-attachment-list {
        border-top: 1px solid rgba(255, 255, 255, 0.22);
    }

    .attachment-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.15s ease;
        max-width: 100%;
    }
    .chat-bubble-agent .attachment-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #1e293b;
    }
    .chat-bubble-agent .attachment-pill:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .chat-bubble-customer .attachment-pill {
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #ffffff;
    }
    .chat-bubble-customer .attachment-pill:hover {
        background: rgba(255, 255, 255, 0.26);
        color: #ffffff;
    }

    /* Integrated Reply Composer */
    .chat-composer-box {
        background: #ffffff;
        border-top: 1px solid #f1f5f9;
        padding: 16px 20px;
    }
    .chat-textarea-modern {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #1e293b;
        transition: all 0.2s ease;
        resize: vertical;
        min-height: 72px;
    }
    .chat-textarea-modern:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        outline: none;
    }

    /* Sidebar Widgets */
    .sidebar-card-clean {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 2px 12px rgba(18, 38, 63, 0.03);
        margin-bottom: 18px;
    }
    .sidebar-card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        font-size: 14px;
        color: #0f172a;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .sidebar-prop-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 0;
        font-size: 12.5px;
    }
    .sidebar-prop-row:not(:last-child) {
        border-bottom: 1px solid #f8fafc;
    }
    .sidebar-prop-label {
        color: #64748b;
        font-weight: 500;
    }
    .sidebar-prop-val {
        color: #1e293b;
        font-weight: 600;
        text-align: right;
    }

    /* Status Pills */
    .pill-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .pill-open { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .pill-in_progress { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .pill-resolved { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .pill-closed { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    .priority-dot-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
{{-- Modern Executive Header Banner --}}
<div class="ticket-header-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 font-size-12">
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
        <a href="{{ route('customer.tickets.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 font-size-12">
            <i class="bx bx-arrow-back me-1"></i> Back to My Tickets
        </a>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge bg-light text-dark border font-size-12 px-2 py-1 font-monospace">
                    #{{ $ticket->ticket_number }}
                </span>
                <span class="pill-badge pill-{{ $ticket->status }}">
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
                <span class="priority-dot-badge">
                    <span class="d-inline-block rounded-circle {{ $dotColor }}" style="width: 6px; height: 6px;"></span>
                    <span class="text-capitalize">{{ $ticket->priority }}</span>
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
    {{-- Left: Modern Chat Stream (8 cols) --}}
    <div class="col-lg-8">
        <div class="chat-card">
            {{-- Chat Card Header --}}
            <div class="chat-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-conversation font-size-18 text-primary"></i>
                    <span class="fw-bold text-dark font-size-14">Conversation Stream</span>
                </div>
                <span class="badge bg-light text-muted border font-size-11">
                    {{ $messages->total() }} {{ \Illuminate\Support\Str::plural('message', $messages->total()) }}
                </span>
            </div>

            {{-- Chat Messages Area --}}
            <div class="chat-card-body">
                @forelse($messages as $message)
                    @php
                        $isAgent = $message->user && $message->user->isAdmin();
                        $senderName = $message->user?->name ?? ($isAgent ? 'Support Specialist' : 'Customer');
                        $avatarUrl = $message->user ? $message->user->getFirstMediaUrl('avatar') : null;
                    @endphp

                    <div class="chat-row {{ $isAgent ? 'chat-row-agent' : 'chat-row-customer' }}">
                        {{-- Avatar --}}
                        <div class="chat-avatar-wrap {{ $isAgent ? 'chat-avatar-agent' : 'chat-avatar-customer' }}" title="{{ $senderName }}">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $senderName }}">
                            @elseif($isAgent)
                                <i class="bx bx-headphone font-size-16"></i>
                            @else
                                {{ strtoupper(substr($senderName, 0, 1)) }}
                            @endif
                        </div>

                        {{-- Bubble & Meta Container --}}
                        <div class="chat-bubble-col">
                            {{-- Meta Line (Sender & Timestamp) --}}
                            <div class="chat-meta-bar">
                                <span class="fw-semibold text-dark">{{ $isAgent ? $senderName : 'You' }}</span>
                                @if($isAgent)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-size-10 px-1 py-0">
                                        Support Team
                                    </span>
                                @endif
                                <span>&bull; {{ $message->created_at->format('h:i A') }} ({{ $message->created_at->diffForHumans() }})</span>
                            </div>

                            {{-- Compact Chat Bubble (Tightly Wraps Content) --}}
                            <div class="chat-bubble-box {{ $isAgent ? 'chat-bubble-agent' : 'chat-bubble-customer' }}">
                                <p class="chat-message-text">{{ trim($message->content) }}</p>

                                {{-- Attachments --}}
                                @if($message->attachments && $message->attachments->count() > 0)
                                    <div class="chat-attachment-list">
                                        @foreach($message->attachments as $attachment)
                                            @php
                                                $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                                                $icon = match($ext) {
                                                    'pdf' => 'bxs-file-pdf text-danger',
                                                    'jpg', 'jpeg', 'png', 'webp', 'gif' => 'bxs-file-image',
                                                    'doc', 'docx' => 'bxs-file-doc',
                                                    'zip', 'rar' => 'bxs-file-archive',
                                                    default => 'bxs-file',
                                                };
                                                $sizeKb = round($attachment->size / 1024);
                                                $sizeFormatted = $sizeKb > 1024 ? round($sizeKb / 1024, 1) . ' MB' : $sizeKb . ' KB';
                                            @endphp
                                            <a href="{{ route('customer.tickets.attachments.show', [$ticket->id, $message->id, $attachment->id]) }}"
                                               target="_blank"
                                               class="attachment-pill"
                                               title="View/Download {{ $attachment->file_name }}">
                                                <i class="bx {{ $icon }} font-size-15"></i>
                                                <span class="text-truncate" style="max-width: 180px;">{{ $attachment->file_name }}</span>
                                                <span style="opacity: 0.8; font-size: 11px;">({{ $sizeFormatted }})</span>
                                                <i class="bx bx-download ms-1 opacity-75 font-size-13"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-message-rounded-dots font-size-36 text-muted mb-2"></i>
                        <p class="mb-0 font-size-14">No messages recorded yet for this ticket.</p>
                    </div>
                @endforelse

                {{-- Pagination Links --}}
                @if($messages->hasPages())
                    <div class="pt-2 d-flex justify-content-center">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>

            {{-- Integrated Composer Box --}}
            @if($ticket->status !== 'closed')
                <div class="chat-composer-box">
                    <form action="{{ route('customer.tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-2">
                            <textarea
                                name="content"
                                rows="3"
                                class="form-control chat-textarea-modern @error('content') is-invalid @enderror"
                                placeholder="Write your reply to the support team..."
                                minlength="2"
                                required
                            >{{ old('content') }}</textarea>
                            @error('content')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-1">
                            {{-- Attachment Picker --}}
                            <div class="d-flex align-items-center">
                                <label for="replyAttachments" class="btn btn-sm btn-light border rounded-pill px-3 py-1 cursor-pointer font-size-12 mb-0">
                                    <i class="bx bx-paperclip me-1 text-primary"></i> Attach files
                                </label>
                                <input
                                    type="file"
                                    name="attachments[]"
                                    id="replyAttachments"
                                    class="d-none @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                                    multiple
                                    onchange="updateAttachmentLabel(this)"
                                >
                                <span id="replyFileCount" class="text-muted font-size-12 ms-2"></span>
                            </div>

                            {{-- Send Button --}}
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 font-size-13 fw-semibold shadow-sm">
                                <i class="bx bx-paper-plane me-1"></i> Send Reply
                            </button>
                        </div>

                        @error('attachments')
                            <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                        @enderror
                        @error('attachments.*')
                            <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                        @enderror
                    </form>
                </div>
            @else
                <div class="chat-composer-box bg-light text-center py-3 text-muted font-size-13">
                    <i class="bx bx-lock-alt me-1"></i> This ticket is closed. If you require further assistance, please submit a new ticket.
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Separate Sidebar Sections (4 cols) --}}
    <div class="col-lg-4">
        {{-- Section 1: Ticket Information --}}
        <div class="sidebar-card-clean">
            <div class="sidebar-card-header">
                <i class="bx bx-info-circle text-primary font-size-17"></i>
                <span>Ticket Information</span>
            </div>

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Ticket Number</span>
                <span class="sidebar-prop-val font-monospace">#{{ $ticket->ticket_number }}</span>
            </div>

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Status</span>
                <span class="sidebar-prop-val">
                    <span class="pill-badge pill-{{ $ticket->status }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>
                </span>
            </div>

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Priority</span>
                <span class="sidebar-prop-val text-capitalize">{{ $ticket->priority }}</span>
            </div>

            @if($ticket->department)
                <div class="sidebar-row sidebar-prop-row">
                    <span class="sidebar-prop-label">Department</span>
                    <span class="sidebar-prop-val">{{ $ticket->department }}</span>
                </div>
            @endif

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Submitted</span>
                <span class="sidebar-prop-val">{{ $ticket->created_at->format('M d, Y') }}</span>
            </div>

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Last Activity</span>
                <span class="sidebar-prop-val">{{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</span>
            </div>

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Assigned Specialist</span>
                <div class="d-flex align-items-center gap-2">
                    @if($ticket->assignedTo)
                        @php $agentAvatar = $ticket->assignedTo->getFirstMediaUrl('avatar'); @endphp
                        @if($agentAvatar)
                            <img src="{{ $agentAvatar }}" alt="{{ $ticket->assignedTo->name }}" class="rounded-circle" style="width: 22px; height: 22px; object-fit: cover;">
                        @else
                            <span class="chat-avatar-wrap chat-avatar-agent" style="width: 22px; height: 22px; font-size: 10px; margin-right: 0;">
                                <i class="bx bx-headphone" style="font-size: 11px;"></i>
                            </span>
                        @endif
                        <span class="sidebar-prop-val">{{ $ticket->assignedTo->name }}</span>
                    @else
                        <span class="sidebar-prop-val text-muted">Support Team</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Section 2: SLA Guarantee --}}
        <div class="sidebar-card-clean">
            <div class="sidebar-card-header">
                <i class="bx bx-stopwatch text-primary font-size-17"></i>
                <span>SLA Guarantee</span>
            </div>

            <div class="sidebar-prop-row">
                <span class="sidebar-prop-label">Current SLA</span>
                <span class="badge {{ $slaBadgeStyle }} font-size-11">{{ $sla->label() }}</span>
            </div>

            @if($ticket->first_response_due_at)
                <div class="sidebar-prop-row">
                    <span class="sidebar-prop-label">First Response Due</span>
                    <span class="sidebar-prop-val font-size-12">{{ $ticket->first_response_due_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif

            @if($ticket->first_responded_at)
                <div class="sidebar-prop-row">
                    <span class="sidebar-prop-label">First Responded</span>
                    <span class="sidebar-prop-val font-size-12 text-success">{{ $ticket->first_responded_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif

            @if($ticket->resolution_due_at)
                <div class="sidebar-prop-row">
                    <span class="sidebar-prop-label">Resolution Due</span>
                    <span class="sidebar-prop-val font-size-12">{{ $ticket->resolution_due_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif

            @if($ticket->resolved_at)
                <div class="sidebar-prop-row">
                    <span class="sidebar-prop-label">Resolved Date</span>
                    <span class="sidebar-prop-val font-size-12 text-success">{{ $ticket->resolved_at->format('M d, Y h:i A') }}</span>
                </div>
            @endif
        </div>

        {{-- Section 3: Confidentiality Reassurance --}}
        <div class="p-3 rounded-3 bg-white border border-light-subtle font-size-12 text-muted d-flex align-items-center gap-2">
            <i class="bx bx-shield-quarter text-success font-size-20 flex-shrink-0"></i>
            <span>All messages and attachments are private and encrypted between you and customer support.</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateAttachmentLabel(input) {
        const countSpan = document.getElementById('replyFileCount');
        if (input.files && input.files.length > 0) {
            countSpan.textContent = input.files.length + (input.files.length === 1 ? ' file selected' : ' files selected');
        } else {
            countSpan.textContent = '';
        }
    }
</script>
@endpush
