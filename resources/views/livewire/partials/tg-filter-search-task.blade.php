<span class="rp-filter-hint">Nazwa / treść</span>
@include('livewire.partials.tg-filter-op', ['field' => 'searchTask', 'eqLabel' => 'zawiera', 'neqLabel' => 'nie zawiera'])
<input type="text" wire:model.live.debounce.300ms="searchTask"
       class="form-control form-control-sm rp-filter-input" placeholder="Szukaj zadania…" @click.stop>
