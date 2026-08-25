<div class="rp-filter-panel__inner">
    <div class="rp-filter-panel__header">
        <span class="rp-filter-panel__title">Zawężanie listy</span>
        <button type="button" wire:click="clearFilters" @click="open=false" class="rp-filter-panel__clear">
            Wyczyść
        </button>
    </div>

    <div class="d-md-none mb-2">
        <input type="text"
               wire:model.live.debounce.300ms="searchTask"
               class="form-control form-control-sm rp-filter-input"
               placeholder="Szukaj zadania…"
               @click.stop>
    </div>

    {{-- 1. Status --}}
    <div class="rp-filter-section">
        <button type="button" @click="openStatus = !openStatus" class="rp-filter-section__head">
            <span><i class="bi bi-flag me-1 opacity-75"></i>Status zadań</span>
            <i class="bi" :class="openStatus ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openStatus" class="rp-filter-section__body">
            <div class="rp-filter-chips">
                <button type="button" wire:click="$set('status', '')"
                        class="rp-filter-chip {{ $status === '' ? 'is-active' : '' }}">Aktywne</button>
                <button type="button" wire:click="$set('status', 'closed')"
                        class="rp-filter-chip {{ $status === 'closed' ? 'is-active' : '' }}">Zamknięte</button>
                <button type="button" wire:click="$set('status', 'all')"
                        class="rp-filter-chip {{ $status === 'all' ? 'is-active' : '' }}">Wszystkie</button>
            </div>
        </div>
    </div>

    {{-- 2. Przypisanie --}}
    <div class="rp-filter-section">
        <button type="button" @click="openVisibility = !openVisibility" class="rp-filter-section__head">
            <span><i class="bi bi-person-check me-1 opacity-75"></i>Przypisanie</span>
            <i class="bi" :class="openVisibility ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openVisibility" class="rp-filter-section__body">
            <span class="rp-filter-hint">Przypisany do</span>
            <select wire:model.live="assignedFilter" class="form-select form-select-sm rp-filter-input" @click.stop>
                <option value="">Wszyscy</option>
                <option value="me">Ja ({{ auth()->user()->name }})</option>
                @foreach($allUsers as $u)
                    @if($u->id !== auth()->id())
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endif
                @endforeach
            </select>
            <span class="rp-filter-hint mt-2 d-block">Utworzono przez</span>
            <select wire:model.live="createdByFilter" class="form-select form-select-sm rp-filter-input" @click.stop>
                <option value="">Wszyscy</option>
                <option value="me">Ja ({{ auth()->user()->name }})</option>
                @foreach($allUsers as $u)
                    @if($u->id !== auth()->id())
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>

    {{-- 3. Typ pracy — checkboxy zamiast pojedynczego przełącznika "oddzwonienia
         rekrutacji"; działa jednakowo dla każdego typu work itemu. --}}
    @if($this->usesWorkItems())
    <div class="rp-filter-section">
        <button type="button" @click="openType = !openType" class="rp-filter-section__head">
            <span><i class="bi bi-tags me-1 opacity-75"></i>Typ pracy</span>
            <i class="bi" :class="openType ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openType" class="rp-filter-section__body">
            <div class="rp-filter-chips">
                @foreach(\App\Enums\WorkItemType::cases() as $wt)
                    <button type="button" wire:click="toggleType('{{ $wt->value }}')"
                            class="rp-filter-option {{ in_array($wt->value, $selectedTypes, true) ? 'is-active' : '' }}">
                        <span class="rp-filter-check {{ in_array($wt->value, $selectedTypes, true) ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                        <span class="rp-filter-option__label"><i class="bi {{ $wt->icon() }} me-1 opacity-75"></i>{{ $wt->label() }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- 4. Szukaj szczegółowo --}}
    <div class="rp-filter-section">
        <button type="button" @click="openSearch = !openSearch" class="rp-filter-section__head">
            <span><i class="bi bi-search me-1 opacity-75"></i>Szukaj szczegółowo</span>
            <i class="bi" :class="openSearch ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openSearch" class="rp-filter-section__body">
            <div class="rp-filter-range">
                <div>
                    <span class="rp-filter-hint">Kategoria</span>
                    <input type="text" wire:model.live.debounce.300ms="searchCategory"
                           class="form-control form-control-sm rp-filter-input" placeholder="np. Logistyka" @click.stop>
                </div>
                <div>
                    <span class="rp-filter-hint">Osoba</span>
                    <input type="text" wire:model.live.debounce.300ms="searchAssignedTo"
                           class="form-control form-control-sm rp-filter-input" placeholder="Imię i nazwisko" @click.stop>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Grupowanie --}}
    <div class="rp-filter-section">
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

    {{-- 6. Kolumny --}}
    <div class="rp-filter-section">
        <button type="button" @click="openColumns = !openColumns" class="rp-filter-section__head">
            <span><i class="bi bi-layout-three-columns me-1 opacity-75"></i>Widoczne kolumny</span>
            <i class="bi" :class="openColumns ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openColumns" class="rp-filter-section__body">
            <div class="rp-filter-scroll">
                @foreach($availableColumns as $colKey => $col)
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
    </div>
</div>
