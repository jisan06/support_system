<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ setting('site_name', config('app.name')) }}{{ isset($title) ? ' - ' . $title : '' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    @php
                        $authLogoVariant = setting('auth_logo_variant', 'dark');
                        $authLogoKey = $authLogoVariant === 'dark' ? 'site_logo_dark' : 'site_logo_light';
                        $authLogoMediaId = setting($authLogoKey);
                        $authLogoUrl = null;
                        if ($authLogoMediaId) {
                            $authLogoMedia = \App\Models\Media::find($authLogoMediaId);
                            $authLogoUrl = $authLogoMedia ? \Illuminate\Support\Facades\Storage::disk($authLogoMedia->disk)->url($authLogoMedia->path) : null;
                        }
                    @endphp
                    <img src="{{ $authLogoUrl ?? asset('backend/assets/images/dark_logo.svg') }}" alt="{{ setting('site_name', config('app.name')) }}" class="h-20 w-auto">
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
