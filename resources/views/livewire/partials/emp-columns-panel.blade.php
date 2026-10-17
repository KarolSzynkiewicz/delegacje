<div class="rp-filter-panel__inner">
    <div class="rp-filter-panel__header">
        <span class="rp-filter-panel__title">Kolumny</span>
        <button type="button" @click="open = false" class="rp-filter-panel__clear">Zamknij</button>
    </div>
    <p class="small text-muted mb-2">Zaznaczone są widoczne. Pracownika nie da się ukryć.</p>
    <button type="button" class="rp-filter-option is-active" disabled style="opacity:.45;cursor:not-allowed">
        <span class="rp-filter-check is-checked"><i class="bi bi-check"></i></span>
        <span class="rp-filter-option__label">Pracownik</span>
    </button>
    @foreach($this->columnCatalog() as $colKey => $colLabel)
        @php $colChecked = $this->showsColumn($colKey); @endphp
        <button type="button"
                wire:click="toggleColumn('{{ $colKey }}')"
                class="rp-filter-option {{ $colChecked ? 'is-active' : '' }}">
            <span class="rp-filter-check {{ $colChecked ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
            <span class="rp-filter-option__label">{{ $colLabel }}</span>
        </button>
    @endforeach
</div>
