@extends('admin.layouts.master')
@section('title', isset($user) ? __('user.edit_user') : __('user.create_user'))
@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST">
            @csrf
            @if(isset($user))
                @method('PATCH')
            @endif
            <div class="card">
                <div class="card-header">
                    {{ isset($user) ? __('user.edit_user') : __('user.create_user') }}
                </div>
                <div class="card-body">
                    @include('components.message.error')
                    <div class="row">
                        {{-- Avatar --}}
                        <div class="col-12 mb-3 text-center">
                            <label class="form-label d-block">{{ __('user.avatar') }}</label>
                            @php
                                $avatarUrl = isset($user) ? $user->getFirstMediaUrl('avatar') : null;
                            @endphp
                            <div class="avatar-preview mx-auto" id="avatarPreview" title="{{ __('user.change_avatar') }}">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="avatar">
                                @else
                                    <i class="bx bx-user" style="font-size:48px; color:#adb5bd;"></i>
                                @endif
                            </div>
                            <input type="hidden" name="avatar_media_id" id="avatarMediaId" value="{{ isset($user) ? optional($user->getFirstMedia('avatar'))->id : '' }}">
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="avatarPickBtn">
                                    <i class="bx bx-image-add me-1"></i> {{ __('user.change_avatar') }}
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm {{ $avatarUrl ? '' : 'd-none' }}" id="avatarRemoveBtn">
                                    <i class="bx bx-trash me-1"></i> {{ __('user.remove_avatar') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <x-form.select
                                name="role_ids[]"
                                label="{{trans('user.select_roles')}}"
                                :options="$roles->pluck('name','id')->toArray()"
                                :selected="$selected_roles ?? []"
                                :select2="true"
                                :multiple="true"
                            />
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-text
                                name="name"
                                :value="$user->name ?? ''"
                                label="{{trans('user.name')}}"
                                placeholder="{{trans('user.name_placeholder')}}"
                                required="true"
                            />
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-email
                                name="email"
                                :value="$user->email ?? ''"
                                label="{{trans('user.email')}}"
                                placeholder="{{trans('user.email_placeholder')}}"
                                required="true"
                            />
                        </div>
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <x-form.input-radio
                                    name="status"
                                    label="{{trans('status.status')}}"
                                    :options="['Active' => trans('status.Active'), 'Inactive' => trans('status.Inactive')]"
                                    :selected="$user->status ?? 'Active'"
                                    required="true"
                                />
                            </div>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-password
                                name="password"
                                :value="''"
                                label="{{trans('user.password')}}"
                                placeholder="{{trans('user.password_placeholder')}}"
                                required=""
                            />
                        </div>
                        <div class="col-sm-6 mb-3">
                            <x-form.input-password
                                name="password_confirm"
                                :value="''"
                                label="{{trans('user.password_confirm')}}"
                                placeholder="{{trans('user.password_confirm_placeholder')}}"
                                required=""
                            />
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{route('users.index')}}" class="btn btn-outline-secondary">
                        <i class="fa fa-times" aria-hidden="true"></i>
                        @lang('common.cancel')
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        @lang('common.submit_button')
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('components.media.picker-modal')
@endsection
@include('admin.layouts.include.others.select2')

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
