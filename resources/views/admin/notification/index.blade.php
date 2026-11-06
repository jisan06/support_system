@extends('admin.layouts.master')
@section('title', trans_choice('notification.notification', 2))
@section('datatable_page', true)
@section('content')
    <div class="row item-list-wrap">
        <div class="col-12">
            <div class="card">
                <x-table.header
                    :title="__('notification.title_page')"
                    icon="bx bx-bell"
                />
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        @if($unread_count > 0)
                            <button type="button" class="btn btn-soft-success btn-sm" id="markAllReadBtn">
                                <i class="bx bx-check-double me-1"></i> @lang('notification.mark_all_as_read')
                            </button>
                        @endif
                        <button type="button" class="btn btn-soft-info btn-sm" id="sendTestBtn">
                            <i class="bx bx-send me-1"></i> @lang('notification.send_test')
                        </button>
                    </div>

                    {{ $dataTable->table() }}
                </div>
            </div>
        </div>

        {{-- Developer Guide --}}
        <div class="col-12 mt-2">
            <div class="card">
                <div class="card-header" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#devGuide">
                    <h6 class="mb-0">
                        <i class="bx bx-code-alt me-1"></i> @lang('notification.developer_guide_title')
                        <i class="bx bx-chevron-down float-end mt-1"></i>
                    </h6>
                </div>
                <div class="collapse" id="devGuide">
                    <div class="card-body">

                        {{-- Overview --}}
                        <h6><i class="bx bx-info-circle me-1"></i> What is this?</h6>
                        <p class="text-muted">A ready-to-use notification system for your admin panel. It stores notifications in the database, shows them in the bell dropdown with real-time updates, and provides a simple one-line helper to send notifications from anywhere in your code.</p>

                        <hr>

                        {{-- How it works --}}
                        <h6><i class="bx bx-cog me-1"></i> How it works</h6>
                        <ul class="text-muted mb-3">
                            <li><strong>Database</strong> — Notifications are stored in Laravel's built-in <code>notifications</code> table</li>
                            <li><strong>Bell icon</strong> — Shows unread count badge + latest 5 in dropdown, updates via polling (30s)</li>
                            <li><strong>Real-time (optional)</strong> — Enable Laravel Reverb for instant WebSocket push + toast popups</li>
                            <li><strong>Click to read</strong> — Clicking a notification in the dropdown marks it as read automatically</li>
                        </ul>

                        <hr>

                        {{-- Built-in features --}}
                        <h6><i class="bx bx-check-shield me-1"></i> What's already built-in</h6>
                        <ul class="text-muted mb-3">
                            <li><code>notify()</code> global helper — send notifications from anywhere</li>
                            <li>Bell dropdown with unread count, mark as read, mark all as read</li>
                            <li>This notification management page (list, delete, bulk delete)</li>
                            <li>Security alerts — failed login (3+ attempts) and account lockout automatically notify admins</li>
                            <li>4 notification types: <span class="badge bg-info">info</span> <span class="badge bg-success">success</span> <span class="badge bg-warning">warning</span> <span class="badge bg-danger">danger</span></li>
                            <li>Multi-language support (EN, BN, AR, ES)</li>
                        </ul>

                        <hr>

                        {{-- Usage --}}
                        <h6><i class="bx bx-code-alt me-1"></i> Usage</h6>
<pre class="p-3 rounded" style="font-size:13px; background:#2d2d2d; color:#f8f8f2;"><code>// Send to a single user
notify($user, 'Order Placed', 'Order #123 has been placed.', 'success', '/orders/123');

// Send to multiple users
notify(User::role('admin')->get(), 'New Signup', 'A new user just registered.', 'info');

// Send with warning type
notify($user, 'Disk Warning', 'Storage is almost full.', 'warning');

// Minimal (defaults to type: info, no url)
notify($user, 'Welcome', 'Thanks for joining!');

// Send to all users
notify(User::all(), 'Maintenance', 'System will be down at 2AM.', 'danger');</code></pre>

                        <hr>

                        {{-- Key files --}}
                        <h6><i class="bx bx-folder me-1"></i> Key files</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-3">
                                <thead><tr><th>File</th><th>Purpose</th></tr></thead>
                                <tbody>
                                    <tr><td><code>app/Helpers/helpers.php</code></td><td><code>notify()</code> global helper function</td></tr>
                                    <tr><td><code>app/Notifications/GeneralNotification.php</code></td><td>Notification class (database channel)</td></tr>
                                    <tr><td><code>app/Events/NewNotification.php</code></td><td>Broadcast event for real-time push</td></tr>
                                    <tr><td><code>app/Services/NotificationService.php</code></td><td>Business logic (read, delete, poll)</td></tr>
                                    <tr><td><code>app/Listeners/SecurityNotificationListener.php</code></td><td>Account lockout listener</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        {{-- What you can extend --}}
                        <h6><i class="bx bx-extension me-1"></i> What you can extend</h6>
                        <ul class="text-muted mb-3">
                            <li><strong>Add your own triggers</strong> — Call <code>notify()</code> in your controllers, observers, events, or jobs</li>
                            <li><strong>Email channel</strong> — Add <code>'mail'</code> to the <code>via()</code> method in <code>GeneralNotification.php</code> and implement <code>toMail()</code></li>
                            <li><strong>Custom notification classes</strong> — Create new classes in <code>app/Notifications/</code> for specific use cases (e.g. <code>OrderNotification</code>)</li>
                            <li><strong>Notification preferences</strong> — Add a user settings page to let users choose which notifications they receive</li>
                            <li><strong>Queue notifications</strong> — Add <code>ShouldQueue</code> to <code>GeneralNotification</code> for async processing</li>
                        </ul>

                        <hr>

                        {{-- Real-time setup --}}
                        <h6><i class="bx bx-broadcast me-1"></i> Enable real-time (Laravel Reverb)</h6>
                        <p class="text-muted mb-2">By default, notifications use polling (every 30s). To enable instant WebSocket push:</p>
<pre class="p-3 rounded" style="font-size:13px; background:#2d2d2d; color:#f8f8f2;"><code># Step 1: Update .env
BROADCAST_DRIVER=reverb
VITE_REVERB_ENABLED=true

# Step 2: Rebuild frontend
npm run build

# Step 3: Start the WebSocket server
php artisan reverb:start</code></pre>
                        <p class="text-muted mt-2 mb-0"><small>Without Reverb running, the system automatically falls back to polling. No errors, no setup needed.</small></p>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
@endpush

@push('script_links')
    @vite(['resources/js/app.js'])
    @include('components.modal.confirm')
    @include('admin.layouts.include.others.delete')
    {{ $dataTable->scripts() }}

    <script>
        $(function() {
            var typeIcons = {info:'bx-info-circle', success:'bx-check-circle', warning:'bx-error', danger:'bx-shield-x'};
            var typeColors = {info:'info', success:'success', warning:'warning', danger:'danger'};

            function refreshBell() {
                $.getJSON('{{ route("notifications.poll") }}', function(data) {
                    // Update badge
                    var badge = $('#nav-noti-count');
                    var bell = $('#page-header-notifications-dropdown .bx-bell');
                    if (data.count > 0) {
                        if (!badge.length) {
                            $('#page-header-notifications-dropdown').append('<span class="badge bg-danger rounded-pill" id="nav-noti-count"></span>');
                            badge = $('#nav-noti-count');
                        }
                        badge.text(data.count > 99 ? '99+' : data.count);
                        bell.addClass('bx-tada');
                    } else {
                        badge.remove();
                        bell.removeClass('bx-tada');
                    }

                    // Rebuild dropdown list
                    var list = $('#nav-noti-list');
                    list.html('');
                    if (data.notifications.length === 0) {
                        list.html('<div class="p-3 text-center text-muted"><i class="bx bx-bell-off font-size-20"></i><p class="mb-0 mt-1">@lang("notification.no_notifications")</p></div>');
                    } else {
                        $.each(data.notifications, function(i, n) {
                            var icon = typeIcons[n.type] || 'bx-info-circle';
                            var color = typeColors[n.type] || 'info';
                            var href = n.url || '{{ route("notifications.index") }}';
                            list.append(
                                '<a href="' + href + '" class="text-reset notification-item nav-noti-click" data-id="' + n.id + '">' +
                                    '<div class="d-flex">' +
                                        '<div class="avatar-xs me-3"><span class="avatar-title bg-' + color + ' rounded-circle font-size-16"><i class="bx ' + icon + '"></i></span></div>' +
                                        '<div class="flex-grow-1"><h6 class="mb-1">' + n.title + '</h6><div class="font-size-12 text-muted"><p class="mb-1">' + n.message.substring(0, 50) + '</p><p class="mb-0"><i class="mdi mdi-clock-outline"></i> ' + n.created_at + '</p></div></div>' +
                                    '</div>' +
                                '</a>'
                            );
                        });
                    }
                });
            }

            // Mark All as Read
            $('#markAllReadBtn').on('click', function() {
                $.post('{{ route("notifications.mark_all_read") }}', {_token: '{{ csrf_token() }}'}, function(res) {
                    toastr.success(res.message);
                    $('.dataTable').DataTable().ajax.reload(null, false);
                    $('#markAllReadBtn').fadeOut();
                    refreshBell();
                });
            });

            // Send Test Notification
            $('#sendTestBtn').on('click', function() {
                $.post('{{ route("notifications.send_test") }}', {_token: '{{ csrf_token() }}'}, function(res) {
                    toastr.success(res.message);
                    $('.dataTable').DataTable().ajax.reload(null, false);
                    refreshBell();
                });
            });

            // Refresh bell after any DataTable reload (covers delete, bulk delete, etc.)
            $('.dataTable').on('xhr.dt', function() {
                setTimeout(refreshBell, 300);
            });

            // Mark Single as Read
            $(document).on('click', '.mark-read-btn', function() {
                var btn = $(this);
                $.ajax({
                    url: btn.data('route'),
                    type: 'PATCH',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(res) {
                        toastr.success(res.message);
                        $('.dataTable').DataTable().ajax.reload(null, false);
                        refreshBell();
                    }
                });
            });
        });
    </script>
@endpush
