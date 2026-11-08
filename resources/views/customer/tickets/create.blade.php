@extends('customer.layouts.master')
@section('title', 'Submit Support Ticket')

@section('content')
{{-- Modern Hero / Breadcrumb Header --}}
<div class="customer-ticket-header">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 font-size-13">
                    <li class="breadcrumb-item">
                        <a href="{{ route('customer.dashboard') }}" class="text-decoration-none text-muted">
                            <i class="bx bx-home-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">New Ticket</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-1">Submit a Support Ticket</h3>
            <p class="text-muted mb-0 font-size-14">
                Describe your issue or question below. Our support team will review and reply promptly.
            </p>
        </div>
        <div>
            <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-pill font-size-13">
                <i class="bx bx-arrow-back me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Form Column (8 cols) --}}
    <div class="col-lg-8">
        <div class="form-card">
            <div class="card-body p-4 p-md-4">
                {{-- Global Errors Alert --}}
                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bx bx-error-circle font-size-20 me-2 text-danger"></i>
                            <span class="fw-bold">Please correct the following errors:</span>
                        </div>
                        <ul class="mb-0 ps-4 font-size-13">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('customer.tickets.store') }}" method="POST" enctype="multipart/form-data" id="customerTicketForm">
                    @csrf

                    {{-- Ticket Subject --}}
                    <div class="mb-4">
                        <label class="form-label form-label-modern" for="subject">
                            Subject <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="subject"
                            id="subject"
                            value="{{ old('subject') }}"
                            class="form-control form-control-modern @error('subject') is-invalid @enderror"
                            placeholder="e.g. Cannot access payment invoice from billing tab"
                            maxlength="255"
                            required
                            autofocus
                        >
                        <div class="form-hint">A clear, concise summary of what you need assistance with.</div>
                        @error('subject')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Category & Priority Grid --}}
                    <div class="row g-3 mb-4">
                        {{-- Category / Department --}}
                        <div class="col-md-5">
                            <label class="form-label form-label-modern" for="department">
                                Category / Department <span class="text-muted fw-normal font-size-12">(Optional)</span>
                            </label>
                            <select name="department" id="department" class="form-select form-control-modern @error('department') is-invalid @enderror">
                                <option value="">Select a Category</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>
                                        {{ $dept }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Priority Radio Pills --}}
                        <div class="col-md-7">
                            <label class="form-label form-label-modern d-block">
                                Priority Level <span class="text-danger">*</span>
                            </label>
                            <div class="priority-pill-group">
                                {{-- Low --}}
                                <label class="priority-pill-label">
                                    <input type="radio" name="priority" value="low" class="d-none" {{ old('priority') == 'low' ? 'checked' : '' }} required>
                                    <div class="priority-pill-card">
                                        <span class="priority-dot bg-success"></span>
                                        <span class="font-size-13 fw-semibold text-dark">Low</span>
                                    </div>
                                </label>
                                {{-- Medium (Default) --}}
                                <label class="priority-pill-label">
                                    <input type="radio" name="priority" value="medium" class="d-none" {{ old('priority', 'medium') == 'medium' ? 'checked' : '' }} required>
                                    <div class="priority-pill-card">
                                        <span class="priority-dot bg-info"></span>
                                        <span class="font-size-13 fw-semibold text-dark">Medium</span>
                                    </div>
                                </label>
                                {{-- High --}}
                                <label class="priority-pill-label">
                                    <input type="radio" name="priority" value="high" class="d-none" {{ old('priority') == 'high' ? 'checked' : '' }} required>
                                    <div class="priority-pill-card">
                                        <span class="priority-dot bg-warning"></span>
                                        <span class="font-size-13 fw-semibold text-dark">High</span>
                                    </div>
                                </label>
                                {{-- Urgent --}}
                                <label class="priority-pill-label">
                                    <input type="radio" name="priority" value="urgent" class="d-none" {{ old('priority') == 'urgent' ? 'checked' : '' }} required>
                                    <div class="priority-pill-card">
                                        <span class="priority-dot bg-danger"></span>
                                        <span class="font-size-13 fw-semibold text-dark">Urgent</span>
                                    </div>
                                </label>
                            </div>
                            @error('priority')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <label class="form-label form-label-modern" for="description">
                            Description <span class="text-danger">*</span>
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="6"
                            class="form-control form-control-modern @error('description') is-invalid @enderror"
                            placeholder="Please explain the problem in detail:&#10;• What were you trying to do?&#10;• What went wrong or what error did you see?&#10;• Any steps we can follow to reproduce the problem?"
                            minlength="2"
                            required
                        >{{ old('description') }}</textarea>
                        <div class="form-hint">The more specifics and context you share, the faster we can diagnose and resolve it.</div>
                        @error('description')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Attachments Upload Dropzone --}}
                    <div class="mb-4">
                        <label class="form-label form-label-modern d-flex justify-content-between align-items-center">
                            <span><i class="bx bx-paperclip me-1 text-primary"></i> Attachments</span>
                            <span class="text-muted fw-normal font-size-12">Optional &bull; Up to {{ config('ticket.attachments.max_files', 5) }} files (Max 10MB each)</span>
                        </label>

                        <div class="modern-dropzone" id="dropzoneArea" onclick="document.getElementById('attachments').click()">
                            <input
                                type="file"
                                name="attachments[]"
                                id="attachments"
                                class="d-none @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                                multiple
                                onchange="handleFileSelection(this)"
                            >
                            <div class="dropzone-icon-circle">
                                <i class="bx bx-cloud-upload"></i>
                            </div>
                            <div class="fw-semibold text-dark font-size-14 mb-1">
                                Click to choose files or drag & drop here
                            </div>
                            <div class="text-muted font-size-12">
                                Supported formats: JPG, PNG, WEBP, PDF, DOCX, XLSX, TXT, ZIP
                            </div>
                        </div>

                        {{-- Selected File Chips Preview --}}
                        <div id="filePreviewContainer" class="mt-3 d-none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="font-size-12 fw-semibold text-muted">Selected files:</span>
                                <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none font-size-12" onclick="clearSelectedFiles()">
                                    <i class="bx bx-trash me-1"></i> Clear all
                                </button>
                            </div>
                            <div id="fileChipsList" class="d-flex flex-wrap gap-2"></div>
                        </div>

                        @error('attachments')
                            <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                        @enderror
                        @error('attachments.*')
                            <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Form Footer Actions --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
                        <a href="{{ route('customer.dashboard') }}" class="btn btn-light rounded-pill px-4 text-muted">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 shadow-sm fw-semibold">
                            <i class="bx bx-paper-plane me-2"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar Guidance Column (4 cols) --}}
    <div class="col-lg-4">
        {{-- Tips Card --}}
        <div class="help-sidebar-card">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="help-sidebar-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bx bx-bulb font-size-20"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0 font-size-15">Tips for Quick Resolution</h6>
                    <small class="text-muted font-size-12">How to get help faster</small>
                </div>
            </div>

            <div class="help-tip-item">
                <span class="help-tip-bullet"><i class="bx bx-check"></i></span>
                <div>
                    <strong class="text-dark">Be Specific:</strong> Mention any exact error messages, codes, or unexpected behaviors you observed.
                </div>
            </div>

            <div class="help-tip-item">
                <span class="help-tip-bullet"><i class="bx bx-check"></i></span>
                <div>
                    <strong class="text-dark">Attach Screenshots:</strong> A picture or screen recording saves hours of back-and-forth troubleshooting.
                </div>
            </div>

            <div class="help-tip-item">
                <span class="help-tip-bullet"><i class="bx bx-check"></i></span>
                <div>
                    <strong class="text-dark">Select Proper Priority:</strong> Use <em>Urgent</em> only if your live services or business operations are completely down.
                </div>
            </div>
        </div>

        {{-- SLA / Response Guarantee Card --}}
        <div class="help-sidebar-card">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="help-sidebar-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bx bx-time-five font-size-20"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0 font-size-15">Support Hours & SLA</h6>
                    <small class="text-muted font-size-12">Guaranteed response timeline</small>
                </div>
            </div>

            <p class="text-muted font-size-13 mb-3">
                Our support agents are active during normal business hours. All incoming tickets are prioritized based on their SLA deadlines.
            </p>

            <div class="p-3 rounded-3 bg-light border font-size-12 text-muted">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-dark fw-medium">Hours:</span>
                    <span>Mon &ndash; Fri, 9:00 AM &ndash; 6:00 PM</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-dark fw-medium">First Response:</span>
                    <span class="text-success fw-semibold">Within 1&ndash;2 Hours</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-dark fw-medium">Urgent Issues:</span>
                    <span class="text-danger fw-semibold">High Priority Queue</span>
                </div>
            </div>
        </div>

        {{-- Customer Security Assurance --}}
        <div class="p-3 rounded-3 bg-white border border-light-subtle font-size-12 text-muted d-flex align-items-center gap-2">
            <i class="bx bx-shield-quarter text-success font-size-22 flex-shrink-0"></i>
            <span>Your communications and uploaded attachments are encrypted and strictly confidential.</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleFileSelection(input) {
        const previewContainer = document.getElementById('filePreviewContainer');
        const chipsList = document.getElementById('fileChipsList');
        chipsList.innerHTML = '';

        if (!input.files || input.files.length === 0) {
            previewContainer.classList.add('d-none');
            return;
        }

        const maxFiles = {{ config('ticket.attachments.max_files', 5) }};
        if (input.files.length > maxFiles) {
            alert('You can select a maximum of ' + maxFiles + ' files.');
            input.value = '';
            previewContainer.classList.add('d-none');
            return;
        }

        Array.from(input.files).forEach(function(file) {
            const sizeInKb = (file.size / 1024).toFixed(0);
            const sizeLabel = sizeInKb > 1024 ? (sizeInKb / 1024).toFixed(1) + ' MB' : sizeInKb + ' KB';

            const chip = document.createElement('div');
            chip.className = 'file-chip';
            chip.innerHTML = '<i class="bx bx-file text-primary"></i> <span class="fw-medium">' + escapeHtml(file.name) + '</span> <span class="text-muted">(' + sizeLabel + ')</span>';
            chipsList.appendChild(chip);
        });

        previewContainer.classList.remove('d-none');
    }

    function clearSelectedFiles() {
        const input = document.getElementById('attachments');
        input.value = '';
        document.getElementById('fileChipsList').innerHTML = '';
        document.getElementById('filePreviewContainer').classList.add('d-none');
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Drag and drop visual cues
    const dropzone = document.getElementById('dropzoneArea');
    const fileInput = document.getElementById('attachments');

    ['dragenter', 'dragover'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelection(fileInput);
        }
    });
</script>
@endpush
