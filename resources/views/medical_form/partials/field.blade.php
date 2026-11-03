@php
    use App\Support\FormFieldRegistry;
    $value = old($name, $data[$name] ?? '');
    $fieldNum = $num ?? FormFieldRegistry::numberForKey($name);
    $required = $fieldNum !== null
        ? FormFieldRegistry::isRequired($fieldNum)
        : ! empty($required);
    $isReadonly = !empty($readonly);
@endphp
<div class="{{ $class ?? '' }}" style="{{ ($class ?? '') === 'md:col-span-2' ? 'grid-column: span 2;' : '' }}">
    <label for="{{ $name }}" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; color: #475569;">
        {{ $label }}@if(!empty($required))<span style="color: #dc2626;">*</span>@endif
    </label>
    @if(($type ?? 'text') === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows ?? 3 }}"
            style="border-radius: 16px; background: rgba(255, 255, 255, 0.4); {{ $isReadonly ? 'opacity: 0.7; pointer-events: none;' : '' }}"
            class="input-modern"
            @if($isReadonly) readonly tabindex="-1" @endif>{{ $value }}</textarea>
    @elseif(($type ?? 'text') === 'select')
        <select id="{{ $name }}" name="{{ $name }}"
            style="background: rgba(255, 255, 255, 0.4); {{ $isReadonly ? 'opacity: 0.7; pointer-events: none;' : '' }}"
            class="select-modern"
            @if($isReadonly) tabindex="-1" @endif>
            <option value="">— Select —</option>
            @foreach($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected($value == $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $type ?? 'text' }}" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"
            @if(!empty($required)) required @endif
            @if(!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
            style="background: rgba(255, 255, 255, 0.4); {{ $isReadonly ? 'opacity: 0.7; pointer-events: none;' : '' }}"
            class="input-modern"
            @if($isReadonly) readonly tabindex="-1" @endif>
    @endif
</div>
