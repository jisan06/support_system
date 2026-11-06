@extends('admin.layouts.master')
@section('title', trans_choice('activity_log.activity_log', 2))
@section('datatable_page', true)
@section('content')
    <div class="row item-list-wrap">
        <div class="col-12">
            <div class="card">
                <x-table.header
                    :title="__('activity_log.title')"
                    icon="fas fa-history"
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
    {{ $dataTable->scripts() }}
@endpush