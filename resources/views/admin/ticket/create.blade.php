@extends('admin.layouts.master')
@section('title', __('permission.create_ticket'))

@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    {{ __('permission.create_ticket') }}
                </div>
                <div class="card-body">
                    @include('components.message.error')

                    <div class="row">
                        {{-- Customer Selection --}}
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="customer_id">
                                    Customer <span class="text-danger">*</span>
                                </label>
                                <select name="customer_id" id="customer_id" class="form-control select2 @error('customer_id') is-invalid @enderror" required>
                                    <option value="">-- Select Customer --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Agent Assignment --}}
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="assigned_to">
                                    Assign Agent <span class="text-muted font-size-12">(Optional)</span>
                                </label>
                                <select name="assigned_to" id="assigned_to" class="form-control select2 @error('assigned_to') is-invalid @enderror">
                                    <option value="">-- Unassigned --</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ old('assigned_to') == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->name }} ({{ $agent->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_to')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Subject --}}
                        <div class="col-12 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="subject">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    name="subject"
                                    id="subject"
                                    value="{{ old('subject') }}"
                                    class="form-control @error('subject') is-invalid @enderror"
                                    placeholder="Brief summary of the issue..."
                                    maxlength="255"
                                    required
                                >
                                @error('subject')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Category / Department --}}
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="department">
                                    Category / Department <span class="text-muted font-size-12">(Optional)</span>
                                </label>
                                <input type="text"
                                    name="department"
                                    id="department"
                                    value="{{ old('department') }}"
                                    class="form-control @error('department') is-invalid @enderror"
                                    placeholder="e.g. Billing, Technical Support, Account..."
                                    maxlength="100"
                                >
                                @error('department')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Priority --}}
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="priority">
                                    Priority <span class="text-danger">*</span>
                                </label>
                                <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror" required>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                @error('priority')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Description / Initial Message --}}
                        <div class="col-12 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="description">
                                    Description <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    name="description"
                                    id="description"
                                    rows="5"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Describe the issue or initial message..."
                                    minlength="2"
                                    required
                                >{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Attachments via Media Library --}}
                        <div class="col-12 mb-3">
                            <div class="form-group">
                                <label class="form-label d-block">
                                    <i class="fas fa-paperclip me-1"></i> Attachments
                                    <span class="text-muted font-size-11">(Max {{ config('ticket.attachments.max_files', 5) }} files)</span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="ticketMediaPickBtn">
                                        <i class="bx bx-image-add me-1"></i> Choose from Media Library
                                    </button>
                                    <span class="text-muted font-size-12" id="attachmentCountText">
                                        0 of {{ config('ticket.attachments.max_files', 5) }} selected
                                    </span>
                                </div>

                                {{-- Selected Media Container --}}
                                <div id="selectedAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>

                                @error('media_ids')
                                    <span class="error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                @error('media_ids.*')
                                    <span class="error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-times" aria-hidden="true"></i>
                        @lang('common.cancel')
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        @lang('common.submit_button')
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('components.media.picker-modal')
@endsection

@include('admin.layouts.include.others.select2')

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
    @vite(['resources/css/media-picker.css'])
@endpush

@push('script_links')
<script src="{{ asset('backend/assets/js/media-picker.js') }}"></script>
<script>
$(function () {
    var maxFiles = {{ config('ticket.attachments.max_files', 5) }};
    var selectedMediaIds = new Set();

    $('#ticketMediaPickBtn').on('click', function () {
        if (selectedMediaIds.size >= maxFiles) {
            toastr.warning('Maximum ' + maxFiles + ' attachments allowed.');
            return;
        }

        MediaPicker.open(function (media) {
            if (!media || !media.id) return;

            if (selectedMediaIds.has(media.id)) {
                toastr.info('This item is already attached.');
                return;
            }

            if (selectedMediaIds.size >= maxFiles) {
                toastr.warning('Maximum ' + maxFiles + ' attachments allowed.');
                return;
            }

            selectedMediaIds.add(media.id);
            updateAttachmentCount();

            var sizeText = media.size ? formatFileSize(media.size) : '';
            var previewThumb = media.url
                ? '<img src="' + media.url + '" alt="' + escapeHtml(media.file_name) + '" style="width:100%;height:100%;object-fit:cover;">'
                : '<i class="fas fa-file text-muted font-size-18"></i>';

            var itemHtml = `
                <div class="selected-media-item d-flex align-items-center p-2 border rounded bg-white shadow-sm" data-id="${media.id}" style="width: calc(50% - 6px); min-width: 240px;">
                    <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded" style="width: 42px; height: 42px; overflow: hidden; flex-shrink: 0;">
                        ${previewThumb}
                    </div>
                    <div class="flex-grow-1 text-truncate me-2">
                        <div class="font-size-13 text-truncate fw-medium" title="${escapeHtml(media.file_name)}">${escapeHtml(media.file_name)}</div>
                        <small class="text-muted font-size-11">${sizeText}</small>
                    </div>
                    <input type="hidden" name="media_ids[]" value="${media.id}">
                    <button type="button" class="btn btn-sm btn-link text-danger p-1 remove-attachment-btn" title="Remove attachment">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            $('#selectedAttachmentsList').append(itemHtml);
        });
    });

    $(document).on('click', '.remove-attachment-btn', function () {
        var item = $(this).closest('.selected-media-item');
        var id = item.data('id');
        selectedMediaIds.delete(id);
        item.remove();
        updateAttachmentCount();
    });

    function updateAttachmentCount() {
        $('#attachmentCountText').text(selectedMediaIds.size + ' of ' + maxFiles + ' selected');
    }

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
