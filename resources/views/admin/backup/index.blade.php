@extends('admin.layouts.master')
@section('title', __('menu.backup'))
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bx bx-data me-1"></i> @lang('backup.title')
                </h5>
                <div class="d-flex gap-2">
                    {{-- Clean Old Backups --}}
                    <form action="{{ route('backups.clean') }}" method="POST"
                          onsubmit="return confirm('@lang('backup.confirm_clean')')">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm">
                            <i class="bx bx-brush me-1"></i> @lang('backup.clean_old')
                        </button>
                    </form>

                    {{-- Backup Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-plus me-1"></i> @lang('backup.create_backup')
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="{{ route('backups.store') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bx bx-data me-1"></i> @lang('backup.full_backup')
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form action="{{ route('backups.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="option" value="only-db">
                                    <button type="submit" class="dropdown-item">
                                        <i class="bx bx-server me-1"></i> @lang('backup.db_only')
                                    </button>
                                </form>
                            </li>
                            <li>
                                <form action="{{ route('backups.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="option" value="only-files">
                                    <button type="submit" class="dropdown-item">
                                        <i class="bx bx-folder me-1"></i> @lang('backup.files_only')
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @include('components.message.error')

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(count($backups) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>@lang('backup.file_name')</th>
                                    <th width="12%">@lang('backup.file_size')</th>
                                    <th width="18%">@lang('backup.created_at')</th>
                                    <th width="20%" class="text-center">@lang('backup.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $index => $backup)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <i class="bx bx-file me-1 text-primary"></i>
                                            {{ $backup->name }}
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $backup->size }}</span>
                                        </td>
                                        <td>{{ $backup->date }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                {{-- Download --}}
                                                <a href="{{ route('backups.download', $backup->name) }}"
                                                   class="btn btn-sm btn-outline-success"
                                                   title="@lang('backup.download')">
                                                    <i class="bx bx-download"></i>
                                                </a>

                                                {{-- Restore --}}
                                                <form action="{{ route('backups.restore', $backup->name) }}" method="POST"
                                                      onsubmit="return confirm('@lang('backup.confirm_restore')')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-warning"
                                                            title="@lang('backup.restore')">
                                                        <i class="bx bx-revision"></i>
                                                    </button>
                                                </form>

                                                {{-- Delete --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger delete-row"
                                                        data-route="{{ route('backups.destroy', $backup->name) }}"
                                                        data-modal-title="@lang('common.confirm_deletion')"
                                                        data-modal-desc="@lang('backup.confirm_delete')"
                                                        data-modal-action="@lang('common.delete')"
                                                        title="@lang('common.delete')">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-data" style="font-size: 48px; color: #adb5bd;"></i>
                        <p class="text-muted mt-2">@lang('backup.no_backups')</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('css_links')
    <link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
@endpush

@push('script_links')
    @include('components.modal.confirm')
    <script src="{{ admin_asset('libs/toastr/build/toastr.min.js') }}"></script>
    <script>
        $(function () {
            let url = '';

            $(document).on("click", ".delete-row", function () {
                let that = $(this);
                $('#confirmModal #confirmModalLabel').text(that.data('modal-title'));
                $('#confirmModal #confirmModalDesc').text(that.data('modal-desc'));
                $('#confirmModal #confirmAction').text(that.data('modal-action'));
                $('#confirmModal').modal('show');
                url = that.data("route");
            });

            $(document).on('click', '#confirmAction', function () {
                let close = $(this).parents('#confirmModal:first').find('.btn-close');
                $.ajax({
                    url: url,
                    type: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        close.trigger("click");
                        toastr["success"](response.message);
                        setTimeout(function () { location.reload(); }, 1000);
                    },
                    error: function () {
                        alert("@lang('common.delete_failed')");
                    }
                });
            });
        });
    </script>
@endpush