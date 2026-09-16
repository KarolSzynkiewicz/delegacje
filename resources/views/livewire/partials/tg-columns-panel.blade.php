<div class="rp-filter-panel__inner">
    <div class="rp-filter-panel__header">
        <span class="rp-filter-panel__title">Kolumny</span>
        <button type="button" @click="open=false" class="rp-filter-panel__clear">Zamknij</button>
    </div>
    <p class="rp-filter-hint mb-2">Zaznaczone są widoczne na liście. Nazwy nie da się ukryć.</p>
    <div class="rp-filter-scroll">
        @foreach($this->columnPickerKeys() as $colKey)
            @php $col = $availableColumns[$colKey] ?? null @endphp
            @if(! $col)
                @continue
            @endif
            @if($this->isLockedToSprint() && $colKey === 'sprint')
                @continue
            @endif
            @if(! $this->usesWorkItems() && $colKey === 'type')
                @continue
            @endif
            @php
                $colLockedByGroup = $groupBy !== '' && $colKey === $groupBy;
                $colChecked = in_array($colKey, $visibleColumns) && ! $colLockedByGroup;
                $colDisabled = ($col['always'] ?? false) || $colLockedByGroup;
            @endphp
            <button type="button"
                    wire:click="toggleColumn('{{ $colKey }}')"
                    {{ $colDisabled ? 'disabled' : '' }}
                    class="rp-filter-option {{ $colChecked ? 'is-active' : '' }}"
                    style="{{ $colDisabled ? 'opacity:.45;cursor:not-allowed' : '' }}">
                <span class="rp-filter-check {{ $colChecked ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                <span class="rp-filter-option__label">{{ $col['label'] }}@if($colLockedByGroup)<span class="text-muted"> · grupowanie</span>@endif</span>
            </button>
        @endforeach
    </div>
</div>
