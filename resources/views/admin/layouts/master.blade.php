<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') | {{ setting('site_name', 'Admin') }}@else{{ setting('site_name', 'Admin') }}@endif</title>
    @php
        $faviconUrl = null;
        if ($faviconId = setting('favicon')) {
            $faviconMedia = \App\Models\Media::find($faviconId);
            $faviconUrl = $faviconMedia ? \Illuminate\Support\Facades\Storage::disk($faviconMedia->disk)->url($faviconMedia->path) : null;
        }
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl ?? admin_asset('images/favicon.svg') }}">
    <link rel="preload" href="{{ admin_asset('fonts/boxicons.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ admin_asset('fonts/materialdesignicons-webfonte910.woff2') }}" as="font" type="font/woff2" crossorigin>
    @include('admin.layouts.include.header.css-link')
    @vite(['resources/css/theme-customizer.css'])
    <script>
        (function(){try{var s=JSON.parse(localStorage.getItem('admin_theme_settings'));if(s&&s.theme)document.documentElement.setAttribute('data-bs-theme',s.theme);if(s&&s.direction)document.documentElement.setAttribute('dir',s.direction);}catch(e){}})();
    </script>
</head>
@section('body')
    <body data-sidebar="dark">
        @show
        <div id="layout-wrapper">
            @include('admin.layouts.include.header.navbar')
            @include('admin.layouts.include.sidebar.left-sidebar')
            <div class="main-content">
                <div class="page-content">
                    <div class="container-fluid">

                        <x-breadcrumb />
                        @yield('content')

                    </div>
                </div>
            </div>
        </div>
        @include('admin.layouts.include.others.theme-customizer')
        @include('admin.layouts.include.footer.script-link')
        <script src="{{ admin_asset('js/theme-customizer.js') }}"></script>
    </body>
</html>
