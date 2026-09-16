<span class="rp-filter-hint">Osoba</span>
@include('livewire.partials.tg-filter-op', ['field' => 'searchAssignedTo', 'eqLabel' => 'zawiera', 'neqLabel' => 'nie zawiera'])
<input type="text" wire:model.live.debounce.300ms="searchAssignedTo"
       class="form-control form-control-sm rp-filter-input" placeholder="Imię i nazwisko" @click.stop>
