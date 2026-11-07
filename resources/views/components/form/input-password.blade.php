<div class="form-group">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    <div class="input-group">
        <input type="password"
               name="{{ $name }}"
               id="{{ $name }}"
               value=""
               class="form-control @error($name) is-invalid @enderror"
               placeholder="{{ $placeholder }}"
               autocomplete="new-password"
            {{ $required ? 'required' : '' }}
            @if($name === 'password') data-strength-meter data-strength-rules='@json(config("password"))' @endif
        >
        <button type="button" class="btn btn-outline-secondary toggle-password-btn" data-target="#{{ $name }}" title="Show/Hide password" tabindex="-1">
            <i class="bx bx-show font-size-16 align-middle"></i>
        </button>
    </div>

    @error($name)
    <span class="error invalid-feedback d-block">{{ $message }}</span>
    @enderror
</div>
