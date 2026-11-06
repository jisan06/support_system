<div class="form-group">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    <input type="email"
           name="{{ $name }}"
           id="{{ $name }}"
           value="{{ old($name, $value) }}"
           class="form-control @error($name) is-invalid @enderror"
           placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
    >

    @error($name)
    <span class="error invalid-feedback">{{ $message }}</span>
    @enderror
</div>
