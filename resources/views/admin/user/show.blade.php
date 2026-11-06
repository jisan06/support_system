@extends('admin.layouts.master')
@section('title', __('user.view_user'))
@section('content')
<div class="row">
    <div class="col-xl-4">
        <div class="card overflow-hidden">
            <div class="bg-primary bg-soft">
                <div class="row">
                    <div class="col-12">
                        <div class="text-primary p-4">
                            <h5 class="text-primary">{{ __('user.view_user') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="text-center mt-n4">
                    @php
                        $avatarUrl = $user->getFirstMediaUrl('avatar');
                    @endphp
                    <div class="mx-auto mb-3" style="width:80px; height:80px; border-radius:50%; overflow:hidden; border:3px solid #fff; box-shadow:0 2px 10px rgba(0,0,0,.1); display:flex; align-items:center; justify-content:center; background:#f8f9fa;">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="avatar" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <i class="bx bx-user" style="font-size:40px; color:#adb5bd;"></i>
                        @endif
                    </div>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    @foreach($user->roles as $role)
                        <span class="badge rounded-pill bg-info font-size-12">{{ $role->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">{{ __('user.name') }}</th>
                                <td>{{ $user->name }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('user.email') }}</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('status.status') }}</th>
                                <td>
                                    @if($user->status === 'Active')
                                        <span class="badge bg-success">{{ __('status.Active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('status.Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('user.roles') }}</th>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge rounded-pill bg-info font-size-12">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('user.created_by') }}</th>
                                <td>{{ optional($user->createdBy)->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('user.joined') }}</th>
                                <td>{{ $user->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i>
                    {{ __('user.back_to_list') }}
                </a>
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i> {{ __('user.edit_user') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection