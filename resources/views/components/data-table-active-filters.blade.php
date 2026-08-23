{{-- Pasek „Filtry:” między kartą filtrów a kartą tabeli (jak /tasks2). --}}
<div class="rp-active-filters dt-active-filters mb-2 px-1">
    <span class="rp-active-filters__label">Filtry:</span>
    {{ $slot }}
    <button type="button" wire:click="clearFilters" class="rp-active-filters__clear">Wyczyść</button>
</div>
