@extends('admin.layouts.master')
@section('title', __('menu.media'))
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2">
                <h5 class="font-size-15 mb-0">
                    <i class="bx bx-image me-2"></i> {{ __('media.title') }}
                </h5>
                <div class="d-flex align-items-center gap-2">
                    {{-- Bulk Select Toggle --}}
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkSelectToggle">
                        {{ __('media.bulk_select') }}
                    </button>
                    {{-- Bulk action buttons (visible in bulk mode with selection) --}}
                    <span class="text-muted small d-none" id="selectedCount"></span>
                    <button type="button" class="btn btn-danger btn-sm d-none" id="bulkDeleteBtn">
                        {{ __('media.delete_selected') }}
                    </button>
                    <span class="border-start ms-1 ps-2 d-none" id="bulkDivider"></span>
                    <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none d-none" id="bulkCancelBtn">
                        {{ __('common.cancel') }}
                    </button>
                    {{-- Collection Filter --}}
                    <select id="filterCollection" class="form-select form-select-sm" style="width:150px;">
                        <option value="">{{ __('media.all_collections') }}</option>
                        @foreach($collections as $col)
                            <option value="{{ $col }}">{{ $col }}</option>
                        @endforeach
                    </select>
                    {{-- Search --}}
                    <input type="text" id="searchMedia" class="form-control form-control-sm" placeholder="{{ __('media.search_placeholder') }}" style="width:200px;">
                </div>
            </div>

            <div class="card-body">
                {{-- Drag & Drop Upload Zone --}}
                <div id="dropZone" class="border border-2 border-dashed rounded-3 text-center p-4 mb-4 position-relative" style="border-color:#adb5bd !important; transition:all .2s;">
                    <input type="file" id="fileInput" multiple accept="{{ implode(',', array_map(fn($m) => '.' . $m, config('media.allowed_mimes'))) }}" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor:pointer; z-index:2;">
                    <div class="py-2">
                        <i class="bx bx-cloud-upload text-muted" style="font-size:48px;"></i>
                        <p class="text-muted mb-1 mt-2 fw-medium">{{ __('media.drop_files') }}</p>
                        <p class="text-muted small mb-0">{{ __('media.or_click') }}</p>
                    </div>
                </div>

                {{-- Upload Progress --}}
                <div id="uploadProgress" class="mb-3" style="display:none;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <span class="text-muted small" id="uploadStatus">Uploading...</span>
                    </div>
                    <div class="progress" style="height:4px;">
                        <div id="progressBar" class="progress-bar" style="width:0%"></div>
                    </div>
                </div>

                {{-- Media Grid --}}
                <div id="mediaGrid" class="row g-3">
                    {{-- Items loaded via AJAX --}}
                </div>

                {{-- Empty State --}}
                <div id="emptyState" class="text-center py-5" style="display:none;">
                    <i class="bx bx-image text-muted" style="font-size:64px;"></i>
                    <p class="text-muted mt-2">{{ __('media.no_media') }}</p>
                </div>

                {{-- Load More --}}
                <div id="loadMoreWrap" class="text-center mt-4" style="display:none;">
                    <button id="loadMoreBtn" class="btn btn-outline-primary btn-sm px-4">{{ __('media.load_more') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Lightbox Modal --}}
<div class="modal fade" id="mediaLightbox" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-none" style="background: rgba(0,0,0,.9); border-radius: 12px;">
            <div class="modal-body p-3 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index:10;"></button>
                <img id="lightboxImage" src="" class="img-fluid rounded" style="max-height:80vh; object-fit:contain;">
                <div class="mt-2">
                    <span id="lightboxName" class="text-white small"></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirm Delete Modal --}}
@include('components.modal.confirm')
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
    @vite(['resources/css/media.css'])
@endpush

@push('script_links')
<div id="mediaConfig"
    data-index-url="{{ route('media.index') }}"
    data-store-url="{{ route('media.store') }}"
    data-destroy-url="{{ url('admin/media') }}"
    data-bulk-destroy-url="{{ route('media.bulk_destroy') }}"
    data-csrf-token="{{ csrf_token() }}"
    data-lang-confirm-deletion="@lang('common.confirm_deletion')"
    data-lang-sure-bulk-delete="@lang('common.sure_bulk_delete')"
    data-lang-sure-delete="@lang('common.sure_delete')"
    data-lang-delete="@lang('common.delete')"
    data-lang-delete-failed="@lang('common.delete_failed')"
    data-lang-upload-failed="@lang('media.upload_failed')"
></div>
<script src="{{ asset('backend/assets/js/media.js') }}"></script>
@endpush
