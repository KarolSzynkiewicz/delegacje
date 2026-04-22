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

@if($label && $type !== 'checkbox')
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
        {{ $attributes->merge(['class' => 'form-select ' . ($hasError ? 'is-invalid' : '')]) }}
        {{ $required ? 'required' : '' }}
    >
        {{ $slot }}
    </select>
@elseif($type === 'checkbox')
    @php
        // Wartość: `:value` trafia do props `$value`, a nie zawsze do `$attributes` — bez tego
        // wiele checkboxów z tym samym `name` dostaje domyślne value="1" (błąd Livewire).
        $checkboxValue = $value ?? $attributes->get('value', '1');
        $checkboxValue = $checkboxValue === '' || $checkboxValue === null ? '1' : $checkboxValue;

        // Unikalny id: ten sam `name` na wielu wierszach nie może mieć jednego id (HTML + Livewire).
        $checkboxId = $id;
        if (! $checkboxId && $name) {
            $safe = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $checkboxValue);
            $checkboxId = $name.'-'.$safe;
        }
        $checkboxId = $checkboxId ?: 'checkbox-'.uniqid('', true);

        // Stan zaznaczenia — NIE wynika z propa `value`: przy checkboxie `value="1"`
        // to wartość wysyłana w POST, a domyślna; mylono ją ze „zaznaczony” (miganie przy Livewire).
        $isChecked = false;
        if ($attributes->has('checked')) {
            $c = $attributes->get('checked');
            $isChecked = ! in_array($c, [false, 'false', '0', 0, 'off'], true);
        } elseif (is_bool($value)) {
            $isChecked = $value;
        } elseif (old($name ?? '')) {
            $isChecked = (bool) old($name);
        }
    @endphp
    <div class="form-check {{ $attributes->get('class') }}">
        <input 
            type="checkbox" 
            name="{{ $name }}" 
            id="{{ $checkboxId }}"
            class="form-check-input"
            value="{{ $checkboxValue }}"
            {{ $isChecked ? 'checked' : '' }}
            {{ $attributes->except(['class', 'value', 'checked', 'id'])->merge([]) }}
            {{ $required ? 'required' : '' }}
        >
        @if($label)
            <label class="form-check-label" for="{{ $checkboxId }}">
                {!! $label !!}
                @if($required)
                    <span class="text-danger">*</span>
                @endif
            </label>
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
