@extends('customer.layouts.master')
@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 bg-primary bg-gradient text-white shadow-sm" style="border-radius: 10px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold mb-1 text-white">Welcome back, {{ $customer->name }}!</h4>
                        <p class="mb-0 text-white-50 font-size-14">
                            Welcome to your customer support portal. Track your support requests and status updates.
                        </p>
                    </div>
                    <div class="d-none d-md-block text-white-50">
                        <i class="bx bx-support font-size-48" style="opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Summary Statistics Cards --}}
<div class="row g-3 mb-4">
    {{-- Total Tickets --}}
    <div class="col-md-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-13 text-uppercase fw-semibold">Total Tickets</span>
                        <h2 class="fw-bold text-dark my-2">{{ $counts['total'] }}</h2>
                        <small class="text-muted">All support requests submitted</small>
                    </div>
                    <div class="stat-icon bg-light text-primary">
                        <i class="bx bx-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Open Tickets --}}
    <div class="col-md-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-13 text-uppercase fw-semibold">Open Tickets</span>
                        <h2 class="fw-bold text-warning my-2">{{ $counts['open'] }}</h2>
                        <small class="text-muted">Awaiting staff response or in progress</small>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bx bx-time-five"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Resolved Tickets --}}
    <div class="col-md-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted font-size-13 text-uppercase fw-semibold">Resolved Tickets</span>
                        <h2 class="fw-bold text-success my-2">{{ $counts['resolved'] }}</h2>
                        <small class="text-muted">Successfully resolved issues</small>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bx bx-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Info Card --}}
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 8px;">
            <div class="card-body p-4 text-center text-muted">
                <div class="py-3">
                    <i class="bx bx-support font-size-36 text-primary mb-2"></i>
                    <h5 class="text-dark fw-semibold">Have a Question or Need Help?</h5>
                    <p class="mb-3 text-muted font-size-14" style="max-width: 550px; margin: 0 auto;">
                        Submit a new support ticket and our dedicated team will respond promptly within our guaranteed business hours.
                    </p>
                    <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary px-4 py-2">
                        <i class="bx bx-plus-circle me-1 align-middle"></i> Create Support Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

