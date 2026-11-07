<script src="{{ admin_asset('libs/jquery/jquery.min.js') }}"></script>
<script src="{{ admin_asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ admin_asset('libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ admin_asset('libs/node-waves/waves.min.js') }}"></script>
<script src="{{ admin_asset('js/script.js') }}"></script>
<script src="{{ admin_asset('libs/toastr/build/toastr.min.js') }}"></script>

<!-- App js -->
<script src="{{ admin_asset('js/app.js') }}"></script>
<script src="{{ admin_asset('js/password-strength.js') }}"></script>
@if(!empty($bulk_actions))
    @include('admin.layouts.include.others.bulk_action')
@endif
@stack('script_links')
<script>
    toastr.options = {
        "closeButton": true,       // Adds a close (×) button to the toast, allowing users to manually close it
        "newestOnTop": false,       // Places new toasts on top of existing ones, rather than at the bottom
        "progressBar": true,       // Shows a progress bar that visually indicates how long the toast will remain
        // "positionClass": "toast-top-right", // Determines the position of the toast on the screen
        // // e.g., top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
        //
        // "preventDuplicates": true, // Prevents the same toast message from appearing multiple times simultaneously
        // "onclick": null,           // A callback function that executes when the toast is clicked (null = no action)
        // "showDuration": "300",     // Time in milliseconds for the toast to appear (fade-in effect duration)
        // "hideDuration": "1000",    // Time in milliseconds for the toast to disappear (fade-out effect duration)
        // "timeOut": "0",         // How long (in milliseconds) the toast stays visible before automatically hiding
        // "extendedTimeOut": "0", // Additional time the toast remains visible if the user hovers over it
        // "showEasing": "swing",     // Easing function for the show animation (controls the motion style)
        // "hideEasing": "linear",    // Easing function for the hide animation
        // "showMethod": "fadeIn",    // Animation method to display the toast (e.g., fadeIn, slideDown)
        // "hideMethod": "fadeOut",    // Animation method to hide the toast (e.g., fadeOut, slideUp)
        // "debug": false,            // Enables debug mode (not usually needed; used for troubleshooting)
    };

    @if (session('success'))
        toastr.success("{{ session('success') }}");
    @endif
    $(document).ready(function () {
        function initTooltips() {
            if (typeof $.fn.tooltip === 'function') {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        }
        $('.dataTable').on('draw.dt', initTooltips);
        initTooltips();

        // Global Password Visibility Toggle
        $(document).off('click', '.toggle-password-btn').on('click', '.toggle-password-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var targetSelector = $btn.data('target');
            var $input = targetSelector ? $(targetSelector) : $btn.closest('.input-group').find('input');
            var $icon = $btn.find('i');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('bx-show').addClass('bx-hide');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('bx-hide').addClass('bx-show');
            }
        });
    })
</script>
