@extends('customer.layouts.master')
@section('title', 'My Profile')

@push('styles')
<style>
    /* Customer Portal Profile Page Styling */
    .profile-hero-card {
        background: linear-gradient(135deg, #f8faff 0%, #eef2f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 24px;
    }
    .profile-panel-card {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(18, 38, 63, 0.04);
        overflow: hidden;
    }
    .profile-panel-header {
        padding: 18px 24px;
        border-bottom: 1px solid #f1f5f9;
        background-color: #fafbfd;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .profile-panel-body {
        padding: 24px;
    }
    .form-control-modern {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 14px;
        color: #1e293b;
        background-color: #ffffff;
        transition: all 0.2s ease-in-out;
        width: 100%;
    }
    .form-control-modern:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        outline: none;
    }
    .form-control-modern.is-invalid {
        border-color: #dc3545;
        background-color: #fff8f8;
    }
    .form-label-modern {
        font-weight: 600;
        font-size: 13.5px;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .form-hint {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }
    .avatar-preview-box {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .avatar-fallback-box {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }
    .meta-stat-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 13.5px;
    }
    .meta-stat-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .section-divider {
        border-top: 1px solid #f1f5f9;
        margin: 28px 0;
    }
</style>
@endpush

@section('content')
<div class="customer-profile-container">

    {{-- Breadcrumb & Hero Header --}}
    <div class="profile-hero-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0 font-size-13">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Profile Settings</li>
                    </ol>
                </nav>
                <h3 class="fw-bold text-dark mb-1">Account & Profile Settings</h3>
                <p class="text-muted font-size-14 mb-0">Manage your personal information, profile photo, and password security.</p>
            </div>
            <div>
                <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary d-inline-flex align-items-center px-3 py-2 rounded-3 font-size-13">
                    <i class="bx bx-arrow-back me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Global Alert Feedback --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-check-circle fs-4 me-2"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="bx bx-error-circle fs-4 me-2 mt-1"></i>
                <div>
                    <div class="fw-bold mb-1">Please correct the following errors:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li class="font-size-13">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- Left Column: User Summary Card --}}
        <div class="col-lg-4">
            <div class="profile-panel-card mb-4">
                <div class="profile-panel-body text-center p-4">
                    <div class="d-flex justify-content-center mb-3">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}" class="avatar-preview-box">
                        @else
                            <div class="avatar-fallback-box">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $customer->name }}</h5>
                    <p class="text-muted font-size-13 mb-3">{{ $customer->email }}</p>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill font-size-12 fw-semibold">
                        <i class="bx bx-user-check me-1 align-middle"></i> Verified Customer
                    </span>

                    <div class="text-start mt-4 pt-3 border-top">
                        <div class="meta-stat-row">
                            <span class="text-muted">Account Type</span>
                            <span class="fw-semibold text-dark text-capitalize">{{ $customer->account_type->value ?? 'Customer' }}</span>
                        </div>
                        <div class="meta-stat-row">
                            <span class="text-muted">Account Status</span>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">{{ $customer->status ?? 'Active' }}</span>
                        </div>
                        <div class="meta-stat-row">
                            <span class="text-muted">Customer Since</span>
                            <span class="fw-semibold text-dark">{{ $customer->created_at ? $customer->created_at->format('M d, Y') : 'N/A' }}</span>
                        </div>
                        <div class="meta-stat-row">
                            <span class="text-muted">Total Tickets</span>
                            <a href="{{ route('customer.tickets.index') }}" class="fw-semibold text-primary text-decoration-none">
                                {{ $customer->tickets()->count() }} {{ \Illuminate\Support\Str::plural('Ticket', $customer->tickets()->count()) }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Privacy & Security Notice --}}
            <div class="profile-panel-card p-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="bx bx-shield-quarter text-primary fs-3"></i>
                    <div>
                        <div class="fw-semibold text-dark font-size-13">Privacy & Account Security</div>
                        <p class="text-muted font-size-12 mb-0 mt-1">
                            Your account information is encrypted and accessible only by you. You can update your profile details and change your password at any time.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Profile Edit Form --}}
        <div class="col-lg-8">
            <div class="profile-panel-card">
                <div class="profile-panel-header">
                    <div>
                        <h5 class="fw-bold text-dark mb-0 font-size-16">Profile Information</h5>
                        <small class="text-muted">Update your account identity and contact details</small>
                    </div>
                </div>
                <div class="profile-panel-body">
                    <form action="{{ route('customer.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        {{-- Section 1: Personal Details --}}
                        <div class="row g-3">
                            {{-- Full Name --}}
                            <div class="col-md-6">
                                <label for="name" class="form-label-modern">Full Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $customer->name) }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       required
                                       placeholder="Enter your full name">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email Address --}}
                            <div class="col-md-6">
                                <label for="email" class="form-label-modern">Email Address <span class="text-danger">*</span></label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $customer->email) }}"
                                       class="form-control-modern @error('email') is-invalid @enderror"
                                       required
                                       placeholder="name@example.com">
                                <div class="form-hint">Used for ticket notifications and account login.</div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Phone (Optional / If Schema Supports) --}}
                            <div class="col-md-12">
                                <label for="phone" class="form-label-modern">Phone Number <span class="text-muted font-size-12 fw-normal">(Optional)</span></label>
                                <input type="text"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone', $customer->phone ?? '') }}"
                                       class="form-control-modern @error('phone') is-invalid @enderror"
                                       placeholder="+1 (555) 000-0000">
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Avatar Upload & Removal --}}
                            <div class="col-md-12">
                                <label for="avatar" class="form-label-modern">Profile Picture <span class="text-muted font-size-12 fw-normal">(Optional)</span></label>
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-1">
                                    @if($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted border" style="width: 48px; height: 48px;">
                                            <i class="bx bx-user font-size-22"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1">
                                        <input type="file"
                                               id="avatar"
                                               name="avatar"
                                               accept="image/jpeg,image/png,image/jpg,image/webp"
                                               class="form-control-modern @error('avatar') is-invalid @enderror">
                                        <div class="form-hint">Supported formats: JPEG, PNG, JPG, WebP. Maximum size: 2MB.</div>
                                    </div>
                                </div>
                                @if($avatarUrl)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar">
                                        <label class="form-check-label font-size-13 text-danger" for="remove_avatar">
                                            <i class="bx bx-trash me-1"></i> Remove current profile photo
                                        </label>
                                    </div>
                                @endif
                                @error('avatar')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Section 2: Security & Password Update --}}
                        <div class="section-divider"></div>

                        <div class="mb-3">
                            <h5 class="fw-bold text-dark mb-1 font-size-15">Change Password</h5>
                            <p class="text-muted font-size-13 mb-0">Leave blank if you do not wish to change your current password.</p>
                        </div>

                        <div class="row g-3">
                            {{-- Current Password --}}
                            <div class="col-md-12">
                                <label for="current_password" class="form-label-modern">Current Password</label>
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       class="form-control-modern @error('current_password') is-invalid @enderror"
                                       placeholder="Enter your current password">
                                <div class="form-hint">Required only if you are updating your password.</div>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- New Password --}}
                            <div class="col-md-6">
                                <label for="password" class="form-label-modern">New Password</label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control-modern @error('password') is-invalid @enderror"
                                       placeholder="Minimum 8 characters">
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Confirm New Password --}}
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label-modern">Confirm New Password</label>
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       class="form-control-modern"
                                       placeholder="Re-enter new password">
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('customer.dashboard') }}" class="btn btn-light px-4 py-2 rounded-3 font-size-14">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 font-size-14 fw-semibold d-inline-flex align-items-center">
                                <i class="bx bx-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

