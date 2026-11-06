@extends('admin.layouts.master')
@section('title', __('permission.manage_permissions'))
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i> @lang('permission.manage_permissions'): {{ $role->name }}</h5>
                </div>
            </div>

            @include('components.message.error')

            @php
                $permissions = config('permissions');
                $roleSlugs = $role->permissions()->pluck('slug')->toArray();
                $multiSections = collect($permissions)->filter(fn($s) => count($s['modules']) > 1);
                $singleSections = collect($permissions)->filter(fn($s) => count($s['modules']) === 1);
            @endphp

            <form action="{{ route('roles.permission_update', $role->id) }}" method="post">
                @csrf
                @foreach($multiSections as $section)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-primary">{{ $section['label'] }}</h5>
                            <div class="row g-3">
                                @foreach($section['modules'] as $module)
                                    @include('admin.role._permission-module', ['module' => $module, 'roleSlugs' => $roleSlugs])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($singleSections->count())
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-primary">@lang('permission.other_permissions')</h5>
                            <div class="row g-3">
                                @foreach($singleSections as $section)
                                    @foreach($section['modules'] as $module)
                                        @include('admin.role._permission-module', ['module' => $module, 'roleSlugs' => $roleSlugs])
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                <div class="align-items-center d-flex justify-content-between">
                    <a href="{{route('roles.index')}}" class="btn btn-outline-secondary">
                        <i class="fa fa-times" aria-hidden="true"></i>
                        @lang('common.cancel')
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> @lang('permission.update_permission')
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('css_links')
    @vite(['resources/css/role.css'])
@endpush
@push('script_links')
    <script>
        $(document).ready(function () {
            // Handle Select All checkbox per module
            $('.select-all-module').on('change', function () {
                let isChecked = $(this).is(':checked');
                $(this).closest('.card').find('.permissions-list .permission-checkbox').prop('checked', isChecked);
            });

            // Optional: if all individual checkboxes are manually checked, check Select All
            $('.permission-checkbox').on('change', function () {
                let $moduleCard = $(this).closest('.card');
                let allChecked = $moduleCard.find('.permission-checkbox').length ===
                    $moduleCard.find('.permission-checkbox:checked').length;
                $moduleCard.find('.select-all-module').prop('checked', allChecked);
            });

            // Initialize select all state on page load
            $('.card').each(function () {
                let allChecked = $(this).find('.permission-checkbox').length ===
                    $(this).find('.permission-checkbox:checked').length;
                $(this).find('.select-all-module').prop('checked', allChecked);
            });
        });
    </script>
@endpush
