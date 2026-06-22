<div>
    <x-ui.card>
        @if(session('success'))
            <x-ui.alert variant="success" dismissible>{{ session('success') }}</x-ui.alert>
        @endif

        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label small fw-semibold mb-1">
                        <i class="bi bi-search me-1"></i> Szukaj pracownika / spółki
                    </label>
                    <input type="text" id="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Imię, nazwisko, spółka, NIP...">
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label small fw-semibold mb-1">Status</label>
                    <select id="statusFilter" wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="active">Aktywne</option>
                        <option value="scheduled">Zaplanowane</option>
                        <option value="completed">Zakończone</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="w-100 btn-sm">
                        <i class="bi bi-x-circle"></i> Wyczyść
                    </x-ui.button>
                </div>
                @if($assignments->total() > 0)
                    <div class="col-md-3 text-end">
                        <small class="text-muted"><strong>{{ $assignments->total() }}</strong> przypisań</small>
                    </div>
                @endif
            </div>
        </div>

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
