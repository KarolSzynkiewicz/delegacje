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
    <div x-data="{ 
        selectedText: '',
        updateSelected() {
            const select = $refs.selectEl;
            const selectedOption = select.options[select.selectedIndex];
            this.selectedText = selectedOption ? selectedOption.text : '';
        }
    }" x-init="updateSelected()">
        <select 
            name="{{ $name }}" 
            id="{{ $inputId }}"
            {{ $attributes->merge(['class' => str_replace('form-control', 'form-select', $inputClasses)]) }}
            {{ $required ? 'required' : '' }}
            style="color: white !important;"
            x-ref="selectEl"
            @change="updateSelected()"
        >
            {{ $slot }}
        </select>
        <div x-show="selectedText && selectedText !== 'Wybierz projekt' && selectedText !== 'Wybierz rolę' && selectedText !== 'Wybierz pojazd' && selectedText !== 'Wybierz mieszkanie' && !selectedText.includes('--')" 
             class="mt-1 small text-white-50" 
             style="color: #94a3b8 !important; font-size: 0.8rem;">
            <i class="bi bi-check-circle-fill text-success"></i> Wybrano: <span class="text-white fw-semibold" x-text="selectedText"></span>
        </div>
    </div>
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
