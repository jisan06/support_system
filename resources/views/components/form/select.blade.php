<div class="form-group">
    @if($label)
        <label for="{{ $name }}">{!! $label !!}</label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-control {{ $select2 ? 'select2' : '' }} select2-multiple"
        {{ $multiple ? 'multiple' : '' }}
    >
        @foreach($options as $key => $value)
            <option value="{{ $key }}"
                    @if(collect($selected)->contains($key)) selected @endif>
                {{ $value }}
            </option>
        @endforeach
    </select>
</div>
