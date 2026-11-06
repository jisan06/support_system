/**
 * Media Picker — WordPress-style media selection modal.
 *
 * Usage:
 *   MediaPicker.open(function (media) {
 *       // media = { id, url, file_name }
 *   });
 */
var MediaPicker = (function () {
    'use strict';

    // ── State ──
    let onSelect    = null;
    let selected    = null;
    let currentPage = 1;
    let lastPage    = 1;
    let loading     = false;
    let modal       = null;

    // ── Selectors (meaningful names) ──
    const SEL = {
        modal:        '#mediaPickerModal',
        config:       '#mediaPickerConfig',
        // Tabs
        tab:          '.media-picker__tab',
        panel:        '.media-picker__panel',
        // Library
        search:       '#mediaPickerSearch',
        grid:         '#mediaPickerGrid',
        gridItem:     '.media-picker__grid-item',
        empty:        '#mediaPickerEmpty',
        loadMore:     '#mediaPickerLoadMore',
        loadMoreBtn:  '#mediaPickerLoadMoreBtn',
        // Sidebar
        sidebarPlaceholder: '#mediaPickerSidebarPlaceholder',
        sidebarDetails:     '#mediaPickerSidebarDetails',
        previewImg:         '#mediaPickerPreviewImg',
        metaName:           '#mediaPickerMetaName',
        metaType:           '#mediaPickerMetaType',
        metaSize:           '#mediaPickerMetaSize',
        // Upload
        dropzone:     '#mediaPickerDropzone',
        fileInput:    '#mediaPickerFileInput',
        uploadProgress: '#mediaPickerUploadProgress',
        progressBar:    '#mediaPickerProgressBar',
        uploadStatus:   '#mediaPickerUploadStatus',
        // Sidebar actions
        deleteBtn:    '#mediaPickerDeleteBtn',
        // Footer
        selectBtn:    '#mediaPickerSelectBtn',
    };

    function getConfig() {
        return $(SEL.config).data();
    }

    // ── Init ──
    function init() {
        // Tab switching
        $(document).on('click', SEL.tab, function () {
            var tabName = $(this).data('tab');
            $(SEL.tab).removeClass('active');
            $(this).addClass('active');
            $(SEL.panel).removeClass('active').hide();
            $(SEL.panel + '[data-panel="' + tabName + '"]').addClass('active').show();
        });

        // Search
        var searchTimer;
        $(SEL.search).on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadGrid(1, false); }, 400);
        });

        // Load more
        $(SEL.loadMoreBtn).on('click', function () {
            loadGrid(currentPage + 1, true);
        });

        // Upload file input
        $(SEL.fileInput).on('change', function () {
            if (this.files.length) uploadFile(this.files[0]);
            this.value = '';
        });

        // Drag & drop on upload tab
        var dropzone = document.querySelector(SEL.dropzone);
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('drag-over');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('drag-over');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
            });
        }

        // Select / deselect grid item
        $(document).on('click', SEL.gridItem, function () {
            if ($(this).hasClass('is-selected')) {
                $(this).removeClass('is-selected');
                selected = null;
                $(SEL.selectBtn).prop('disabled', true);
                $(SEL.sidebarDetails).hide();
                $(SEL.sidebarPlaceholder).show();
            } else {
                $(SEL.gridItem).removeClass('is-selected');
                $(this).addClass('is-selected');
                selected = $(this).data('media');
                showSidebar(selected);
                $(SEL.selectBtn).prop('disabled', false);
            }
        });

        // Confirm selection
        $(SEL.selectBtn).on('click', function () {
            if (selected && onSelect) onSelect(selected);
            close();
        });

        // Delete permanently
        $(SEL.deleteBtn).on('click', function () {
            if (!selected) return;
            if (!confirm('Are you sure you want to delete this permanently?')) return;
            deleteMedia(selected.id);
        });

        // Reset on modal close
        $(SEL.modal).on('hidden.bs.modal', function () {
            reset();
        });
    }

    // ── Public: open ──
    function open(callback) {
        onSelect = callback;
        reset();
        if (!modal) {
            modal = new bootstrap.Modal(SEL.modal);
        }
        modal.show();

        // Ensure library tab is active
        $(SEL.tab).removeClass('active').filter('[data-tab="library"]').addClass('active');
        $(SEL.panel).removeClass('active').hide();
        $(SEL.panel + '[data-panel="library"]').addClass('active').show();

        loadGrid(1, false);
    }

    function close() {
        if (modal) modal.hide();
    }

    // ── Reset ──
    function reset() {
        selected = null;
        $(SEL.grid).empty();
        $(SEL.search).val('');
        $(SEL.selectBtn).prop('disabled', true);
        $(SEL.sidebarPlaceholder).show();
        $(SEL.sidebarDetails).hide();
        $(SEL.empty).hide();
        $(SEL.loadMore).hide();
    }

    // ── Skeleton helpers ──
    function showSkeletons(count) {
        var html = '';
        for (var i = 0; i < count; i++) {
            html += '<div class="media-picker__grid-item media-picker__skeleton"><div class="media-picker__skeleton-shine"></div></div>';
        }
        $(SEL.grid).append(html);
    }

    function removeSkeletons() {
        $(SEL.grid).find('.media-picker__skeleton').remove();
    }

    // ── Load grid via AJAX ──
    function loadGrid(page, append) {
        if (loading) return;
        loading = true;

        if (!append) $(SEL.grid).empty();
        showSkeletons(12);

        var params = new URLSearchParams({
            page: page,
            search: $(SEL.search).val() || '',
        });

        $.ajax({
            url: getConfig().indexUrl + '?' + params.toString(),
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                removeSkeletons();

                var images = res.data.filter(function (item) { return item.is_image; });

                if (images.length === 0 && !append) {
                    $(SEL.empty).show();
                    $(SEL.loadMore).hide();
                } else {
                    $(SEL.empty).hide();
                    images.forEach(function (item) {
                        $(SEL.grid).append(renderGridItem(item));
                    });
                }

                currentPage = res.current_page;
                lastPage    = res.last_page;
                $(SEL.loadMore).toggle(currentPage < lastPage);
                loading = false;
            },
            error: function () {
                removeSkeletons();
                loading = false;
            }
        });
    }

    // ── Render single grid item ──
    function renderGridItem(item) {
        var mediaData = JSON.stringify({ id: item.id, url: item.url, file_name: item.file_name, mime_type: item.mime_type, size: item.size });
        return '<div class="media-picker__grid-item" data-media=\'' + mediaData.replace(/'/g, '&#39;') + '\'>' +
                   '<img src="' + item.url + '" alt="' + escHtml(item.file_name) + '" loading="lazy">' +
               '</div>';
    }

    // ── Show sidebar details ──
    function showSidebar(item) {
        $(SEL.sidebarPlaceholder).hide();
        $(SEL.sidebarDetails).show();
        $(SEL.previewImg).attr('src', item.url);
        $(SEL.metaName).text(item.file_name);
        $(SEL.metaType).text(item.mime_type || 'image');
        $(SEL.metaSize).text(item.size ? formatSize(item.size) : '-');
    }

    // ── Upload ──
    function uploadFile(file) {
        var c = getConfig();
        var formData = new FormData();
        formData.append('file', file);
        formData.append('collection', 'default');
        formData.append('_token', c.csrfToken);

        $(SEL.uploadProgress).show();
        $(SEL.progressBar).css('width', '0%');
        $(SEL.uploadStatus).text('Uploading...');

        $.ajax({
            url: c.storeUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        $(SEL.progressBar).css('width', pct + '%');
                        $(SEL.uploadStatus).text(pct + '%');
                    }
                });
                return xhr;
            },
            success: function (res) {
                if (res.status === 'success') {
                    var item = res.data;
                    if (item.is_image) {
                        // Switch to library tab & prepend
                        $(SEL.tab).removeClass('active').filter('[data-tab="library"]').addClass('active');
                        $(SEL.panel).removeClass('active').hide();
                        $(SEL.panel + '[data-panel="library"]').addClass('active').show();
                        $(SEL.grid).prepend(renderGridItem(item));
                        $(SEL.empty).hide();

                        // Auto-select the uploaded item
                        $(SEL.gridItem).removeClass('is-selected');
                        $(SEL.grid).find(SEL.gridItem).first().addClass('is-selected');
                        selected = { id: item.id, url: item.url, file_name: item.file_name, mime_type: item.mime_type, size: item.size };
                        showSidebar(selected);
                        $(SEL.selectBtn).prop('disabled', false);
                    }
                    toastr.success(res.message);
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Upload failed';
                toastr.error(msg);
            },
            complete: function () {
                setTimeout(function () { $(SEL.uploadProgress).hide(); }, 400);
            }
        });
    }

    // ── Delete permanently ──
    function deleteMedia(id) {
        var c = getConfig();
        $.ajax({
            url: c.destroyUrl + '/' + id,
            type: 'DELETE',
            data: { _token: c.csrfToken },
            success: function (res) {
                // Remove from grid
                $(SEL.gridItem).filter(function () {
                    return $(this).data('media').id === id;
                }).remove();

                // Reset sidebar
                selected = null;
                $(SEL.selectBtn).prop('disabled', true);
                $(SEL.sidebarDetails).hide();
                $(SEL.sidebarPlaceholder).show();

                // Show empty state if no items left
                if ($(SEL.grid).find(SEL.gridItem).length === 0) {
                    $(SEL.empty).show();
                }

                toastr.success(res.message);
            },
            error: function () {
                toastr.error('Delete failed');
            }
        });
    }

    // ── Helpers ──
    function formatSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Auto-init
    $(function () { init(); });

    return { open: open };
})();