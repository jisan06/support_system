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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0" for="password">{{ trans('user.password') }}</label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-primary text-decoration-none fw-semibold" id="generatePasswordBtn">
                                    <i class="bx bx-refresh me-1 font-size-14 align-middle"></i> Generate Password
                                </button>
                            </div>
                            <x-form.input-password
                                name="password"
                                :value="''"
                                label=""
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
    // Avatar Picker
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

    // Password Generator adhering strictly to config('password') rules
    $('#generatePasswordBtn').on('click', function(e) {
        e.preventDefault();
        var rules = @json(config('password'));
        var minLength = Math.max(parseInt(rules.min_length || 8), 12);

        var uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        var lowercase = 'abcdefghijkmnopqrstuvwxyz';
        var numbers = '23456789';
        var symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        var chars = [];
        var allPool = '';

        if (rules.uppercase !== false) {
            chars.push(uppercase.charAt(Math.floor(Math.random() * uppercase.length)));
            allPool += uppercase;
        }
        if (rules.lowercase !== false) {
            chars.push(lowercase.charAt(Math.floor(Math.random() * lowercase.length)));
            allPool += lowercase;
        }
        if (rules.numbers !== false) {
            chars.push(numbers.charAt(Math.floor(Math.random() * numbers.length)));
            allPool += numbers;
        }
        if (rules.symbols !== false) {
            chars.push(symbols.charAt(Math.floor(Math.random() * symbols.length)));
            allPool += symbols;
        }

        if (!allPool) {
            allPool = uppercase + lowercase + numbers + symbols;
        }

        while (chars.length < minLength) {
            chars.push(allPool.charAt(Math.floor(Math.random() * allPool.length)));
        }

        // Fisher-Yates Shuffle
        for (var i = chars.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = chars[i];
            chars[i] = chars[j];
            chars[j] = tmp;
        }

        var generatedPassword = chars.join('');

        var passEl = document.getElementById('password');
        var confirmEl = document.getElementById('password_confirm');

        if (passEl) {
            passEl.value = generatedPassword;
            passEl.type = 'text';
            passEl.dispatchEvent(new Event('input', { bubbles: true }));
            passEl.dispatchEvent(new Event('change', { bubbles: true }));
            if (window.checkPasswordStrength) {
                window.checkPasswordStrength(passEl);
            }
            $('.toggle-password-btn[data-target="#password"]').attr('title', 'Hide password').find('i').removeClass('bx-show').addClass('bx-hide');
        }

        if (confirmEl) {
            confirmEl.value = generatedPassword;
            confirmEl.type = 'text';
            confirmEl.dispatchEvent(new Event('input', { bubbles: true }));
            confirmEl.dispatchEvent(new Event('change', { bubbles: true }));
            $('.toggle-password-btn[data-target="#password_confirm"]').attr('title', 'Hide password').find('i').removeClass('bx-show').addClass('bx-hide');
        }
    });
});
</script>
@endpush
