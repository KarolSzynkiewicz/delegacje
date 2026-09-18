<div class="rp-filter-panel__inner">
    <div class="rp-filter-panel__header" x-show="filterMode === 'all'">
        <span class="rp-filter-panel__title">Zawężanie listy</span>
        <button type="button" wire:click="clearFilters" @click="closeFilters()" class="rp-filter-panel__clear">
            Wyczyść
        </button>
    </div>
    <div class="rp-filter-panel__header" x-show="filterMode !== 'all'" x-cloak>
        <span class="rp-filter-panel__title" x-text="filterLabels[filterMode] || 'Filtr'"></span>
        <button type="button" class="rp-filter-panel__clear"
                @click="$wire.clearColumnFilter(filterMode)">
            Wyczyść
        </button>
    </div>

    <div class="rp-filter-join" x-show="filterMode === 'all'">
        <span class="rp-filter-hint">Kilka wartości w jednej sekcji = <strong>lub</strong> (Marek lub Krzyś). Między sekcjami zawsze <strong>i</strong> (przypisany i status).</span>
    </div>

    @if($this->isPlanQueue())
        <div class="rp-filter-locks" x-show="filterMode === 'all'">
            <span class="rp-filter-hint">Zablokowane w Planie</span>
            <div class="d-flex flex-column gap-1 mt-1 mb-2">
                @foreach($filterChips as $chip)
                    @if(! empty($chip['locked']))
                        <span class="rp-active-filters__chip is-locked">
                            <span class="rp-active-filters__chip-text">{{ $chip['label'] }}</span>
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Każdy widget raz. Filtry (mode=all) i Filtruj na kolumnie (mode=klucz) pokazują ten sam węzeł. --}}

    @unless($this->isPlanQueue())
    <div class="rp-filter-section" x-show="filterMode === 'all' || filterMode === 'status'" data-tg-col-filter="status">
        <button type="button" x-show="filterMode === 'all'" @click="openStatus = !openStatus" class="rp-filter-section__head">
            <span><i class="bi bi-flag me-1 opacity-75"></i>Status zadań</span>
            <i class="bi" :class="openStatus ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div class="rp-filter-section__body" x-show="filterMode === 'status' || (filterMode === 'all' && openStatus)">
            @include('livewire.partials.tg-filter-status')
        </div>
    </div>
    @endunless

    <div class="rp-filter-section" x-show="filterMode === 'all' || filterMode === 'assigned_to' || filterMode === 'created_by'">
        <button type="button" x-show="filterMode === 'all'" @click="openVisibility = !openVisibility" class="rp-filter-section__head">
            <span><i class="bi bi-person-check me-1 opacity-75"></i>Przypisanie</span>
            <i class="bi" :class="openVisibility ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div class="rp-filter-section__body" x-show="filterMode === 'assigned_to' || filterMode === 'created_by' || (filterMode === 'all' && openVisibility)">
            @unless($this->isPlanQueue())
            <div x-show="filterMode === 'all' || filterMode === 'assigned_to'" data-tg-col-filter="assigned_to">
                <span class="rp-filter-hint">Przypisany do</span>
                @include('livewire.partials.tg-filter-people', [
                    'field' => 'assignedFilter',
                    'selected' => $assignedFilters,
                    'toggle' => 'toggleAssignedFilter',
                    'clear' => 'clearAssignedFilters',
                    'hint' => 'Nic = wszyscy. Kilka osób = lub.',
                ])
                <span class="rp-filter-hint mt-2 d-block">Szukaj po osobie</span>
                @include('livewire.partials.tg-filter-search-person')
            </div>
            @endunless
            <div class="mt-2" x-show="filterMode === 'all' || filterMode === 'created_by'" data-tg-col-filter="created_by">
                <span class="rp-filter-hint">Utworzono przez</span>
                @include('livewire.partials.tg-filter-people', [
                    'field' => 'createdByFilter',
                    'selected' => $createdByFilters,
                    'toggle' => 'toggleCreatedByFilter',
                    'clear' => 'clearCreatedByFilters',
                    'hint' => 'Nic = wszyscy. Kilka osób = lub.',
                ])
            </div>
        </div>
    </div>

    @if($this->usesWorkItems())
    <div class="rp-filter-section" x-show="filterMode === 'all' || filterMode === 'type'" data-tg-col-filter="type">
        <button type="button" x-show="filterMode === 'all'" @click="openType = !openType" class="rp-filter-section__head">
            <span><i class="bi bi-tags me-1 opacity-75"></i>Typ pracy</span>
            <i class="bi" :class="openType ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div class="rp-filter-section__body" x-show="filterMode === 'type' || (filterMode === 'all' && openType)">
            @include('livewire.partials.tg-filter-types')
        </div>
    </div>
    @endif

    <div class="rp-filter-section" x-show="filterMode === 'all' || filterMode === 'name' || filterMode === 'category'" data-tg-col-filter="name">
        <button type="button" x-show="filterMode === 'all'" @click="openSearch = !openSearch" class="rp-filter-section__head">
            <span><i class="bi bi-search me-1 opacity-75"></i>Szukaj szczegółowo</span>
            <i class="bi" :class="openSearch ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div class="rp-filter-section__body" x-show="filterMode === 'name' || filterMode === 'category' || (filterMode === 'all' && openSearch)">
            <div class="mb-2" x-show="filterMode === 'all' || filterMode === 'name'">
                @include('livewire.partials.tg-filter-search-task')
            </div>
            <div x-show="filterMode === 'all' || filterMode === 'category'" data-tg-col-filter="category">
                @include('livewire.partials.tg-filter-search-category')
            </div>
        </div>
    </div>

    <div class="rp-filter-section" x-show="filterMode === 'all' || filterMode === 'priority' || filterMode === 'due_date'">
        <button type="button" x-show="filterMode === 'all'" @click="openMore = !openMore" class="rp-filter-section__head">
            <span><i class="bi bi-sliders me-1 opacity-75"></i>Priorytet i termin</span>
            <i class="bi" :class="openMore ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div class="rp-filter-section__body" x-show="filterMode === 'priority' || filterMode === 'due_date' || (filterMode === 'all' && openMore)">
            <div class="mb-2" x-show="filterMode === 'all' || filterMode === 'priority'" data-tg-col-filter="priority">
                @include('livewire.partials.tg-filter-priority')
            </div>
            <div x-show="filterMode === 'all' || filterMode === 'due_date'" data-tg-col-filter="due_date">
                @include('livewire.partials.tg-filter-due')
            </div>
        </div>
    </div>

    <div class="rp-filter-section" x-show="filterMode === 'all'">
        <button type="button" @click="openGroup = !openGroup" class="rp-filter-section__head">
            <span><i class="bi bi-collection me-1 opacity-75"></i>Grupowanie</span>
            <i class="bi" :class="openGroup ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openGroup" class="rp-filter-section__body">
            <div class="rp-filter-chips">
                <button type="button" wire:click="setGroupBy('')"
                        class="rp-filter-chip {{ $groupBy === '' ? 'is-active' : '' }}">Bez grupowania</button>
                @php
                    $groupFields = ['status' => 'Status'];
                    if ($this->usesWorkItems()) {
                        $groupFields['type'] = 'Typ pracy';
                    }
                    $groupFields += [
                        'sprint' => 'Sprint',
                        'category' => 'Kategoria',
                        'assigned_to' => 'Przypisany',
                        'priority' => 'Priorytet',
                    ];
                @endphp
                @foreach($groupFields as $gf => $gl)
                    @if($this->isLockedToSprint() && $gf === 'sprint')
                        @continue
                    @endif
                    <button type="button" wire:click="setGroupBy('{{ $gf }}')"
                            class="rp-filter-chip {{ $groupBy === $gf ? 'is-active' : '' }}">{{ $gl }}</button>
                @endforeach
            </div>
        </div>
    </div>
</div>
