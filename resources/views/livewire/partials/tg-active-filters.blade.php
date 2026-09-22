<div class="rp-active-filters tg-active-filters{{ $this->isPlanQueue() ? '' : ' mb-2 px-1' }}">
    <span class="rp-active-filters__label">Filtry:</span>
    <div class="tg-active-filters__chips">
        @foreach($filterChips as $chip)
            <span class="rp-active-filters__chip{{ ! empty($chip['locked']) ? ' is-locked' : '' }}"
                  data-tg-filter-key="{{ $chip['key'] }}"
                  wire:key="tg-filter-chip-{{ $chip['key'] }}">
                <span class="rp-active-filters__chip-text">{{ $chip['label'] }}</span>
                @if(empty($chip['locked']))
                <button type="button"
                        wire:click="clearFilter('{{ $chip['key'] }}')"
                        class="rp-active-filters__chip-remove"
                        title="Usuń filtr">
                    <i class="bi bi-x"></i>
                </button>
                @endif
            </span>
        @endforeach
    </div>
    <button type="button" wire:click="clearFilters" class="rp-active-filters__clear">Wyczyść</button>
</div>
