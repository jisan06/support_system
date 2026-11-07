<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Customer Portal') | {{ setting('site_name', 'Support Portal') }}</title>

    @php
        $faviconUrl = null;
        if ($faviconId = setting('favicon')) {
            $faviconMedia = \App\Models\Media::find($faviconId);
            $faviconUrl = $faviconMedia ? \Illuminate\Support\Facades\Storage::disk($faviconMedia->disk)->url($faviconMedia->path) : null;
        }
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl ?? admin_asset('images/favicon.svg') }}">

    {{-- Core Project CSS (Bootstrap 5 & Icons) --}}
    <link href="{{ admin_asset('css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ admin_asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ admin_asset('css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        body {
            background-color: #f8f9fa;
            color: #495057;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .customer-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
        }
        .customer-main-content {
            flex: 1 0 auto;
            padding: 30px 0;
        }
        .customer-footer {
            flex-shrink: 0;
            background-color: #ffffff;
            border-top: 1px solid #e9ecef;
            padding: 15px 0;
        }
        .stat-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body>

    {{-- Customer Portal Header & Navigation --}}
    <header class="customer-navbar shadow-sm">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between py-2">
                {{-- Logo & Brand --}}
                <div class="d-flex align-items-center">
                    <a href="{{ route('customer.dashboard') }}" class="d-flex align-items-center text-dark text-decoration-none me-4">
                        <span class="avatar-title rounded-circle bg-primary text-white font-size-18 me-2" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="bx bx-support"></i>
                        </span>
                        <span class="fs-5 fw-bold text-primary">{{ setting('site_name', 'Support Portal') }}</span>
                    </a>

                    {{-- Navigation Links --}}
                    <nav class="d-flex align-items-center">
                        <a href="{{ route('customer.dashboard') }}" class="nav-link px-3 py-2 rounded {{ request()->routeIs('customer.dashboard') ? 'text-primary fw-semibold bg-light' : 'text-dark' }}">
                            <i class="bx bx-home-circle me-1 align-middle"></i> Dashboard
                        </a>
                        <a href="#" class="nav-link px-3 py-2 text-muted" title="My Tickets">
                            <i class="bx bx-receipt me-1 align-middle"></i> My Tickets
                        </a>
                        <a href="{{ route('customer.tickets.create') }}" class="nav-link px-3 py-2 rounded {{ request()->routeIs('customer.tickets.*') ? 'text-primary fw-semibold bg-light' : 'text-dark' }}" title="Create Ticket">
                            <i class="bx bx-plus-circle me-1 align-middle"></i> Create Ticket
                        </a>
                    </nav>
                </div>

                {{-- Customer Account Info & Logout --}}
                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <div class="d-flex align-items-center me-3 border-end pe-3">
                        <div class="avatar-sm me-2" style="width: 32px; height: 32px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                            <i class="bx bx-user text-muted font-size-18"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold font-size-13 text-dark text-truncate" style="max-width: 160px;">{{ auth()->user()->name }}</div>
                            <small class="text-muted font-size-11 text-truncate d-block" style="max-width: 160px;">{{ auth()->user()->email }}</small>
                        </div>
                    </div>

                    {{-- Logout (Standard HTML POST Form) --}}
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center">
                            <i class="bx bx-log-out me-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content Area --}}
    <main class="customer-main-content">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bx bx-check-circle me-1 align-middle font-size-16"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bx bx-error-circle me-1 align-middle font-size-16"></i>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="customer-footer">
        <div class="container text-center text-muted font-size-12">
            <span>&copy; {{ date('Y') }} {{ setting('site_name', 'Support Portal') }}. All rights reserved.</span>
        </div>
    </footer>

</body>
</html>

