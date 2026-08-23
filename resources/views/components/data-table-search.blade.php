@props([
    'placeholder' => 'Szukaj…',
    'wide' => false,
])

{{-- Wzorzec z /tasks2: ikona w input-group-text, bez absolute — nie nachodzi na tekst. --}}
<div @class([
    'input-group input-group-sm dt-search-field',
    'dt-search-field--wide' => $wide,
])>
    <span class="input-group-text px-2">
        <i class="bi bi-search" style="font-size:0.72rem"></i>
    </span>
    <input
        type="text"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        {{ $attributes->merge(['class' => 'form-control']) }}
    >
</div>
