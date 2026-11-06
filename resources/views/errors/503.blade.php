@extends('errors.layout')

@section('title', __('errors.503_title'))

@section('content')
    <div class="error-icon text-info">
        <i class="bx bx-wrench"></i>
    </div>
    <div class="error-code text-info">503</div>
    <h4 class="error-title">{{ __('errors.503_title') }}</h4>
    <p class="error-message">{{ __('errors.503_message') }}</p>
    <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="bx bx-revision me-1"></i> {{ __('errors.try_again') }}
    </a>
@endsection
