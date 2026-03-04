@props(['name' => 'status', 'selected' => '', 'required' => false, 'disabled' => false, 'class' => 'form-select'])

<select 
    name="{{ $name }}" 
    id="{{ $name }}" 
    class="{{ $class }}" 
    @if($required) required @endif 
    @if($disabled) disabled @endif
>
    <option value="">-- Select Status --</option>
    @foreach(get_domain_status_options() as $value => $label)
        @php
            $config = get_domain_status_config($value);
            $style = get_domain_status_style($value);
        @endphp
        <option 
            value="{{ $value }}" 
            @if($selected == $value) selected @endif
            style="{{ $style }}"
            data-badge-class="{{ $config['badge_class'] }}"
            data-icon="{{ $config['icon'] }}"
        >
            {{ $label }}
        </option>
    @endforeach
</select>
