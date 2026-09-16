<span class="rp-filter-hint">Kategoria</span>
@include('livewire.partials.tg-filter-op', ['field' => 'searchCategory', 'eqLabel' => 'zawiera', 'neqLabel' => 'nie zawiera'])
<input type="text" wire:model.live.debounce.300ms="searchCategory"
       class="form-control form-control-sm rp-filter-input" placeholder="np. Logistyka" @click.stop>
