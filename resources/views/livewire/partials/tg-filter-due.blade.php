<span class="rp-filter-hint"
      data-tip="Klik na dacie w wierszu: do tego dnia włącznie. × przy dacie: później niż ta data (z terminem).">
    Do / po tym dniu. Pusty = wszystkie.
</span>
@include('livewire.partials.tg-filter-op', ['field' => 'filterDueDate', 'eqLabel' => 'do tego dnia', 'neqLabel' => 'po tym dniu'])
<input type="date" wire:model.live="filterDueDate"
       class="form-control form-control-sm rp-filter-input" @click.stop>
