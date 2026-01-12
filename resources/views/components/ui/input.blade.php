@props([
    'type' => 'text',
    'label' => null,
    'name' => null,
    'id' => null,
    'placeholder' => null,
    'value' => null,
    'required' => false,
])

@php
    $inputId = $id ?? $name;
    $hasError = $errors->has($name ?? '');
    $inputClasses = 'form-control';
    if ($hasError) {
        $inputClasses .= ' is-invalid';
    }
@endphp

@if($label)
    <label class="form-label" for="{{ $inputId }}">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
@endif

@if($type === 'textarea')
    <textarea 
        name="{{ $name }}" 
        id="{{ $inputId }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => $inputClasses]) }}
        {{ $required ? 'required' : '' }}
    >{{ $value ?? old($name) }}</textarea>
@elseif($type === 'select')
    <select 
        name="{{ $name }}" 
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => $inputClasses]) }}
        {{ $required ? 'required' : '' }}
    >
        {{ $slot }}
    </select>
@elseif($type === 'checkbox')
    @php
        // Dla checkboxów, sprawdzamy czy value jest true/checked
        // Jeśli value jest przekazane jako atrybut, używamy go
        $isChecked = false;
        if (isset($value)) {
            // Jeśli value jest boolean lub truthy, zaznaczamy
            $isChecked = $value === true || $value === '1' || $value === 1 || $value === 'on';
        } else {
            // W przeciwnym razie sprawdzamy old() lub atrybut checked
            $isChecked = old($name) || $attributes->has('checked');
        }
    @endphp
    <div class="form-check {{ $attributes->get('class') }}">
        <input 
            type="checkbox" 
            name="{{ $name }}" 
            id="{{ $inputId }}"
            value="{{ $attributes->get('value', '1') }}"
            {{ $isChecked ? 'checked' : '' }}
            {{ $attributes->except(['class', 'value', 'checked'])->merge([]) }}
            {{ $required ? 'required' : '' }}
        >
        @if($label)
            <label for="{{ $inputId }}">{!! $label !!}</label>
        @endif
    </div>
@else
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        id="{{ $inputId }}"
        value="{{ $value ?? old($name) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => $inputClasses]) }}
        {{ $required ? 'required' : '' }}
    >
@endif

@if($hasError && $name)
    <span class="invalid-feedback">
        {{ $errors->first($name) }}
    </span>
@endif
