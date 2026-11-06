@extends('admin.layouts.master')
@section('title', trans_choice('role.role', 2))
@section('datatable_page', true)
@section('content')
    <div class="row item-list-wrap">
        <div class="col-12">
            <div class="card">
                <x-table.header
                    :title="__('role.title')"
                    icon="fas fa-users-cog"
                    :tabs="[
                        ['label' => __('common.all'), 'route' => 'roles.index', 'icon' => 'fas fa-list'],
                        ['label' => __('common.trash'), 'route' => 'roles.trash', 'icon' => 'fas fa-trash', 'permission' => 'delete_role']
                    ]"
                    :add-button="['label'=> __('role.add_role'), 'route'=>'roles.create','icon'=>'fas fa-plus-circle', 'permission' => 'create_role']"
                />

                <div class="card-body">
                    {{ $dataTable->table() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
@endpush

@push('script_links')
    @vite(['resources/js/app.js'])
    @include('components.modal.confirm')
    @include('admin.layouts.include.others.delete')
    @if(!empty($page_type) && $page_type === 'trash')
        @include('admin.layouts.include.others.restore')
    @endif
    {{ $dataTable->scripts() }}
@endpush
