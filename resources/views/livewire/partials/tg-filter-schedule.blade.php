<span class="rp-filter-hint"
      data-tip="Klik na chipie w wierszu: ten stan. × przy chipie: odwrotnie.">
    Slot w kalendarzu. Pusty = wszystkie.
</span>
@include('livewire.partials.tg-filter-op', ['field' => 'filterSchedule', 'eqLabel' => 'jest', 'neqLabel' => 'nie jest'])
<div class="rp-filter-chips">
    <button type="button" wire:click="clearFilter('filterSchedule')"
            class="rp-filter-chip {{ $filterSchedule === '' ? 'is-active' : '' }}">Wszystkie</button>
    @foreach(['none' => 'Brak', 'scheduled' => 'Zaplanowane', 'stale' => 'Zaległe'] as $value => $label)
        <button type="button" wire:click="filterBySchedule('{{ $value }}')"
                class="rp-filter-option {{ $filterSchedule === $value ? 'is-active' : '' }}">
            <span class="rp-filter-check {{ $filterSchedule === $value ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
            <span class="rp-filter-option__label">{{ $label }}</span>
        </button>
    @endforeach
</div>
