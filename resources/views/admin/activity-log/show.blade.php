@extends('admin.layouts.master')
@section('title', __('activity_log.log_detail'))
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                {{ __('activity_log.log_detail') }}
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">{{ __('activity_log.description') }}</th>
                                <td>{{ $activity_log->description }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('activity_log.causer') }}</th>
                                <td>{{ $activity_log->causer_name }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('activity_log.event') }}</th>
                                <td>
                                    @php
                                        $colors = [
                                            'created'            => 'success',
                                            'updated'            => 'info',
                                            'deleted'            => 'warning',
                                            'restored'           => 'primary',
                                            'force_deleted'      => 'danger',
                                            'permission_updated' => 'secondary',
                                            'settings_updated'   => 'dark',
                                        ];
                                        $color = $colors[$activity_log->event] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ __('activity_log.' . $activity_log->event) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('activity_log.subject') }}</th>
                                <td>
                                    @if($activity_log->subject_type)
                                        {{ $activity_log->subject_label }} #{{ $activity_log->subject_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('activity_log.log_name') }}</th>
                                <td><span class="badge bg-light text-dark">{{ $activity_log->log_name }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('activity_log.date') }}</th>
                                <td>{{ $activity_log->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if($activity_log->properties && $activity_log->properties->count())
                    <div class="mt-4">
                        <h6 class="fw-bold">{{ __('activity_log.properties') }}</h6>

                        @if($activity_log->properties->has('old'))
                            <div class="mb-3">
                                <h6 class="text-muted">{{ __('activity_log.old_values') }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('activity_log.field') }}</th>
                                                <th>{{ __('activity_log.value') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($activity_log->properties['old'] as $key => $value)
                                                <tr>
                                                    <td><code>{{ $key }}</code></td>
                                                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if($activity_log->properties->has('attributes'))
                            <div class="mb-3">
                                <h6 class="text-muted">{{ __('activity_log.new_values') }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('activity_log.field') }}</th>
                                                <th>{{ __('activity_log.value') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($activity_log->properties['attributes'] as $key => $value)
                                                <tr>
                                                    <td><code>{{ $key }}</code></td>
                                                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- For custom properties (permissions, settings group, etc.) --}}
                        @php
                            $customKeys = $activity_log->properties->except(['old', 'attributes'])->keys();
                        @endphp
                        @if($customKeys->count())
                            <div class="mb-3">
                                <h6 class="text-muted">{{ __('activity_log.details') }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('activity_log.field') }}</th>
                                                <th>{{ __('activity_log.value') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($customKeys as $key)
                                                <tr>
                                                    <td><code>{{ $key }}</code></td>
                                                    <td>{{ is_array($activity_log->properties[$key]) ? json_encode($activity_log->properties[$key], JSON_PRETTY_PRINT) : $activity_log->properties[$key] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i>
                    @lang('activity_log.back_to_list')
                </a>
            </div>
        </div>
    </div>
</div>
@endsection