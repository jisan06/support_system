<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ setting('site_name', 'Admin') }}</title>
    @php
        $faviconUrl = null;
        if ($faviconId = setting('favicon')) {
            $faviconMedia = \App\Models\Media::find($faviconId);
            $faviconUrl = $faviconMedia ? \Illuminate\Support\Facades\Storage::disk($faviconMedia->disk)->url($faviconMedia->path) : null;
        }
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl ?? asset('backend/assets/images/favicon.ico') }}">
    <link href="{{ asset('backend/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .error-wrapper {
            max-width: 540px;
            width: 100%;
            padding: 1rem;
            text-align: center;
        }
        .error-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, .08);
            padding: 3rem 2rem;
        }
        .error-code {
            font-size: 100px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 1.5rem;
        }
        .error-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #343a40;
        }
        .error-message {
            color: #74788d;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <div class="error-card">
            @yield('content')
        </div>
    </div>
</body>
</html>
