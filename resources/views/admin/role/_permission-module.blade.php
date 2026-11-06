<div class="col-md-4">
    <div class="card border shadow-sm h-100">
        <div class="align-items-center card-header d-flex justify-content-between">
            <span>{{ $module['label'] }}</span>
            <div class="form-check mb-0">
                <input
                    type="checkbox"
                    id="select_all_{{ $module['label'] }}"
                    class="form-check-input select-all-module"
                >
                <label
                    for="select_all_{{ $module['label'] }}"
                    class="font-size-15 form-check-label fw-normal small"
                >
                    @lang('common.select_all')
                </label>
            </div>
        </div>
        <div class="card-body d-flex flex-column gap-2 permissions-list">
            @foreach($module['permissions'] as $slug => $label)
                <div class="align-items-center d-flex form-check gap-2">
                    <input
                        type="checkbox"
                        id="{{ $module['label'] }}_{{ $slug }}"
                        class="form-check-input permission-checkbox mt-0"
                        name="permissions[]"
                        value="{{ $slug }}"
                            {{ in_array($slug, $roleSlugs) ? 'checked' : '' }}
                    >
                    <label
                        class="font-size-15 form-check-label m-0"
                        for="{{ $module['label'] }}_{{ $slug }}"
                    >
                        @lang($label)
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>