<div class="form-group">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    <div
        class="form-control d-flex gap-4 @error($name) is-invalid @enderror"
    >
        @foreach($options as $key => $option)
            <div class="d-flex align-items-center gap-1">
                <input
                    type="radio"
                    id="{{ $name }}_{{ $key }}"
                    name="{{ $name }}"
                    value="{{ $key }}"
                    {{ $required ? 'required ' : '' }}
                    {{ old($name, isset($selected) ? $selected : '') == $key ? 'checked' : '' }}
                >
                <label class="form-check-label" for="{{ $name }}_{{ $key }}">{{ $option }}</label>
            </div>
        @endforeach
    </div>
    @error($name)
        <span class="error invalid-feedback">{{ $message }}</span>
    @enderror
</div>
