@extends('admin.layouts.master')
@section('title', trans_choice('customer.customers', 2))
@section('datatable_page', true)
@section('content')
    <div class="row item-list-wrap">
        <div class="col-12">
            <div class="card">
                <x-table.header
                    :title="__('customer.title')"
                    icon="fas fa-user-friends"
                    :tabs="[
                        ['label' => __('common.all'), 'route' => 'customers.index', 'icon' => 'fas fa-list'],
                        ['label' => __('common.trash'), 'route' => 'customers.trash', 'icon' => 'fas fa-trash', 'permission' => 'delete_customer']
                    ]"
                    :add-button="['label'=> __('customer.add_new'), 'route'=>'customers.create','icon'=>'fas fa-plus-circle', 'permission' => 'create_customer']"
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

