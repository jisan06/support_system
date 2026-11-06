@extends('admin.layouts.master')
@section('title', isset($role) ? __('role.edit_role') : __('role.create_role'))
@section('content')
<div class="row">
    <div class="col-12">
        <form action="{{ isset($role) ? route('roles.update', $role->id) : route('roles.store') }}" method="POST">
            @csrf
            @if(isset($role))
                @method('PATCH')
            @endif
            <div class="card">
                <div class="card-header">
                    {{ isset($role) ? __('role.edit_role') : __('role.create_role') }}
                </div>
                <div class="card-body">
                    @include('components.message.error')
                    <div class="row">
                        <div class="col-sm-4 mb-3">
                            <x-form.input-text
                                name="name"
                                :value="$role->name ?? ''"
                                label="{{ trans('role.role_name') }}"
                                placeholder="{{ trans('role.role_name_placeholder') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-sm-4 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="level">{{ trans('role.level') }}</label>
                                <input type="number"
                                    name="level"
                                    id="level"
                                    value="{{ old('level', $role->level ?? $min_level) }}"
                                    class="form-control @error('level') is-invalid @enderror"
                                    placeholder="{{ trans('role.level_placeholder') }}"
                                    min="{{ $min_level }}"
                                    required
                                >
                                @error('level')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-4 mb-3">
                            <div class="form-group">
                                <x-form.input-radio
                                    name="status"
                                    label="{{ trans('role.status') }}"
                                    :options="['Active' => trans('status.Active'), 'Inactive' => trans('status.Inactive')]"
                                    :selected="$role->status ?? 'Active'"
                                    required="true"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{route('roles.index')}}" class="btn btn-outline-secondary">
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
@endsection
