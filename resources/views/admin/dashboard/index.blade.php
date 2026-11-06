@extends('admin.layouts.master')
@section('title', 'Dashboard')
@section('content')

{{-- Update Notification --}}
@if($updateInfo && $updateInfo['update_available'])
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bx bx-revision font-size-22 me-2"></i>
            <div class="flex-grow-1">
                <strong>{{ __('update.new_version_available', ['version' => $updateInfo['latest_version']]) }}</strong>
                {{ __('update.you_are_running', ['version' => $updateInfo['current_version']]) }}
            </div>
            <a href="{{ route('update.index') }}" class="btn btn-sm btn-warning ms-3">
                <i class="bx bx-right-arrow-alt me-1"></i> {{ __('update.view_details') }}
            </a>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
@endif

{{-- Stat Widgets --}}
<div class="row">
    @foreach($widgets as $widget)
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium mb-2">{{ $widget['label'] }}</p>
                            <h4 class="mb-0">{{ number_format($widget['value']) }}</h4>
                        </div>
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-{{ $widget['color'] }} align-self-center">
                            <span class="avatar-title">
                                <i class="{{ $widget['icon'] }} font-size-24"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="{{ route($widget['route']) }}" class="text-{{ $widget['color'] }} font-size-12">
                            View All <i class="mdi mdi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Charts Row 1: Line + Donut --}}
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-line-chart me-1"></i> User Registrations (Last 12 Months)</h4>
            </div>
            <div class="card-body">
                <div id="userRegistrationChart" style="height: 320px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-pie-chart-alt-2 me-1"></i> Users by Status</h4>
            </div>
            <div class="card-body">
                <div id="userStatusChart" style="height: 320px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row 2: Area + Bar --}}
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-trending-up me-1"></i> Activity Trend (Last 30 Days)</h4>
            </div>
            <div class="card-body">
                <div id="activityTrendChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-bar-chart me-1"></i> Activities by Event</h4>
            </div>
            <div class="card-body">
                <div id="activityEventChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-history me-1"></i> Recent Activity</h4>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if(count($recentActivities) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">User</th>
                                    <th>Event</th>
                                    <th>Description</th>
                                    <th class="text-end pe-3">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentActivities as $activity)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-medium">{{ $activity['causer'] }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $colors = [
                                                    'created' => 'success', 'updated' => 'info',
                                                    'deleted' => 'warning', 'restored' => 'primary',
                                                    'force_deleted' => 'danger',
                                                ];
                                                $color = $colors[$activity['event']] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst($activity['event']) }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $activity['description'] }}</small></td>
                                        <td class="text-end pe-3">
                                            <small class="text-muted" title="{{ $activity['date'] }}">{{ $activity['time'] }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-info-circle font-size-24"></i>
                        <p class="mt-2 mb-0">No recent activity.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('script_links')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var textColor = isDark ? '#a6b0cf' : '#495057';
    var gridColor = isDark ? '#32394e' : '#f1f1f1';

    // User Registration - Area Chart
    new ApexCharts(document.querySelector("#userRegistrationChart"), {
        series: [{
            name: 'Users',
            data: @json($userChart['data'])
        }],
        chart: { type: 'area', height: 320, toolbar: { show: false } },
        colors: ['#556ee6'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 }
        },
        xaxis: {
            categories: @json($userChart['labels']),
            labels: { style: { colors: textColor } }
        },
        yaxis: {
            labels: { style: { colors: textColor } },
            min: 0
        },
        grid: { borderColor: gridColor },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    }).render();

    // Users by Status - Donut Chart
    new ApexCharts(document.querySelector("#userStatusChart"), {
        series: @json($userStatusChart['data']),
        chart: { type: 'donut', height: 320 },
        labels: @json($userStatusChart['labels']),
        colors: ['#2ab57d', '#e74c3c', '#f1b44c', '#50a5f1'],
        legend: {
            position: 'bottom',
            labels: { colors: textColor }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: { show: true, label: 'Total', color: textColor }
                    }
                }
            }
        },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    }).render();

    // Activity Trend - Area Chart
    new ApexCharts(document.querySelector("#activityTrendChart"), {
        series: [{
            name: 'Activities',
            data: @json($activityTrend['data'])
        }],
        chart: { type: 'area', height: 300, toolbar: { show: false }, sparkline: { enabled: false } },
        colors: ['#50a5f1'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 }
        },
        xaxis: {
            categories: @json($activityTrend['labels']),
            labels: { style: { colors: textColor }, rotate: -45, rotateAlways: false },
            tickAmount: 10
        },
        yaxis: {
            labels: { style: { colors: textColor } },
            min: 0
        },
        grid: { borderColor: gridColor },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    }).render();

    // Activities by Event - Bar Chart
    new ApexCharts(document.querySelector("#activityEventChart"), {
        series: [{
            name: 'Count',
            data: @json($activityEventChart['data'])
        }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#556ee6', '#2ab57d', '#f1b44c', '#e74c3c', '#50a5f1', '#7b5ea7'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
                distributed: true
            }
        },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        xaxis: {
            categories: @json($activityEventChart['labels']),
            labels: { style: { colors: textColor } }
        },
        yaxis: {
            labels: { style: { colors: textColor } }
        },
        legend: { show: false },
        grid: { borderColor: gridColor },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    }).render();
});
</script>
@endpush