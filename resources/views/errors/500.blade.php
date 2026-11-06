@extends('errors.layout')

@section('title', __('errors.500_title'))

@section('content')
    <div class="error-icon text-danger">
        <i class="bx bx-error-circle"></i>
    </div>
    <div class="error-code text-danger">500</div>
    <h4 class="error-title">{{ __('errors.500_title') }}</h4>
    <p class="error-message">{{ __('errors.500_message') }}</p>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
        <i class="bx bx-home me-1"></i> {{ __('errors.back_home') }}
    </a>
@endsection
