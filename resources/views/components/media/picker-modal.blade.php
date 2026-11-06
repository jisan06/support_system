{{-- Media Picker Modal (WordPress-style) --}}
<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:1140px;">
        <div class="modal-content media-picker">
            {{-- Header with tabs --}}
            <div class="media-picker__header">
                <div class="media-picker__tabs">
                    <button type="button" class="media-picker__tab active" data-tab="library">
                        {{ __('media.media_library') }}
                    </button>
                    <button type="button" class="media-picker__tab" data-tab="upload">
                        {{ __('media.upload_files') }}
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="media-picker__body">
                {{-- Upload Tab --}}
                <div class="media-picker__panel" data-panel="upload" style="display:none;">
                    <div class="media-picker__dropzone" id="mediaPickerDropzone">
                        <input type="file" id="mediaPickerFileInput" accept="image/*" class="media-picker__file-input">
                        <div class="media-picker__dropzone-inner">
                            <i class="bx bx-cloud-upload media-picker__dropzone-icon"></i>
                            <p class="media-picker__dropzone-title">{{ __('media.drop_files') }}</p>
                            <p class="media-picker__dropzone-subtitle">{{ __('media.or_click') }}</p>
                        </div>
                    </div>
                    <div class="media-picker__upload-progress" id="mediaPickerUploadProgress" style="display:none;">
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar" id="mediaPickerProgressBar" style="width:0%"></div>
                        </div>
                        <span class="media-picker__upload-status" id="mediaPickerUploadStatus">{{ __('media.uploading') }}...</span>
                    </div>
                </div>

                {{-- Library Tab --}}
                <div class="media-picker__panel active" data-panel="library">
                    <div class="media-picker__content">
                        {{-- Left: Grid --}}
                        <div class="media-picker__library">
                            {{-- Toolbar --}}
                            <div class="media-picker__toolbar">
                                <input type="text" class="form-control form-control-sm media-picker__search" id="mediaPickerSearch" placeholder="{{ __('media.search_placeholder') }}">
                            </div>

                            {{-- Grid --}}
                            <div class="media-picker__grid" id="mediaPickerGrid"></div>

                            {{-- Empty state --}}
                            <div class="media-picker__empty" id="mediaPickerEmpty" style="display:none;">
                                <i class="bx bx-image"></i>
                                <p>{{ __('media.no_media') }}</p>
                            </div>

                            {{-- Load more --}}
                            <div class="media-picker__load-more" id="mediaPickerLoadMore" style="display:none;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="mediaPickerLoadMoreBtn">
                                    {{ __('media.load_more') }}
                                </button>
                            </div>
                        </div>

                        {{-- Right: Attachment details sidebar --}}
                        <div class="media-picker__sidebar">
                            <div class="media-picker__sidebar-placeholder" id="mediaPickerSidebarPlaceholder">
                                <i class="bx bx-pointer"></i>
                                <p>{{ __('media.click_to_select') }}</p>
                            </div>
                            <div class="media-picker__sidebar-details" id="mediaPickerSidebarDetails" style="display:none;">
                                <h6 class="media-picker__sidebar-title">{{ __('media.attachment_details') }}</h6>
                                <div class="media-picker__preview">
                                    <img src="" alt="" id="mediaPickerPreviewImg">
                                </div>
                                <dl class="media-picker__meta">
                                    <dt>{{ __('media.file_name') }}</dt>
                                    <dd id="mediaPickerMetaName"></dd>
                                    <dt>{{ __('media.mime_type') }}</dt>
                                    <dd id="mediaPickerMetaType"></dd>
                                    <dt>{{ __('media.size') }}</dt>
                                    <dd id="mediaPickerMetaSize"></dd>
                                </dl>
                                <a href="javascript:void(0)" class="media-picker__delete-link" id="mediaPickerDeleteBtn">
                                    {{ __('media.delete_permanently') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="media-picker__footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="mediaPickerSelectBtn" disabled>
                    {{ __('media.use_selected') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div id="mediaPickerConfig"
     data-index-url="{{ route('media.index') }}"
     data-store-url="{{ route('media.store') }}"
     data-destroy-url="{{ url('admin/media') }}"
     data-csrf-token="{{ csrf_token() }}"
     style="display:none;"
></div>