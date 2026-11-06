<template id="bulk-action-template">
    <div id="bulk-action-wrapper">
        <select id="bulk-action-select" class="form-select form-select-sm">
            <option value="">@lang('common.bulk_actions')</option>
            @foreach($bulk_actions as $option)
                <option value="{{$option->value}}">
                    {{$option->label}}
                </option>
            @endforeach
        </select>
        <button
            id="bulk-apply-btn"
            class="btn btn-sm btn-secondary"
            data-modal-del-title="@lang('common.confirm_deletion')"
            data-modal-del-desc="@lang('common.sure_bulk_delete')"
            data-modal-force-del-desc="@lang('common.sure_bulk_force_delete')"
            data-modal-del-action="@lang('common.delete')"

            data-modal-res-title="@lang('common.confirm_restore')"
            data-modal-res-desc="@lang('common.sure_bulk_restore')"
            data-modal-res-action="@lang('common.restore')"
        >
            @lang('common.apply')
        </button>
    </div>
</template>

<script>
    $(document).ready(function () {
        let selectedIds = [];

        // Show Toastr error
        function showEmptyError(message) {
            const prevPosition = toastr.options.positionClass;
            toastr.options.positionClass = 'toast-middle-center';
            toastr.error(message);
            toastr.options.positionClass = prevPosition;
        }

        // Generic AJAX action for delete or restore
        function performAction(url, ids, successMsg, errorMsg) {
            if (!url || ids.length === 0) return;

            let close = $('#confirmModal .btn-close');
            let table = $('.dataTable').DataTable();

            $.ajax({
                url: url,
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids
                },
                success: function (response) {
                    close.trigger("click");
                    toastr.success(response.message || successMsg);
                    ids.forEach(function(id) {
                        table.rows('[data-id="' + id + '"]').remove().draw(false);
                    });
                },
                error: function (xhr) {
                    close.trigger("click");
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : errorMsg;
                    toastr.error(msg);
                }
            });
        }

        // Single row actions (delete or restore)
        $(document).on("click", ".delete-row, .restore-row", function () {
            let that = $(this);
            let url = that.data("route");
            let type = that.hasClass('delete-row') ? 'delete' : 'restore';
            let errorMsg = type === 'delete' ? "@lang('common.delete_failed')" : "@lang('common.restore_failed')";

            // Set modal text
            $('#confirmModal #confirmModalLabel').text(that.data('modal-title'));
            $('#confirmModal #confirmModalDesc').text(that.data('modal-desc'));
            $('#confirmModal #confirmAction').text(that.data('modal-action'));

            // Store URL and error message in modal button
            $('#confirmModal #confirmAction')
                .data('url', url)
                .data('ids', [that.data('id')])
                .data('error-msg', errorMsg);

            $('#confirmModal').modal('show');
        });

        // Bulk actions
        $('.dataTable').on('init.dt', function () {
            const $table = $(this);

            // Insert bulk action HTML
            $('.dt-length').after($('#bulk-action-template').html());

            const $bulkSelect = $('#bulk-action-select');
            const $bulkApplyBtn = $('#bulk-apply-btn');
            const $selectAll = $table.find('#select_all');

            function updateCheckboxHandlers() {
                const $actionCheckboxes = $table.find('.action-checkbox');
                $selectAll.off('change').on('change', function () {
                    $actionCheckboxes.prop('checked', $(this).is(':checked'));
                });

                $actionCheckboxes.off('change').on('change', function () {
                    $selectAll.prop('checked', $actionCheckboxes.length === $actionCheckboxes.filter(':checked').length);
                });
            }

            updateCheckboxHandlers();
            $table.on('draw.dt', updateCheckboxHandlers);

            $bulkApplyBtn.on('click', function () {
                let actionType = $bulkSelect.val();
                const $checked = $table.find('.action-checkbox:checked');
                selectedIds = $checked.map(function () { return $(this).val(); }).get();

                if (!actionType || selectedIds.length === 0) {
                    showEmptyError("@lang('common.bulk_empty_warning')");
                    return;
                }

                let url = '';
                let modalTitle = '';
                let modalDesc = '';
                let modalAction = '';
                let errorMsg = '';

                if ((actionType === 'trash' || actionType === 'force_delete') && "{{ $bulk_delete_url ?? '' }}") {
                    url = "{{ $bulk_delete_url ?? '' }}";
                    modalTitle = $(this).data('modal-del-title');
                    modalDesc = actionType === 'force_delete'
                        ? $(this).data('modal-force-del-desc')
                        : $(this).data('modal-del-desc');
                    modalAction = $(this).data('modal-del-action');
                    errorMsg = "@lang('common.delete_failed')";
                    $('#confirmModal').addClass('bulkDelModal').modal('show');
                } else if (actionType === 'restore' && "{{ $bulk_restore_url ?? '' }}") {
                    url = "{{ $bulk_restore_url ?? '' }}";
                    modalTitle = $(this).data('modal-res-title');
                    modalDesc = $(this).data('modal-res-desc');
                    modalAction = $(this).data('modal-res-action');
                    errorMsg = "@lang('common.restore_failed')";
                    $('#confirmModal').addClass('bulkRestoreModal').modal('show');
                }

                if (!url) return;

                // Set modal content and store data
                $('#confirmModal #confirmModalLabel').text(modalTitle);
                $('#confirmModal #confirmModalDesc').text(modalDesc);
                $('#confirmModal #confirmAction').text(modalAction)
                    .data('url', url)
                    .data('ids', selectedIds)
                    .data('error-msg', errorMsg);

                $('#confirmModal').modal('show');
            });
        });

        // Handle modal confirm action for bulk delete & restore
        $(document).on('click', '.bulkDelModal #confirmAction, .bulkRestoreModal #confirmAction', function () {
            let url = $(this).data('url');
            let ids = $(this).data('ids') || [];
            let errorMsg = $(this).data('error-msg') || "@lang('common.action_failed')";

            performAction(url, ids, "", errorMsg);
        });
    });
</script>
