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
    <link href="{{ admin_asset('css/customer.css') }}?v={{ file_exists(public_path('backend/assets/css/customer.css')) ? filemtime(public_path('backend/assets/css/customer.css')) : '1.0' }}" rel="stylesheet" type="text/css" />

    @stack('styles')
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
                        <a href="{{ route('customer.tickets.index') }}" class="nav-link px-3 py-2 rounded {{ request()->routeIs('customer.tickets.index', 'customer.tickets.show') ? 'text-primary fw-semibold bg-light' : 'text-dark' }}" title="My Tickets">
                            <i class="bx bx-receipt me-1 align-middle"></i> My Tickets
                        </a>
                        <a href="{{ route('customer.tickets.create') }}" class="nav-link px-3 py-2 rounded {{ request()->routeIs('customer.tickets.create') ? 'text-primary fw-semibold bg-light' : 'text-dark' }}" title="Create Ticket">
                            <i class="bx bx-plus-circle me-1 align-middle"></i> Create Ticket
                        </a>
                        <a href="{{ route('customer.profile.edit') }}" class="nav-link px-3 py-2 rounded {{ request()->routeIs('customer.profile.*') ? 'text-primary fw-semibold bg-light' : 'text-dark' }}" title="Profile">
                            <i class="bx bx-user me-1 align-middle"></i> Profile
                        </a>
                        <a href="{{ route('customer.notifications.index') }}" class="nav-link px-3 py-2 rounded position-relative {{ request()->routeIs('customer.notifications.*') ? 'text-primary fw-semibold bg-light' : 'text-dark' }}" title="Notifications">
                            <i class="bx bx-bell me-1 align-middle"></i> Notifications
                            @php
                                $unreadHeaderCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                            @endphp
                            @if($unreadHeaderCount > 0)
                                <span class="badge bg-danger rounded-pill font-size-11 align-middle ms-1">{{ $unreadHeaderCount > 99 ? '99+' : $unreadHeaderCount }}</span>
                            @endif
                        </a>
                    </nav>
                </div>

                {{-- Customer Account Info & Logout --}}
                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <a href="{{ route('customer.profile.edit') }}" class="d-flex align-items-center me-3 border-end pe-3 text-decoration-none text-reset" title="My Profile">
                        @php
                            $userAvatarUrl = auth()->user()->getFirstMediaUrl('avatar');
                        @endphp
                        @if($userAvatarUrl)
                            <img src="{{ $userAvatarUrl }}" alt="{{ auth()->user()->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                        @else
                            <div class="avatar-sm me-2" style="width: 32px; height: 32px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                                <i class="bx bx-user text-muted font-size-18"></i>
                            </div>
                        @endif
                        <div class="text-start">
                            <div class="fw-semibold font-size-13 text-dark text-truncate" style="max-width: 160px;">{{ auth()->user()->name }}</div>
                            <small class="text-muted font-size-11 text-truncate d-block" style="max-width: 160px;">{{ auth()->user()->email }}</small>
                        </div>
                    </a>

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

    @stack('scripts')
</body>
</html>

