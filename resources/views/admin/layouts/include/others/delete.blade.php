<script>
    // Single Delete
    function singleDelete () {
        let url = '';
        $(document).on("click", ".delete-row", function () {
            let that = $(this);
            $('#confirmModal #confirmModalLabel').text( that.data('modal-title') );
            $('#confirmModal #confirmModalDesc').text( that.data('modal-desc') );
            $('#confirmModal #confirmAction').text( that.data('modal-action') );
            $('#confirmModal').addClass('singleDelModal').modal('show');
            url = that.data("route");
        });

        // When the confirm delete button is clicked, send AJAX request
        $(document).on('click', '.singleDelModal #confirmAction', function () {
            let close = $(this).closest('#confirmModal').find('.btn-close');
            $.ajax({
                url: url,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    close.trigger("click");
                    toastr["success"](response.message);
                    $('.dataTable').DataTable().ajax.reload(null, false);
                },
                error: function (xhr) {
                    close.trigger("click");
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : "@lang('common.delete_failed')";
                    toastr.error(msg);
                }
            });
        });
    }

    $(document).ready(function () {
        // Single Delete
        singleDelete()

    });

</script>
