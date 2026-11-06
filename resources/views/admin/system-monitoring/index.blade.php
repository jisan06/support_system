@extends('admin.layouts.master')
@section('title', __('system_monitoring.title'))
@section('content')

{{-- Version Info --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-info-circle me-1"></i> {{ __('system_monitoring.version_info') }}</h4>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title bg-primary rounded-circle">
                            <i class="bx bx-package font-size-24"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">{{ __('system_monitoring.current_version') }}: <strong>v{{ config('app-version.version', '1.0.0') }}</strong></h5>
                        <p class="text-muted mb-0">Laravel {{ app()->version() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Disk Usage + DB Size + Log Size + Environment --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">{{ __('system_monitoring.disk_usage') }}</p>
                        <h4 class="mb-0">{{ $disk['percentage'] }}%</h4>
                        <small class="text-muted">{{ $disk['used'] }} / {{ $disk['total'] }}</small>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-primary align-self-center">
                        <span class="avatar-title"><i class="bx bx-hdd font-size-24"></i></span>
                    </div>
                </div>
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar {{ $disk['percentage'] > 90 ? 'bg-danger' : ($disk['percentage'] > 70 ? 'bg-warning' : 'bg-success') }}"
                         style="width:{{ $disk['percentage'] }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">{{ __('system_monitoring.database') }}</p>
                        <h4 class="mb-0">{{ $database['size'] }}</h4>
                        <small class="text-muted">{{ $database['tables'] }} tables &middot; {{ $database['driver'] }}</small>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-success align-self-center">
                        <span class="avatar-title"><i class="bx bx-data font-size-24"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">{{ __('system_monitoring.log_file_size') }}</p>
                        <h4 class="mb-0">{{ $logs['size'] }}</h4>
                        <small class="text-muted">laravel.log</small>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-warning align-self-center">
                        <span class="avatar-title"><i class="bx bx-file font-size-24"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Environment</p>
                        <h4 class="mb-0">{{ config('app.env') }}</h4>
                        <small class="text-muted">Laravel {{ app()->version() }}</small>
                    </div>
                    <div class="mini-stat-icon avatar-sm rounded-circle bg-info align-self-center">
                        <span class="avatar-title"><i class="bx bx-server font-size-24"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Server Info --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-chip me-1"></i> {{ __('system_monitoring.server_info') }}</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <tbody>
                            @foreach($server as $key => $value)
                                <tr>
                                    <th style="width:45%;" class="ps-3">{{ str_replace('_', ' ', ucwords($key, '_')) }}</th>
                                    <td><code>{{ $value }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Application Health --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-heart me-1"></i> {{ __('system_monitoring.laravel_health') }}</h4>
                <form action="{{ route('system-monitoring.clear-cache') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="bx bx-trash me-1"></i>{{ __('system_monitoring.clear_cache') }}
                    </button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <tbody>
                            @foreach($health as $item)
                                <tr>
                                    <th style="width:45%;" class="ps-3">{{ $item['label'] }}</th>
                                    <td><span class="badge bg-{{ $item['status'] }}">{{ $item['value'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Storage Usage --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="bx bx-folder me-1"></i> {{ __('system_monitoring.storage_usage') }}</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('system_monitoring.folder') }}</th>
                                <th>Path</th>
                                <th class="text-end pe-3">{{ __('system_monitoring.size') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($storage as $item)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $item['label'] }}</td>
                                    <td><code class="text-muted">{{ $item['path'] }}</code></td>
                                    <td class="text-end pe-3">{{ $item['size'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Database - Largest Tables --}}
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="bx bx-table me-1"></i> {{ __('system_monitoring.largest_tables') }}
                    <small class="text-muted ms-2">{{ $database['database'] }} ({{ $database['version'] }})</small>
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('system_monitoring.table_name') }}</th>
                                <th class="text-end">{{ __('system_monitoring.rows') }}</th>
                                <th class="text-end pe-3">{{ __('system_monitoring.size') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($database['largest_tables'] as $table)
                                <tr>
                                    <td class="ps-3"><code>{{ $table->table_name ?? $table->TABLE_NAME ?? 'N/A' }}</code></td>
                                    <td class="text-end">{{ number_format($table->table_rows ?? $table->TABLE_ROWS ?? 0) }}</td>
                                    <td class="text-end pe-3">{{ $table->size_mb ?? $table->SIZE_MB ?? 0 }} MB</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No tables found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Logs --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-error-circle me-1"></i> {{ __('system_monitoring.recent_logs') }}</h4>
                <form action="{{ route('system-monitoring.clear-logs') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bx bx-trash me-1"></i>{{ __('system_monitoring.clear_logs') }}
                    </button>
                </form>
            </div>
            <div class="card-body p-0">
                @if(count($logs['entries']) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:160px;">{{ __('system_monitoring.date') }}</th>
                                    <th style="width:90px;">{{ __('system_monitoring.level') }}</th>
                                    <th>{{ __('system_monitoring.message') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs['entries'] as $entry)
                                    <tr>
                                        <td class="ps-3"><small>{{ $entry['date'] }}</small></td>
                                        <td>
                                            @php
                                                $colors = ['ERROR' => 'danger', 'WARNING' => 'warning', 'CRITICAL' => 'dark'];
                                            @endphp
                                            <span class="badge bg-{{ $colors[$entry['level']] ?? 'secondary' }}">{{ $entry['level'] }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $entry['message'] }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-check-circle font-size-24 text-success"></i>
                        <p class="mt-2 mb-0">{{ __('system_monitoring.no_errors') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection