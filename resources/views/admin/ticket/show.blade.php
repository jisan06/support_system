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

                            {{-- Attachments Section --}}
                            @if($msg->media->isNotEmpty())
                                <div class="mt-3 pt-2 border-top {{ $isInternal ? 'border-warning border-opacity-25' : 'border-light-subtle' }}">
                                    <div class="text-muted small fw-semibold mb-2">
                                        <i class="bx bx-paperclip me-1"></i> Attachments ({{ $msg->media->count() }}):
                                        @if($isInternal)
                                            <span class="badge bg-warning text-dark font-size-11 ms-1">
                                                <i class="bx bx-lock-alt me-1"></i> Internal Only
                                            </span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($msg->media as $media)
                                            @php
                                                $formattedSize = $media->size >= 1048576
                                                    ? round($media->size / 1048576, 2) . ' MB'
                                                    : round($media->size / 1024, 1) . ' KB';
                                                $isImage = str_starts_with($media->mime_type, 'image/');
                                                $downloadUrl = route('tickets.attachments.download', [$ticket->id, $msg->id, $media->id]);
                                            @endphp
                                            @if($isImage)
                                                <a href="{{ $downloadUrl }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="attachment-image-card text-decoration-none border rounded p-1 d-inline-flex flex-column align-items-center bg-white shadow-sm {{ $isInternal ? 'border-warning' : 'border-light-subtle' }}"
                                                   style="width: 140px;"
                                                   title="Click to view {{ $media->file_name }} in new tab">
                                                    <div class="attachment-thumb rounded bg-light d-flex align-items-center justify-content-center overflow-hidden mb-1" style="width: 100%; height: 90px;">
                                                        <img src="{{ $downloadUrl }}" alt="{{ $media->file_name }}" style="max-width: 100%; max-height: 100%; object-fit: cover;" loading="lazy">
                                                    </div>
                                                    <div class="w-100 text-truncate font-size-12 px-1 text-dark fw-medium text-center" title="{{ $media->file_name }}">
                                                        {{ $media->file_name }}
                                                    </div>
                                                    <small class="text-muted font-size-10">{{ $formattedSize }} <i class="bx bx-link-external ms-1 text-primary"></i></small>
                                                </a>
                                            @else
                                                <a href="{{ $downloadUrl }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="btn btn-sm {{ $isInternal ? 'btn-outline-warning text-dark' : 'btn-outline-secondary' }} d-inline-flex align-items-center"
                                                   title="Download {{ $media->file_name }}">
                                                    <i class="bx bx-file me-1"></i>
                                                    <span class="text-truncate" style="max-width: 220px;">{{ $media->file_name }}</span>
                                                    <span class="badge bg-light text-muted ms-2 font-size-11">{{ $formattedSize }}</span>
                                                    <i class="bx bx-download ms-2 text-primary font-size-14"></i>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
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
                        <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <p class="text-muted small mb-2">
                                <i class="bx bx-globe me-1"></i> This message will be sent as a public response and visible to the customer.
                            </p>
                            <div class="mb-3">
                                <textarea name="content" rows="4" class="form-control" placeholder="Write your public reply here..." required minlength="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small d-block">
                                    <i class="bx bx-paperclip me-1"></i> Attachments <span class="text-muted font-size-11">(Max {{ config('ticket.attachments.max_files', 5) }} files)</span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="replyMediaPickBtn">
                                        <i class="bx bx-image-add me-1"></i> Choose from Media Library
                                    </button>
                                    <span class="text-muted font-size-12" id="replyAttachmentCountText">
                                        0 of {{ config('ticket.attachments.max_files', 5) }} selected
                                    </span>
                                </div>
                                <div id="replyAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
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
                        <form action="{{ route('tickets.internal_note', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="alert alert-warning py-2 mb-3 font-size-13">
                                <i class="bx bx-lock-alt me-1"></i> <strong>Confidential:</strong> Internal notes and their attachments are strictly visible to staff/agents. Customers will never see them.
                            </div>
                            <div class="mb-3">
                                <textarea name="content" rows="4" class="form-control bg-warning bg-opacity-10 border-warning" placeholder="Write your internal staff note here..." required minlength="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small d-block">
                                    <i class="bx bx-paperclip me-1"></i> Note Attachments <span class="badge bg-warning text-dark font-size-11 ms-1"><i class="bx bx-lock-alt"></i> Internal Only</span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-warning btn-sm text-dark" id="noteMediaPickBtn">
                                        <i class="bx bx-image-add me-1"></i> Choose from Media Library
                                    </button>
                                    <span class="text-muted font-size-12" id="noteAttachmentCountText">
                                        0 of {{ config('ticket.attachments.max_files', 5) }} selected
                                    </span>
                                </div>
                                <div id="noteAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                                <div class="form-text text-muted font-size-12 mt-1">
                                    Attachments added here remain strictly confidential and internal to staff.
                                </div>
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
@include('components.media.picker-modal')
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
    @vite(['resources/css/media-picker.css'])
@endpush

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
