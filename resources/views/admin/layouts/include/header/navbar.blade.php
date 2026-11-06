<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            @php
                $logoVariant = setting('admin_logo_variant', 'light');
                $logoSettingKey = $logoVariant === 'dark' ? 'site_logo_dark' : 'site_logo_light';
                $logoMediaId = setting($logoSettingKey);
                $logoUrl = null;
                if ($logoMediaId) {
                    $logoMedia = \App\Models\Media::find($logoMediaId);
                    $logoUrl = $logoMedia ? \Illuminate\Support\Facades\Storage::disk($logoMedia->disk)->url($logoMedia->path) : null;
                }

                $smallLogoMediaId = setting('site_logo_small');
                $smallLogoUrl = null;
                if ($smallLogoMediaId) {
                    $smallLogoMedia = \App\Models\Media::find($smallLogoMediaId);
                    $smallLogoUrl = $smallLogoMedia ? \Illuminate\Support\Facades\Storage::disk($smallLogoMedia->disk)->url($smallLogoMedia->path) : null;
                }

                $siteName = setting('site_name', 'Admin');
            @endphp
            <div class="navbar-brand-box">
                <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ $smallLogoUrl ?? admin_asset('images/dark_logo.svg') }}" alt="{{ $siteName }}" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ $logoUrl ?? admin_asset('images/dark_logo.svg') }}" alt="{{ $siteName }}" height="17">
                    </span>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ $smallLogoUrl ?? admin_asset('images/light_logo.svg') }}" alt="{{ $siteName }}" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ $logoUrl ?? admin_asset('images/light_logo.svg') }}" alt="{{ $siteName }}" height="19">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

        </div>

        <div class="d-flex">

            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                     aria-labelledby="page-header-search-dropdown">

                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ..." aria-label="Recipient's username">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if(setting('multi_language') === 'on')
                @php
                    $languages = [
                        'en' => ['name' => 'English', 'flag' => 'us.jpg'],
                        'bn' => ['name' => 'বাংলা', 'flag' => 'bd.png'],
                        'ar' => ['name' => 'العربية', 'flag' => 'sa.png'],
                        'es' => ['name' => 'Español', 'flag' => 'es.png'],
                    ];
                    $currentLocale = app()->getLocale();
                    $currentLang = $languages[$currentLocale] ?? $languages['en'];
                @endphp
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item waves-effect"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="{{ admin_asset('images/flags/' . $currentLang['flag']) }}" alt="{{ $currentLang['name'] }}" height="16">
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach($languages as $code => $lang)
                            <a href="{{ route('lang.switch', $code) }}" class="dropdown-item notify-item {{ $currentLocale === $code ? 'active' : '' }}">
                                <img src="{{ admin_asset('images/flags/' . $lang['flag']) }}" alt="{{ $lang['name'] }}" class="me-1" height="12">
                                <span class="align-middle">{{ $lang['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="d-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" id="theme-customizer-toggle" title="Theme Settings">
                    <i class="bx bx-cog"></i>
                </button>
            </div>

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="fullscreen">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>

            @php
                $navNotifications = auth()->user()->unreadNotifications()->latest()->take(5)->get();
                $navUnreadCount = auth()->user()->unreadNotifications()->count();
                $typeIcons = ['info' => 'bx-info-circle', 'success' => 'bx-check-circle', 'warning' => 'bx-error', 'danger' => 'bx-shield-x'];
                $typeColors = ['info' => 'info', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
            @endphp
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="bx bx-bell {{ $navUnreadCount > 0 ? 'bx-tada' : '' }}"></i>
                    @if($navUnreadCount > 0)
                        <span class="badge bg-danger rounded-pill" id="nav-noti-count">{{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                     aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0">@lang('notification.notifications_label')</h6>
                            </div>
                            @if($navUnreadCount > 0)
                                <div class="col-auto">
                                    <a href="#!" class="small" id="nav-mark-all-read">@lang('notification.mark_all_as_read')</a>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div style="max-height: 230px; overflow-y: auto;" id="nav-noti-list">
                        @forelse($navNotifications as $notification)
                            @php $nType = $notification->data['type'] ?? 'info'; @endphp
                            <a href="{{ $notification->data['url'] ?? route('notifications.index') }}" class="text-reset notification-item nav-noti-click" data-id="{{ $notification->id }}">
                                <div class="d-flex">
                                    <div class="avatar-xs me-3">
                                        <span class="avatar-title bg-{{ $typeColors[$nType] ?? 'info' }} rounded-circle font-size-16">
                                            <i class="bx {{ $typeIcons[$nType] ?? 'bx-info-circle' }}"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $notification->data['title'] ?? '' }}</h6>
                                        <div class="font-size-12 text-muted">
                                            <p class="mb-1">{{ Str::limit($notification->data['message'] ?? '', 50) }}</p>
                                            <p class="mb-0"><i class="mdi mdi-clock-outline"></i> {{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-3 text-center text-muted" id="nav-noti-empty">
                                <i class="bx bx-bell-off font-size-20"></i>
                                <p class="mb-0 mt-1">@lang('notification.no_notifications')</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="p-2 border-top d-grid">
                        <a class="btn btn-sm btn-link font-size-14 text-center" href="{{ route('notifications.index') }}">
                            <i class="mdi mdi-arrow-right-circle me-1"></i> @lang('notification.view_all')
                        </a>
                    </div>
                </div>
            </div>
            @php
                $authUser = auth()->user();
                $authAvatarUrl = $authUser->getFirstMediaUrl('avatar');
            @endphp
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    @if($authAvatarUrl)
                        <img class="rounded-circle header-profile-user" src="{{ $authAvatarUrl }}" alt="Avatar">
                    @else
                        <span class="rounded-circle header-profile-user d-inline-flex align-items-center justify-content-center bg-light" style="width:32px; height:32px;">
                            <i class="bx bx-user font-size-20 text-secondary"></i>
                        </span>
                    @endif
                    <span class="d-none d-xl-inline-block ms-1">{{ $authUser->name }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.index') }}"><i class="bx bx-user font-size-16 align-middle me-1"></i> {{ __('profile.title') }}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}">
                        <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> {{ __('menu.logout') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark All Read from navbar
    var markAllBtn = document.getElementById('nav-mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('{{ route("notifications.mark_all_read") }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
            }).then(function() { location.reload(); });
        });
    }

    function updateBellBadge(count) {
        var badge = document.getElementById('nav-noti-count');
        var bell = document.querySelector('#page-header-notifications-dropdown .bx-bell');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.id = 'nav-noti-count';
                badge.className = 'badge bg-danger rounded-pill';
                document.getElementById('page-header-notifications-dropdown').appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
            if (bell) bell.classList.add('bx-tada');
        } else {
            if (badge) badge.remove();
            if (bell) bell.classList.remove('bx-tada');
        }
    }

    // Click notification → mark as read, then navigate
    document.addEventListener('click', function(e) {
        var item = e.target.closest('.nav-noti-click');
        if (item) {
            e.preventDefault();
            var id = item.getAttribute('data-id');
            var href = item.getAttribute('href');
            fetch('{{ url("admin/notifications") }}/' + id + '/read', {
                method: 'PATCH',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
            }).finally(function() {
                window.location.href = href;
            });
        }
    });

    // Real-time via Laravel Echo (Reverb WebSocket)
    var notiCount = {{ $navUnreadCount }};
    if (typeof window.Echo !== 'undefined') {
        window.Echo.private('notifications.{{ auth()->id() }}')
            .listen('.new-notification', function(data) {
                notiCount++;
                updateBellBadge(notiCount);
                if (typeof toastr !== 'undefined') {
                    var t = data.type === 'danger' ? 'error' : (data.type || 'info');
                    toastr[t](data.message, data.title);
                }
            });
    } else {
        // Fallback: poll every 30s if Echo is not available
        setInterval(function() {
            fetch('{{ route("notifications.poll") }}', {headers: {'Accept': 'application/json'}})
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.count > notiCount && d.notifications[0] && typeof toastr !== 'undefined') {
                        var t = d.notifications[0].type === 'danger' ? 'error' : (d.notifications[0].type || 'info');
                        toastr[t](d.notifications[0].message, d.notifications[0].title);
                    }
                    notiCount = d.count;
                    updateBellBadge(d.count);
                }).catch(function() {});
        }, 30000);
    }
});
</script>
