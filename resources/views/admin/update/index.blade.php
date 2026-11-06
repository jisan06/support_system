@extends('admin.layouts.master')
@section('title', __('update.title'))
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Current Version --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title bg-primary rounded-circle">
                            <i class="bx bx-package font-size-24"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">{{ __('update.installed_version') }}</h5>
                        <p class="text-muted mb-0 font-size-16">
                            <strong>v{{ config('app-version.version', '1.0.0') }}</strong>
                            <span class="text-muted ms-2 font-size-13">&middot; Laravel {{ app()->version() }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if(!$enabled)
            {{-- Not Configured --}}
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bx bx-error font-size-22 me-2"></i>
                <div>
                    <strong>{{ __('update.not_configured') }}</strong>
                    <p class="mb-0 mt-1">{{ __('update.not_configured_hint') }}</p>
                </div>
            </div>
        @else
            {{-- Update Status --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="bx bx-revision me-1"></i> {{ __('update.update_status') }}</h4>
                    <form action="{{ route('update.check') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bx bx-refresh me-1"></i>{{ __('update.check_now') }}
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    @if($updateInfo['error'])
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <span class="avatar-title bg-light rounded-circle">
                                    <i class="bx bx-error-circle font-size-24 text-warning"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1 text-warning">{{ __('update.check_failed') }}</h5>
                                <p class="text-muted mb-0">{{ $updateInfo['error'] }}</p>
                            </div>
                        </div>
                    @elseif($updateInfo['update_available'])
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <span class="avatar-title bg-warning rounded-circle">
                                    <i class="bx bx-up-arrow-alt font-size-24"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1 text-warning">{{ __('update.new_version_available', ['version' => $updateInfo['latest_version']]) }}</h5>
                                <p class="text-muted mb-0">
                                    {{ __('update.you_are_running', ['version' => $updateInfo['current_version']]) }}
                                </p>
                            </div>
                        </div>

                        <hr>

                        <h6 class="text-muted mb-3"><i class="bx bx-list-check me-1"></i> {{ __('update.how_to_update') }}</h6>
                        <ol class="text-muted mb-0">
                            <li class="mb-2">{{ __('update.step_backup') }}</li>
                            <li class="mb-2">{{ __('update.step_download') }}</li>
                            <li class="mb-2">{!! __('update.step_replace') !!}</li>
                            <li class="mb-2">{!! __('update.step_migrate') !!}</li>
                            <li class="mb-2">{!! __('update.step_build') !!}</li>
                            <li>{!! __('update.step_clear') !!}</li>
                        </ol>
                    @else
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="bx bx-check font-size-24"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1 text-success">{{ __('update.up_to_date') }}</h5>
                                <p class="text-muted mb-0">{{ __('update.running_latest') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>

@endsection
