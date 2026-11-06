@extends('admin.layouts.master')

@section('title', __('permission.no_access'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card mt-5">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bx bx-shield-x" style="font-size: 72px; color: #f46a6a;"></i>
                    </div>
                    <h4 class="text-danger mb-3">{{ __('permission.access_denied') }}</h4>
                    <p class="text-muted mb-4">{{ __('permission.no_access_message') }}</p>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                        <i class="bx bx-home me-1"></i> {{ __('permission.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
