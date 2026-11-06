<div class="form-group">
    @if($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-control @error($name) is-invalid @enderror"
        placeholder="{{ $placeholder ?? '' }}"
        rows="{{ $rows ?? 3 }}"
        {{ $required ?? false ? 'required' : '' }}
    >{{ old($name, $value ?? '') }}</textarea>

    @error($name)
        <span class="error invalid-feedback">{{ $message }}</span>
    @enderror
</div>