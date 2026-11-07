@extends('customer.layouts.master')
@section('title', 'Create New Ticket')

@section('content')
{{-- Breadcrumb & Header --}}
<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Ticket</li>
            </ol>
        </nav>
        <h4 class="fw-bold text-dark mb-0">Create New Support Ticket</h4>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-10 mx-auto">
        <div class="card shadow-sm border-0" style="border-radius: 10px;">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-1 fw-bold text-dark">Submit a Support Ticket</h5>
                <p class="text-muted font-size-13 mb-0">Please fill in the details below. Our support team will review and respond within our guaranteed business hours.</p>
            </div>
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <div class="fw-semibold mb-1"><i class="bx bx-error-circle me-1"></i> Please check the form errors below:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('customer.tickets.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Subject --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="subject">
                            Subject <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="subject"
                            id="subject"
                            value="{{ old('subject') }}"
                            class="form-control @error('subject') is-invalid @enderror"
                            placeholder="Brief summary of your question or issue"
                            maxlength="255"
                            required
                        >
                        @error('subject')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row">
                        {{-- Category / Department --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="department">
                                Category / Department <span class="text-muted font-size-12">(Optional)</span>
                            </label>
                            <select name="department" id="department" class="form-select @error('department') is-invalid @enderror">
                                <option value="">-- Select a Category --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ old('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                @endforeach
                            </select>
                            @error('department')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Priority --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="priority">
                                Priority <span class="text-danger">*</span>
                            </label>
                            <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach($priorities as $val => $label)
                                    <option value="{{ $val }}" {{ old('priority', 'medium') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="description">
                            Description <span class="text-danger">*</span>
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="6"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Please describe your issue in detail. The more details you provide, the faster we can assist you."
                            minlength="2"
                            required
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Attachments --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="attachments">
                            <i class="bx bx-paperclip me-1"></i> Attachments <span class="text-muted font-size-12">(Optional)</span>
                        </label>
                        <input
                            type="file"
                            name="attachments[]"
                            id="attachments"
                            class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                            multiple
                        >
                        <div class="form-text text-muted font-size-12">
                            Attach up to {{ config('ticket.attachments.max_files', 5) }} files (Max 10MB per file). Allowed formats: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP.
                        </div>
                        @error('attachments')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                        @error('attachments.*')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary px-4">
                            <i class="bx bx-arrow-back me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bx bx-send me-1"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

