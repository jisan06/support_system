$(function() {
    const config   = $('#mediaConfig').data();
    const indexUrl   = config.indexUrl;
    const storeUrl   = config.storeUrl;
    const destroyUrl = config.destroyUrl;
    const bulkDestroyUrl = config.bulkDestroyUrl;
    const csrfToken  = config.csrfToken;
    const langConfirmDeletion = config.langConfirmDeletion;
    const langSureDelete      = config.langSureDelete;
    const langSureBulkDelete  = config.langSureBulkDelete;
    const langDelete          = config.langDelete;
    const langDeleteFailed    = config.langDeleteFailed;
    const langUploadFailed    = config.langUploadFailed;

    let currentPage = 1;
    let lastPage    = 1;
    let loading     = false;
    let bulkMode    = false;
    let selectedIds = [];

    // ── Load Media ──
    function loadMedia(page, append) {
        if (loading) return;
        loading = true;

        const params = new URLSearchParams({
            page: page,
            collection: $('#filterCollection').val() || '',
            search: $('#searchMedia').val() || '',
        });

        $.ajax({
            url: indexUrl + '?' + params.toString(),
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (!append) $('#mediaGrid').empty();

                if (res.data.length === 0 && !append) {
                    $('#emptyState').show();
                    $('#loadMoreWrap').hide();
                } else {
                    $('#emptyState').hide();
                    res.data.forEach(item => {
                        $('#mediaGrid').append(renderCard(item));
                    });
                }

                currentPage = res.current_page;
                lastPage    = res.last_page;
                $('#loadMoreWrap').toggle(currentPage < lastPage);
                loading = false;

                // Re-apply selection state after load more
                if (bulkMode) {
                    selectedIds.forEach(id => {
                        $(`.media-item[data-id="${id}"] .media-card`).addClass('is-bulk-selected');
                    });
                }
            },
            error: function() {
                loading = false;
            }
        });
    }

    // ── Render Card ──
    function renderCard(item) {
        const sizeText = formatSize(item.size);
        const preview  = item.is_image
            ? `<img src="${item.url}" alt="${escHtml(item.file_name)}" class="media-thumb" loading="lazy">`
            : `<div class="media-icon-box"><i class="bx bx-file" style="font-size:48px;color:#adb5bd;"></i></div>`;

        return `
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 media-item" data-id="${item.id}">
            <div class="card media-card rounded shadow-sm mb-0 h-100">
                <div class="position-relative">
                    ${preview}
                    <div class="media-overlay rounded-top">
                        ${item.is_image ? `<button class="btn btn-sm btn-light me-1 preview-btn" data-url="${item.url}" data-name="${escHtml(item.file_name)}"><i class="bx bx-zoom-in"></i></button>` : ''}
                        <button class="btn btn-sm btn-danger delete-media-btn" data-id="${item.id}"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <p class="mb-0 small text-truncate fw-medium" title="${escHtml(item.file_name)}">${escHtml(item.file_name)}</p>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="badge bg-soft-info text-info" style="font-size:10px;">${escHtml(item.collection)}</span>
                        <span class="text-muted" style="font-size:10px;">${sizeText}</span>
                    </div>
                </div>
            </div>
        </div>`;
    }

    // ── Upload Files ──
    function uploadFiles(files) {
        if (files.length === 0) return;

        let completed = 0;
        const total = files.length;

        $('#uploadProgress').show();
        $('#uploadStatus').text(`Uploading 0 / ${total}...`);
        $('#progressBar').css('width', '0%');

        Array.from(files).forEach(file => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('collection', $('#filterCollection').val() || 'default');
            formData.append('_token', csrfToken);

            $.ajax({
                url: storeUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.status === 'success') {
                        $('#mediaGrid').prepend(renderCard(res.data));
                        $('#emptyState').hide();
                        toastr.success(res.message);
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || langUploadFailed;
                    toastr.error(file.name + ': ' + msg);
                },
                complete: function() {
                    completed++;
                    const pct = Math.round((completed / total) * 100);
                    $('#progressBar').css('width', pct + '%');
                    $('#uploadStatus').text(`Uploading ${completed} / ${total}...`);

                    if (completed === total) {
                        setTimeout(() => {
                            $('#uploadProgress').hide();
                        }, 500);
                    }
                }
            });
        });
    }

    // ── Drag & Drop ──
    const dropZone = document.getElementById('dropZone');

    ['dragenter', 'dragover'].forEach(e => {
        dropZone.addEventListener(e, function(ev) {
            ev.preventDefault();
            dropZone.classList.add('drag-over');
        });
    });
    ['dragleave', 'drop'].forEach(e => {
        dropZone.addEventListener(e, function(ev) {
            ev.preventDefault();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', function(ev) {
        uploadFiles(ev.dataTransfer.files);
    });

    $('#fileInput').on('change', function() {
        uploadFiles(this.files);
        this.value = '';
    });

    // ── Filter & Search ──
    let searchTimer;
    $('#searchMedia').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadMedia(1, false), 400);
    });

    $('#filterCollection').on('change', function() {
        loadMedia(1, false);
    });

    // ── Load More ──
    $('#loadMoreBtn').on('click', function() {
        loadMedia(currentPage + 1, true);
    });

    // ── Lightbox ──
    $(document).on('click', '.preview-btn', function(e) {
        e.stopPropagation();
        if (bulkMode) return;
        $('#lightboxImage').attr('src', $(this).data('url'));
        $('#lightboxName').text($(this).data('name'));
        new bootstrap.Modal('#mediaLightbox').show();
    });

    // ── Single Delete ──
    $(document).on('click', '.delete-media-btn', function(e) {
        e.stopPropagation();
        if (bulkMode) return;
        const id = $(this).data('id');

        $('#confirmModal #confirmModalLabel').text(langConfirmDeletion);
        $('#confirmModal #confirmModalDesc').text(langSureDelete);
        $('#confirmModal #confirmAction').text(langDelete);
        $('#confirmModal').data('delete-id', id).addClass('mediaDelModal').modal('show');
    });

    $(document).on('click', '.mediaDelModal #confirmAction', function() {
        const id = $('#confirmModal').data('delete-id');
        const close = $(this).parents('#confirmModal:first').find('.btn-close');

        $.ajax({
            url: destroyUrl + '/' + id,
            type: 'DELETE',
            data: { _token: csrfToken },
            success: function(res) {
                close.trigger('click');
                $(`.media-item[data-id="${id}"]`).fadeOut(300, function() {
                    $(this).remove();
                    if ($('#mediaGrid .media-item').length === 0) {
                        $('#emptyState').show();
                    }
                });
                toastr.success(res.message);
            },
            error: function() {
                toastr.error(langDeleteFailed);
            },
            complete: function() {
                $('#confirmModal').removeClass('mediaDelModal');
            }
        });
    });

    // ── Bulk Select Mode (WordPress-style) ──
    function enterBulkMode() {
        bulkMode = true;
        selectedIds = [];
        $('#mediaGrid').addClass('bulk-mode');
        $('#bulkSelectToggle').addClass('active btn-primary').removeClass('btn-outline-secondary');
        $('#bulkCancelBtn, #bulkDivider').removeClass('d-none');
        updateBulkUI();
    }

    function exitBulkMode() {
        bulkMode = false;
        selectedIds = [];
        $('#mediaGrid').removeClass('bulk-mode');
        $('.media-card').removeClass('is-bulk-selected');
        $('#bulkSelectToggle').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $('#selectedCount, #bulkDeleteBtn, #bulkCancelBtn, #bulkDivider').addClass('d-none');
    }

    function updateBulkUI() {
        const count = selectedIds.length;
        if (count > 0) {
            $('#selectedCount').removeClass('d-none').text(count + ' selected');
            $('#bulkDeleteBtn').removeClass('d-none');
        } else {
            $('#selectedCount, #bulkDeleteBtn').addClass('d-none');
        }
    }

    $('#bulkSelectToggle').on('click', function() {
        if (bulkMode) {
            exitBulkMode();
        } else {
            enterBulkMode();
        }
    });

    $('#bulkCancelBtn').on('click', function() {
        exitBulkMode();
    });

    // Click card to toggle selection in bulk mode
    $(document).on('click', '.media-card', function(e) {
        if (!bulkMode) return;
        e.preventDefault();
        e.stopPropagation();

        const $item = $(this).closest('.media-item');
        const id = String($item.data('id'));
        const idx = selectedIds.indexOf(id);

        if (idx > -1) {
            selectedIds.splice(idx, 1);
            $(this).removeClass('is-bulk-selected');
        } else {
            selectedIds.push(id);
            $(this).addClass('is-bulk-selected');
        }

        updateBulkUI();
    });

    // ── Bulk Delete ──
    $('#bulkDeleteBtn').on('click', function() {
        if (selectedIds.length === 0) return;

        $('#confirmModal #confirmModalLabel').text(langConfirmDeletion);
        $('#confirmModal #confirmModalDesc').text(langSureBulkDelete);
        $('#confirmModal #confirmAction').text(langDelete);
        $('#confirmModal').data('bulk-ids', selectedIds.slice()).addClass('mediaBulkDelModal').modal('show');
    });

    $(document).on('click', '.mediaBulkDelModal #confirmAction', function() {
        const ids   = $('#confirmModal').data('bulk-ids');
        const close = $(this).parents('#confirmModal:first').find('.btn-close');
        const $btn  = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: bulkDestroyUrl,
            type: 'DELETE',
            data: { _token: csrfToken, ids: ids },
            success: function(res) {
                close.trigger('click');
                ids.forEach(function(id) {
                    $(`.media-item[data-id="${id}"]`).fadeOut(300, function() { $(this).remove(); });
                });
                setTimeout(function() {
                    if ($('#mediaGrid .media-item').length === 0) {
                        $('#emptyState').show();
                    }
                }, 350);
                toastr.success(res.message);
                exitBulkMode();
            },
            error: function() {
                toastr.error(langDeleteFailed);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $('#confirmModal').removeClass('mediaBulkDelModal');
            }
        });
    });

    // ── Helpers ──
    function formatSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Init ──
    loadMedia(1, false);
});