<span class="rp-filter-hint">Dokładny priorytet. Pusty = wszystkie.</span>
<div class="rp-filter-chips">
    <button type="button" wire:click="clearFilter('filterPriority')"
            class="rp-filter-chip {{ $filterPriority === '' ? 'is-active' : '' }}">Wszystkie</button>
    @foreach(['1' => 'Najniższy', '2' => 'Niski', '3' => 'Średni', '4' => 'Wysoki', '5' => 'Krytyczny'] as $value => $label)
        <button type="button" wire:click="filterByPriority('{{ $value }}')"
                class="rp-filter-option {{ $filterPriority === $value ? 'is-active' : '' }}">
            <span class="rp-filter-check {{ $filterPriority === $value ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
            <span class="rp-filter-option__label">{{ $value }} – {{ $label }}</span>
        </button>
    @endforeach
</div>
