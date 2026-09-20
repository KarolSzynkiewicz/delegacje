@php
    $statusOptions = [
        \App\Models\Sprint::BOARD_ACTIVE => 'Aktywny',
        \App\Models\Sprint::BOARD_UPCOMING => 'Nadchodzący',
        \App\Models\Sprint::BOARD_LATER => 'Później',
        \App\Models\Sprint::BOARD_CLOSED => 'Zakończony',
    ];
@endphp

<div>
    @if($flash)
        <x-ui.alert variant="success" title="OK" dismissible class="mb-3">
            {{ $flash }}
        </x-ui.alert>
    @endif

    <x-ui.errors />

    <x-data-table
        :paginator="$sprints"
        :has-filters="$hasFilters"
    >
        <x-slot:filters>
            <x-data-table-filters :count="$sprints->total()">
                <x-data-table-search
                    wire:model.live.debounce.300ms="search"
                    placeholder="Szukaj: nazwa, cel…"
                    wide
                />
                <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Status sprintu">
                    @foreach($statusOptions as $value => $label)
                        <button
                            type="button"
                            wire:click="toggleStatus('{{ $value }}')"
                            class="btn btn-outline-secondary {{ in_array($value, $statusFilters, true) ? 'active' : '' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search !== '')
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @php
                $defaultStatuses = [\App\Models\Sprint::BOARD_ACTIVE, \App\Models\Sprint::BOARD_UPCOMING];
                $statusChanged = collect($statusFilters)->sort()->values()->all() !== collect($defaultStatuses)->sort()->values()->all();
            @endphp
            @if($statusChanged)
                <x-data-table-filter-chip
                    :label="'Status: '.collect($statusFilters)->map(fn ($v) => $statusOptions[$v] ?? $v)->join(', ')"
                    wire:click="resetStatusFilters"
                />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th>
                    <button type="button" wire:click="sortBy('name')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        Nazwa
                        @if($sortField === 'name')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th>Status</th>
                <th>
                    <button type="button" wire:click="sortBy('start_date')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        Termin
                        @if($sortField === 'start_date')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th>Postęp</th>
                <th>Uczestnicy</th>
                <th>Kategorie</th>
                <th></th>
            </tr>
        </x-slot:head>

        <x-slot:body>
            @foreach ($sprints as $sprint)
                @include('livewire.partials.sprints-row', ['sprint' => $sprint, 'canMutate' => $canMutate])
            @endforeach
        </x-slot:body>

        <x-slot:cards>
            @foreach ($sprints as $sprint)
                @include('livewire.partials.sprints-row-card', ['sprint' => $sprint, 'canMutate' => $canMutate])
            @endforeach
        </x-slot:cards>

        <x-slot:empty>
            <x-ui.empty-state
                icon="calendar3"
                message="Brak sprintów w tym filtrze."
                :has-filters="$hasFilters"
                clear-filters-action="wire:clearFilters"
            >
                @if($hasFilters)
                    <x-ui.button variant="ghost" wire:click="clearFilters">
                        Wyczyść filtry
                    </x-ui.button>
                @elseif($hasAnySprints)
                    <p class="small text-muted mb-0">Włącz „Zakończony” albo „Później”, albo dodaj nowy sprint.</p>
                @else
                    <x-ui.button
                        variant="primary"
                        href="{{ route('sprints.create') }}"
                        routeName="sprints.create"
                        action="create"
                    >
                        Dodaj pierwszy sprint
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>

    @include('livewire.partials.sprint-close-modal')
</div>
