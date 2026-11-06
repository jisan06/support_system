<button
    data-id="{{ $id }}"
    data-route="{{ $route }}"
    data-modal-title="@lang('common.confirm_deletion')"
    data-modal-desc="@lang('common.sure_delete')"
    data-modal-action="@lang('common.delete')"
    data-bs-toggle="tooltip"
    data-bs-placement="top"
    title="@lang('common.delete')"
    class="delete-row btn btn-outline-danger d-flex align-items-center gap-1"
>
    <i class="fas fa-trash"></i>
</button>
