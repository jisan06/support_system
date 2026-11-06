@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/select2/css/select2.min.css') }}">
@endpush

@push('script_links')
    <script src="{{ admin_asset('libs/select2/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                placeholder: "Select Items",
                width: '100%'
            });
        });
    </script>
@endpush
