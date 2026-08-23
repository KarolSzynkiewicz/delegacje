<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    <x-data-table-filters
        :count="$assignments->total()"
        :has-filters="(bool) (!empty($search) || !empty($statusFilter))"
        item-label="przypisań"
    >
        <div class="dt-filter-field dt-filter-field--wide">
            <label for="search" class="form-label small">
                <i class="bi bi-search me-1"></i> Szukaj pracownika / spółki
            </label>
            <input type="text" id="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Imię, nazwisko, spółka, NIP...">
        </div>
        <div class="dt-filter-field">
            <label for="statusFilter" class="form-label small">Status</label>
            <select id="statusFilter" wire:model.live="statusFilter" class="form-select">
                <option value="">Wszystkie</option>
                <option value="active">Aktywne</option>
                <option value="scheduled">Zaplanowane</option>
                <option value="completed">Zakończone</option>
            </select>
        </div>
    </x-data-table-filters>

    <x-ui.card>
        @if($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-start">Pracownik</th>
                            <th class="text-start">Spółka</th>
                            <x-livewire.sortable-header field="start_date" :sortField="$sortField" :sortDirection="$sortDirection">Od</x-livewire.sortable-header>
                            <x-livewire.sortable-header field="end_date" :sortField="$sortField" :sortDirection="$sortDirection">Do</x-livewire.sortable-header>
                            <th class="text-start">Status</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                            <tr wire:key="company-assignment-{{ $assignment->id }}">
                                <td><x-employee-cell :employee="$assignment->employee" /></td>
                                <td>
                                    <a href="{{ route('companies.show', $assignment->company) }}" class="text-primary text-decoration-none">
                                        {{ $assignment->company->name }}
                                    </a>
                                </td>
                                <td><small class="text-muted">{{ $assignment->start_date->format('Y-m-d') }}</small></td>
                                <td><small class="text-muted">{{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '-' }}</small></td>
                                <td>
                                    @php
                                        if ($assignment->isCurrentlyActive()) {
                                            $statusLabel = 'Aktywne';
                                            $badgeVariant = 'success';
                                        } elseif ($assignment->isPast()) {
                                            $statusLabel = 'Zakończone';
                                            $badgeVariant = 'accent';
                                        } elseif ($assignment->isScheduled()) {
                                            $statusLabel = 'Zaplanowane';
                                            $badgeVariant = 'info';
                                        } else {
                                            $statusLabel = 'Nieznany';
                                            $badgeVariant = 'accent';
                                        }
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('company-assignments.show', $assignment) }}"
                                        editRoute="{{ route('company-assignments.edit', $assignment) }}"
                                        deleteRoute="{{ route('company-assignments.destroy', $assignment) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to przypisanie?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="mt-3">{{ $assignments->links() }}</div>
            @endif
        @else
            <x-ui.empty-state
                icon="building"
                :message="!empty($search) || !empty($statusFilter) ? 'Nie znaleziono przypisań spełniających kryteria.' : 'Brak przypisań do spółek.'"
                :has-filters="!empty($search) || !empty($statusFilter)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && empty($statusFilter))
                    <x-ui.button variant="primary" href="{{ route('company-assignments.create') }}">
                        <i class="bi bi-plus-circle"></i> Dodaj pierwsze przypisanie
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
