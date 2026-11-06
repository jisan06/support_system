!(function ($) {
    $(document).on("click", ".alert-dismissible .btn-close", function () {
        $(this).closest(".alert").fadeOut("slow", function () {
            $(this).remove();
        });
    });

    $(document).ready(function () {
        $('.modal').on('hidden.bs.modal', function () {
            $('.modal').removeClass('singleDelModal bulkDelModal singleRestoreModal bulkRestoreModal');
        });
    });
})(jQuery);
