<div class="form-group">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
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

    @error($name)
    <span class="error invalid-feedback">{{ $message }}</span>
    @enderror
</div>
