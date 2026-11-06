@extends('errors.layout')

@section('title', __('errors.419_title'))

@section('content')
    <div class="error-icon text-secondary">
        <i class="bx bx-time-five"></i>
    </div>
    <div class="error-code text-secondary">419</div>
    <h4 class="error-title">{{ __('errors.419_title') }}</h4>
    <p class="error-message">{{ __('errors.419_message') }}</p>
    <a href="{{ url()->previous() }}" class="btn btn-primary">
        <i class="bx bx-revision me-1"></i> {{ __('errors.go_back') }}
    </a>
@endsection
