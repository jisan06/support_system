@extends('errors.layout')

@section('title', __('errors.404_title'))

@section('content')
    <div class="error-icon text-warning">
        <i class="bx bx-search-alt"></i>
    </div>
    <div class="error-code text-warning">404</div>
    <h4 class="error-title">{{ __('errors.404_title') }}</h4>
    <p class="error-message">{{ __('errors.404_message') }}</p>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
        <i class="bx bx-home me-1"></i> {{ __('errors.back_home') }}
    </a>
@endsection
