@extends('admin.layouts.master')
@section('title', __('menu_sort.title'))
@section('content')
<div class="row">
    <div class="col-xl-8 col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1"><i class="bx bx-sort me-1"></i> {{ __('menu_sort.title') }}</h4>
                    <p class="text-muted mb-0 font-size-13">{{ __('menu_sort.description') }}</p>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" id="resetOrder">
                    <i class="bx bx-reset me-1"></i> {{ __('menu_sort.reset_order') }}
                </button>
            </div>
            <div class="card-body">
                <ul class="list-unstyled sortable-menu" id="topLevelSortable">
                    @foreach($menu as $item)
                        <li class="sortable-item" data-key="{{ $item['key'] }}">
                            <div class="sortable-handle d-flex align-items-center">
                                <span class="drag-icon me-3"><i class="bx bx-grid-vertical font-size-20 text-muted"></i></span>
                                <i class="{{ $item['icon'] }} me-2 font-size-16"></i>
                                <span class="flex-grow-1 fw-medium">{{ trans_choice($item['title'], 2) }}</span>
                                @if(!empty($item['sub_menu']))
                                    <span class="badge bg-light text-dark">{{ count($item['sub_menu']) }} {{ __('menu_sort.sub_items') }}</span>
                                @endif
                            </div>

                            @if(!empty($item['sub_menu']))
                                <ul class="list-unstyled sortable-sub-menu ms-4 mt-2" data-parent="{{ $item['key'] }}">
                                    @foreach($item['sub_menu'] as $sub)
                                        <li class="sortable-sub-item" data-key="{{ $sub['key'] }}">
                                            <div class="sortable-handle d-flex align-items-center">
                                                <span class="drag-icon me-3"><i class="bx bx-grid-vertical font-size-18 text-muted"></i></span>
                                                <i class="{{ $sub['icon'] }} me-2 font-size-14"></i>
                                                <span>{{ trans_choice($sub['title'], 2) }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-footer text-end">
                <button type="button" class="btn btn-primary" id="saveOrder">
                    <i class="fas fa-save me-1"></i> {{ __('menu_sort.save_order') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css_links')
<link rel="stylesheet" href="{{ admin_asset('libs/toastr/build/toastr.min.css') }}">
<style>
    .sortable-item {
        background: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #e9ecef);
        border-radius: 6px;
        margin-bottom: 8px;
        transition: box-shadow .2s;
    }
    .sortable-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }
    .sortable-handle {
        padding: 12px 16px;
        cursor: grab;
    }
    .sortable-handle:active {
        cursor: grabbing;
    }
    .sortable-sub-item {
        background: var(--bs-tertiary-bg, #f8f9fa);
        border: 1px solid var(--bs-border-color, #e9ecef);
        border-radius: 4px;
        margin-bottom: 4px;
    }
    .sortable-sub-item .sortable-handle {
        padding: 8px 12px;
    }
    .sortable-ghost {
        opacity: 0.4;
        background: var(--bs-primary, #556ee6) !important;
        border-color: var(--bs-primary, #556ee6) !important;
    }
    .sortable-ghost * {
        color: #fff !important;
    }
    .drag-icon {
        cursor: grab;
    }
    .sortable-item,
    .sortable-sub-item {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    .sortable-item .sortable-handle > i:not(.bx-grid-vertical),
    .sortable-sub-item .sortable-handle > i:not(.bx-grid-vertical) {
        width: 20px;
        text-align: center;
    }
</style>
@endpush

@push('script_links')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize top-level sortable
    var topSortable = Sortable.create(document.getElementById('topLevelSortable'), {
        animation: 200,
        handle: '.sortable-item > .sortable-handle',
        ghostClass: 'sortable-ghost',
        scroll: true,
        scrollSensitivity: 80,
        scrollSpeed: 15,
        bubbleScroll: true,
        forceFallback: true
    });

    // Initialize sub-menu sortables
    document.querySelectorAll('.sortable-sub-menu').forEach(function(el) {
        Sortable.create(el, {
            animation: 200,
            handle: '.sortable-sub-item > .sortable-handle',
            ghostClass: 'sortable-ghost',
            scroll: true,
            scrollSensitivity: 80,
            scrollSpeed: 15,
            bubbleScroll: true,
            forceFallback: true
        });
    });

    // Collect current order
    function getOrder() {
        var topOrder = [];
        document.querySelectorAll('#topLevelSortable > .sortable-item').forEach(function(el) {
            topOrder.push(el.getAttribute('data-key'));
        });

        var subOrder = {};
        document.querySelectorAll('.sortable-sub-menu').forEach(function(el) {
            var parent = el.getAttribute('data-parent');
            var keys = [];
            el.querySelectorAll('.sortable-sub-item').forEach(function(sub) {
                keys.push(sub.getAttribute('data-key'));
            });
            subOrder[parent] = keys;
        });

        return { top: topOrder, sub: subOrder };
    }

    function sendOrder(data, btn, onSuccess) {
        $.ajax({
            url: '{{ route("menu-sort.update") }}',
            type: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: JSON.stringify(data),
            success: function(response) {
                onSuccess(response);
                btn.prop('disabled', false);
            },
            error: function(xhr) {
                var msg = 'Failed to save.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
                btn.prop('disabled', false);
            }
        });
    }

    // Save
    $('#saveOrder').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);
        sendOrder({ order: getOrder() }, btn, function(response) {
            toastr.success(response.message);
        });
    });

    // Reset
    $('#resetOrder').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);
        sendOrder({ order: { top: [], sub: {} } }, btn, function() {
            toastr.success("{{ __('menu_sort.order_reset') }}");
            setTimeout(function() { location.reload(); }, 500);
        });
    });
});
</script>
@endpush