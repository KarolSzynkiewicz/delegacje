@props(['label'])

<span class="rp-active-filters__chip">
    {{ $label }}
    <button type="button" {{ $attributes->merge(['class' => 'rp-active-filters__chip-remove', 'title' => 'Usuń filtr']) }}>
        <i class="bi bi-x"></i>
    </button>
</span>
