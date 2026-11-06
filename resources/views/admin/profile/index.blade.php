@extends('admin.layouts.master')
@section('title', __('profile.title'))
@section('content')
<div class="row">
    {{-- Profile Info Card --}}
    <div class="col-xl-4">
        <div class="card overflow-hidden">
            <div class="bg-primary bg-soft">
                <div class="row">
                    <div class="col-12">
                        <div class="text-primary p-4">
                            <h5 class="text-primary">{{ __('profile.profile_info') }}</h5>
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

                <div class="table-responsive mt-4">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th scope="row" style="width:100px;">{{ __('status.status') }}:</th>
                                <td>
                                    @if($user->status === 'Active')
                                        <span class="badge bg-success">{{ __('status.Active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('status.Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">{{ __('user.email') }}:</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Joined:</th>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Update Profile --}}
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">{{ __('profile.update_profile') }}</h4>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    @include('components.message.error')
                    <div class="row">
                        {{-- Avatar --}}
                        <div class="col-12 mb-3 text-center">
                            <label class="form-label d-block">{{ __('user.avatar') }}</label>
                            <div class="avatar-preview mx-auto" id="avatarPreview" title="{{ __('user.change_avatar') }}">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="avatar">
                                @else
                                    <i class="bx bx-user" style="font-size:48px; color:#adb5bd;"></i>
                                @endif
                            </div>
                            <input type="hidden" name="avatar_media_id" id="avatarMediaId" value="{{ optional($user->getFirstMedia('avatar'))->id }}">
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="avatarPickBtn">
                                    <i class="bx bx-image-add me-1"></i> {{ __('user.change_avatar') }}
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm {{ $avatarUrl ? '' : 'd-none' }}" id="avatarRemoveBtn">
                                    <i class="bx bx-trash me-1"></i> {{ __('user.remove_avatar') }}
                                </button>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-text
                                name="name"
                                :value="$user->name"
                                label="{{trans('user.name')}}"
                                placeholder="{{trans('user.name_placeholder')}}"
                                required="true"
                            />
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> @lang('common.submit_button')
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">{{ __('profile.change_password') }}</h4>
            </div>
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <x-form.input-password
                                name="current_password"
                                :value="''"
                                label="{{trans('profile.current_password')}}"
                                placeholder="{{trans('profile.current_password_placeholder')}}"
                                required="true"
                            />
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-password
                                name="password"
                                :value="''"
                                label="{{trans('profile.new_password')}}"
                                placeholder="{{trans('profile.new_password_placeholder')}}"
                                required="true"
                            />
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-password
                                name="password_confirmation"
                                :value="''"
                                label="{{trans('profile.confirm_password')}}"
                                placeholder="{{trans('profile.confirm_password_placeholder')}}"
                                required="true"
                            />
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-lock me-1"></i> {{ __('profile.change_password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('components.media.picker-modal')
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
    @vite(['resources/css/media-picker.css'])
@endpush

@push('script_links')
<script src="{{ asset('backend/assets/js/media-picker.js') }}"></script>
<script>
$(function() {
    $('#avatarPickBtn').on('click', function() {
        MediaPicker.open(function(media) {
            $('#avatarMediaId').val(media.id);
            $('#avatarPreview').html('<img src="' + media.url + '" alt="avatar">');
            $('#avatarRemoveBtn').removeClass('d-none');
        });
    });

    $('#avatarRemoveBtn').on('click', function() {
        $('#avatarMediaId').val('');
        $('#avatarPreview').html('<i class="bx bx-user" style="font-size:48px; color:#adb5bd;"></i>');
        $(this).addClass('d-none');
    });

    $('#avatarPreview').on('click', function() {
        $('#avatarPickBtn').trigger('click');
    });
});
</script>
@endpush