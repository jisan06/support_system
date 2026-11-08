@extends('admin.layouts.master')
@section('title', 'Ticket #' . $ticket->ticket_number)

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
    @vite(['resources/css/media-picker.css'])
    <style>
        /* ==========================================================================
           Admin Ticket Conversation - Clean Compact Chat Layout
           ========================================================================== */
        .admin-chat-stream {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .admin-chat-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
        }
        .admin-chat-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 600;
            font-size: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.08);
            margin-top: 2px;
        }
        .admin-chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .admin-chat-content-wrap {
            flex: 1;
            min-width: 0;
        }
        .admin-chat-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 12px;
        }
        .admin-chat-bubble {
            display: inline-block;
            width: fit-content;
            max-width: 92%;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            line-height: 1.5;
            border: 1px solid #e9ecef;
        }
        .admin-chat-text {
            white-space: pre-line;
            word-break: break-word;
            margin-bottom: 0;
        }

        /* Message Type Accents */
        /* 1. Customer Message */
        .admin-msg-customer .admin-chat-bubble {
            background-color: #ffffff;
            border-color: #e2e8f0;
            color: #1e293b;
        }
        .admin-msg-customer .admin-chat-avatar {
            background-color: #e2e8f0;
            color: #475569;
        }

        /* 2. Staff Public Reply */
        .admin-msg-staff .admin-chat-bubble {
            background-color: #f0f7ff;
            border-color: #cce3ff;
            color: #1e293b;
        }
        .admin-msg-staff .admin-chat-avatar {
            background-color: #e0f2fe;
            color: #0284c7;
        }

        /* 3. Internal Staff Note */
        .admin-msg-internal .admin-chat-bubble {
            background-color: #fffdf0;
            border-color: #fef08a;
            color: #1e293b;
        }
        .admin-msg-internal .admin-chat-avatar {
            background-color: #fef9c3;
            color: #ca8a04;
        }

        /* Attachments in Chat */
        .admin-chat-attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
        .admin-attachment-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .admin-attachment-chip:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #2563eb;
        }
    </style>
@endpush

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
                                'waiting_for_customer' => 'bg-primary',
                                'resolved' => 'bg-success',
                                'closed' => 'bg-secondary',
                                default => 'bg-primary'
                            };
                        @endphp
                        @if(auth()->check() && auth()->user()->hasPermissionTo('edit_ticket'))
                            <form action="{{ route('tickets.update_status', $ticket->id) }}" method="POST" class="d-inline-flex align-items-center gap-1 mb-0">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-select form-select-sm py-0 px-2 font-size-11" style="width: auto; height: 26px;" onchange="this.form.submit()">
                                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="waiting_for_customer" {{ $ticket->status === 'waiting_for_customer' ? 'selected' : '' }}>Waiting for Customer</option>
                                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </form>
                        @else
                            <span class="badge {{ $statusBadge }} text-uppercase">{{ str_replace('_', ' ', $ticket->status) }}</span>
                        @endif
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
                        <div class="d-flex align-items-center gap-2">
                            @php
                                $custAvatar = $ticket->customer?->getFirstMediaUrl('avatar');
                            @endphp
                            @if($custAvatar)
                                <img src="{{ $custAvatar }}" alt="{{ $ticket->customer->name }}" class="rounded-circle" style="width: 22px; height: 22px; object-fit: cover;">
                            @endif
                            <span class="fw-semibold">{{ $ticket->customer?->name ?? 'Unknown' }}</span>
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Customer Email</span>
                        <span>{{ $ticket->customer?->email ?? '-' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Assigned To</span>
                        <div class="d-flex align-items-center gap-2">
                            @if($ticket->assignedTo)
                                @php
                                    $assigneeAvatar = $ticket->assignedTo->getFirstMediaUrl('avatar');
                                @endphp
                                @if($assigneeAvatar)
                                    <img src="{{ $assigneeAvatar }}" alt="{{ $ticket->assignedTo->name }}" class="rounded-circle" style="width: 22px; height: 22px; object-fit: cover;">
                                @endif
                                <span>{{ $ticket->assignedTo->name }}</span>
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Created</span>
                        <span>{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">SLA Status</span>
                        <span class="badge {{ $ticket->sla_status->badgeClass() }}">{{ $ticket->sla_status->label() }}</span>
                    </li>
                    @if($ticket->escalation_level > 0)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Escalation Level</span>
                            <span class="badge bg-danger">Level {{ $ticket->escalation_level }}</span>
                        </li>
                    @endif
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">First Response</span>
                            <span class="badge {{ $ticket->first_response_sla_status->badgeClass() }}">{{ $ticket->first_response_sla_status->label() }}</span>
                        </div>
                        <div class="font-size-12 text-muted">
                            @if($ticket->first_responded_at)
                                <i class="bx bx-check me-1 text-success"></i> Responded {{ $ticket->first_responded_at->format('M d, Y h:i A') }}
                            @elseif($ticket->first_response_due_at)
                                <i class="bx bx-time-five me-1"></i> Due {{ $ticket->first_response_due_at->format('M d, Y h:i A') }}
                            @else
                                <span>No target</span>
                            @endif
                        </div>
                    </li>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Resolution</span>
                            <span class="badge {{ $ticket->resolution_sla_status->badgeClass() }}">{{ $ticket->resolution_sla_status->label() }}</span>
                        </div>
                        <div class="font-size-12 text-muted">
                            @if($ticket->resolved_at)
                                <i class="bx bx-check me-1 text-success"></i> Resolved {{ $ticket->resolved_at->format('M d, Y h:i A') }}
                            @elseif($ticket->resolution_due_at)
                                <i class="bx bx-time-five me-1"></i> Due {{ $ticket->resolution_due_at->format('M d, Y h:i A') }}
                            @else
                                <span>No target</span>
                            @endif
                        </div>
                    </li>
                </ul>

                @if($ticket->escalations && $ticket->escalations->isNotEmpty())
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="font-size-13 text-muted mb-2"><i class="bx bx-error-circle text-danger me-1"></i> Escalation History</h6>
                        <ul class="list-unstyled mb-0 font-size-12">
                            @foreach($ticket->escalations as $esc)
                                <li class="mb-2 pb-1 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-semibold text-danger">Level {{ $esc->level }}</span>
                                        <small class="text-muted">{{ $esc->created_at->format('M d, H:i') }}</small>
                                    </div>
                                    <div class="text-muted">{{ ucwords(str_replace('_', ' ', $esc->trigger_reason)) }}</div>
                                    @if($esc->new_assigned_to && $esc->new_assigned_to !== $esc->previous_assigned_to)
                                        <div class="text-primary font-size-11">Reassigned to {{ $esc->newAssignee?->name }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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

        {{-- Conversation History Card --}}
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0 font-size-14"><i class="bx bx-conversation me-1 text-primary"></i> Conversation History</h5>
                <span class="badge bg-secondary font-size-11">{{ $messages->total() }} {{ \Illuminate\Support\Str::plural('message', $messages->total()) }}</span>
            </div>
            <div class="card-body p-3" style="background: #fafbfc;">
                <div class="admin-chat-stream">
                    @forelse($messages as $msg)
                        @php
                            $isInternal = $msg->isInternalNote();
                            $isStaffSender = $msg->user?->isAdmin();
                            $avatarUrl = $msg->user ? $msg->user->getFirstMediaUrl('avatar') : null;
                            $senderName = $msg->user?->name ?? 'System';
                            $msgTypeClass = $isInternal ? 'admin-msg-internal' : ($isStaffSender ? 'admin-msg-staff' : 'admin-msg-customer');
                        @endphp

                        <div class="admin-chat-item {{ $msgTypeClass }}">
                            {{-- Avatar --}}
                            <div class="admin-chat-avatar" title="{{ $senderName }}">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $senderName }}">
                                @elseif($isInternal)
                                    <i class="bx bx-lock-alt font-size-15"></i>
                                @elseif($isStaffSender)
                                    <i class="bx bx-user-check font-size-15"></i>
                                @else
                                    {{ strtoupper(substr($senderName, 0, 1)) }}
                                @endif
                            </div>

                            {{-- Message Content Wrap --}}
                            <div class="admin-chat-content-wrap">
                                {{-- Meta Line: Sender, Badge, Timestamp --}}
                                <div class="admin-chat-meta">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fw-bold font-size-13 text-dark">{{ $senderName }}</span>
                                        @if($isInternal)
                                            <span class="badge bg-warning text-dark font-size-11">
                                                <i class="bx bx-lock-alt me-1"></i>Internal Note
                                            </span>
                                        @elseif($isStaffSender)
                                            <span class="badge bg-primary text-white font-size-11">
                                                Staff
                                            </span>
                                        @else
                                            <span class="badge bg-success text-white font-size-11">
                                                Customer
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-muted font-size-11">
                                        <i class="bx bx-time-five me-1"></i>{{ $msg->created_at->format('M d, Y h:i A') }}
                                        <small>({{ $msg->created_at->diffForHumans() }})</small>
                                    </span>
                                </div>

                                {{-- Compact Chat Bubble --}}
                                <div class="admin-chat-bubble">
                                    @if($isInternal)
                                        <div class="small fw-semibold text-warning-emphasis mb-1 font-size-11">
                                            <i class="bx bx-shield-quarter me-1"></i>Private internal note &mdash; staff only
                                        </div>
                                    @endif

                                    <p class="admin-chat-text">{{ trim($msg->content) }}</p>

                                    {{-- Attachments Section --}}
                                    @if($msg->media->isNotEmpty())
                                        <div class="admin-chat-attachments">
                                            @foreach($msg->media as $media)
                                                @php
                                                    $formattedSize = $media->size >= 1048576
                                                        ? round($media->size / 1048576, 2) . ' MB'
                                                        : round($media->size / 1024, 1) . ' KB';
                                                    $isImage = str_starts_with($media->mime_type, 'image/');
                                                    $downloadUrl = route('tickets.attachments.download', [$ticket->id, $msg->id, $media->id]);
                                                @endphp
                                                <a href="{{ $downloadUrl }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="admin-attachment-chip"
                                                   title="Click to view/download {{ $media->file_name }}">
                                                    @if($isImage)
                                                        <i class="bx bxs-file-image text-primary font-size-14"></i>
                                                    @else
                                                        <i class="bx bx-file text-secondary font-size-14"></i>
                                                    @endif
                                                    <span class="text-truncate" style="max-width: 180px;">{{ $media->file_name }}</span>
                                                    <small class="text-muted">({{ $formattedSize }})</small>
                                                    <i class="bx bx-download text-muted ms-1 font-size-12"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-chat display-4 d-block mb-2 text-muted"></i>
                            No conversation messages yet.
                        </div>
                    @endforelse
                </div>

                @if($messages->hasPages())
                    <div class="d-flex justify-content-center mt-3 pt-2 border-top">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Reply & Internal Note Composer Card --}}
        <div class="card shadow-sm border-light">
            <div class="card-header bg-light py-2">
                <ul class="nav nav-tabs card-header-tabs" id="replyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-size-13 py-2 px-3 fw-medium" id="public-reply-tab" data-bs-toggle="tab" data-bs-target="#public-reply" type="button" role="tab" aria-controls="public-reply" aria-selected="true">
                            <i class="bx bx-reply me-1"></i> Public Reply
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-warning-emphasis font-size-13 py-2 px-3 fw-medium" id="internal-note-tab" data-bs-toggle="tab" data-bs-target="#internal-note" type="button" role="tab" aria-controls="internal-note" aria-selected="false">
                            <i class="bx bx-lock-alt me-1"></i> Internal Note
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-3">
                <div class="tab-content" id="replyTabsContent">
                    {{-- Public Reply Form --}}
                    <div class="tab-pane fade show active" id="public-reply" role="tabpanel" aria-labelledby="public-reply-tab">
                        <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <p class="text-muted font-size-12 mb-2">
                                <i class="bx bx-globe me-1 text-primary"></i> Public response visible to the customer.
                            </p>
                            <div class="mb-3">
                                <textarea name="content" rows="3" class="form-control font-size-13" placeholder="Write your public reply here..." required minlength="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm font-size-12" id="replyMediaPickBtn">
                                        <i class="bx bx-paperclip me-1"></i> Attach Media
                                    </button>
                                    <span class="text-muted font-size-12" id="replyAttachmentCountText">
                                        0 of {{ config('ticket.attachments.max_files', 5) }} selected
                                    </span>
                                </div>
                                <div id="replyAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                    <i class="bx bx-send me-1"></i> Send Public Reply
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Internal Note Form --}}
                    <div class="tab-pane fade" id="internal-note" role="tabpanel" aria-labelledby="internal-note-tab">
                        <form action="{{ route('tickets.internal_note', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="alert alert-warning py-1 px-3 mb-2 font-size-12">
                                <i class="bx bx-lock-alt me-1"></i> <strong>Confidential:</strong> Visible strictly to internal staff. Customers cannot view this.
                            </div>
                            <div class="mb-3">
                                <textarea name="content" rows="3" class="form-control font-size-13 border-warning bg-warning bg-opacity-10" placeholder="Write internal note for staff..." required minlength="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm text-dark font-size-12" id="noteMediaPickBtn">
                                        <i class="bx bx-paperclip me-1"></i> Attach Media
                                    </button>
                                    <span class="text-muted font-size-12" id="noteAttachmentCountText">
                                        0 of {{ config('ticket.attachments.max_files', 5) }} selected
                                    </span>
                                </div>
                                <div id="noteAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning btn-sm text-dark fw-semibold px-4">
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
@include('components.media.picker-modal')
@endsection

@push('script_links')
<script src="{{ asset('backend/assets/js/media-picker.js') }}"></script>
<script>
$(function () {
    var maxFiles = {{ config('ticket.attachments.max_files', 5) }};

    function setupMediaPicker(btnId, listId, countId) {
        var selectedIds = new Set();

        $('#' + btnId).on('click', function () {
            if (selectedIds.size >= maxFiles) {
                toastr.warning('Maximum ' + maxFiles + ' attachments allowed.');
                return;
            }

            MediaPicker.open(function (media) {
                if (!media || !media.id) return;
                if (selectedIds.has(media.id)) {
                    toastr.info('This item is already attached.');
                    return;
                }
                if (selectedIds.size >= maxFiles) {
                    toastr.warning('Maximum ' + maxFiles + ' attachments allowed.');
                    return;
                }

                selectedIds.add(media.id);
                $('#' + countId).text(selectedIds.size + ' of ' + maxFiles + ' selected');

                var sizeText = media.size ? formatFileSize(media.size) : '';
                var previewThumb = media.url
                    ? '<img src="' + media.url + '" alt="' + escapeHtml(media.file_name) + '" style="width:100%;height:100%;object-fit:cover;">'
                    : '<i class="fas fa-file text-muted font-size-18"></i>';

                var itemHtml = `
                    <div class="selected-media-item d-flex align-items-center p-2 border rounded bg-white shadow-sm" data-id="${media.id}" style="width: calc(50% - 6px); min-width: 220px;">
                        <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded" style="width: 38px; height: 38px; overflow: hidden; flex-shrink: 0;">
                            ${previewThumb}
                        </div>
                        <div class="flex-grow-1 text-truncate me-2">
                            <div class="font-size-12 text-truncate fw-medium" title="${escapeHtml(media.file_name)}">${escapeHtml(media.file_name)}</div>
                            <small class="text-muted font-size-10">${sizeText}</small>
                        </div>
                        <input type="hidden" name="media_ids[]" value="${media.id}">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-item-btn" title="Remove attachment">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                $('#' + listId).append(itemHtml);
            });
        });

        $('#' + listId).on('click', '.remove-item-btn', function () {
            var item = $(this).closest('.selected-media-item');
            var id = item.data('id');
            selectedIds.delete(id);
            item.remove();
            $('#' + countId).text(selectedIds.size + ' of ' + maxFiles + ' selected');
        });
    }

    setupMediaPicker('replyMediaPickBtn', 'replyAttachmentsList', 'replyAttachmentCountText');
    setupMediaPicker('noteMediaPickBtn', 'noteAttachmentsList', 'noteAttachmentCountText');

    function formatFileSize(bytes) {
        if (!bytes) return '';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
});
</script>
@endpush
