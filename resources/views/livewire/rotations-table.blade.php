<div>
    <x-ui.card>
        @if(session('success'))
            <x-ui.alert variant="success" dismissible>
                {{ session('success') }}
            </x-ui.alert>
        @endif

        <!-- Filtry -->
        <div class="mb-4 pb-3 border-top border-bottom">
                <div class="row g-2 align-items-end">
                    <!-- Wyszukiwanie po pracowniku -->
                    <div class="col-md-4">
                        <label for="search" class="form-label small fw-semibold mb-1">
                            <i class="bi bi-search me-1"></i> Szukaj pracownika
                        </label>
                        <input type="text" 
                               id="search"
                               wire:model.live.debounce.300ms="search" 
                               class="form-control form-control-sm" 
                               placeholder="Imię lub nazwisko...">
                    </div>

                    <!-- Filtrowanie po statusie -->
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label small fw-semibold mb-1">
                            Status
                        </label>
                        <select id="statusFilter" 
                                wire:model.live="statusFilter" 
                                class="form-select form-select-sm">
                            <option value="">Wszystkie</option>
                            <option value="scheduled">Zaplanowana</option>
                            <option value="active">Aktywna</option>
                            <option value="completed">Zakończona</option>
                            <option value="cancelled">Anulowana</option>
                        </select>
                    </div>

                    <!-- Przycisk wyczyść -->
                    <div class="col-md-2">
                        <x-ui.button variant="ghost" wire:click="clearFilters" class="w-100 btn-sm">
                            <i class="bi bi-x-circle"></i> Wyczyść
                        </x-ui.button>
                    </div>

                    <!-- Informacja o liczbie wyników -->
                    <div class="col-md-3 text-end">
                        @if($rotations->total() > 0)
                            <small class="text-muted">
                                Znaleziono: <strong>{{ $rotations->total() }}</strong>
                            </small>
                        @endif
                    </div>
                </div>
        </div>

        @if($rotations->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <x-livewire.sortable-header field="employee_id" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Pracownik
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="start_date" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Data rozpoczęcia
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="end_date" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Data zakończenia
                                </x-livewire.sortable-header>
                                <th class="text-start">Status</th>
                                <th class="text-start">Notatki</th>
                                <th class="text-start">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rotations as $rotation)
                                <tr wire:key="rotation-{{ $rotation->id }}">
                                    <td>
                                        <x-employee-cell :employee="$rotation->employee"  />
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $rotation->start_date->format('Y-m-d') }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $rotation->end_date->format('Y-m-d') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $status = $rotation->status;
                                            $today = now()->toDateString();
                                            
                                            // Oblicz status na podstawie dat, jeśli status nie jest ustawiony
                                            if (empty($status) || $status !== 'cancelled') {
                                                if ($rotation->start_date->toDateString() > $today) {
                                                    $status = 'scheduled';
                                                } elseif ($rotation->end_date->toDateString() < $today) {
                                                    $status = 'completed';
                                                } else {
                                                    $status = 'active';
                                                }
                                            }
                                            
                                            $badgeVariant = match($status) {
                                                'active' => 'success',
                                                'scheduled' => 'info',
                                                'completed' => 'accent',
                                                'cancelled' => 'danger',
                                                default => 'accent'
                                            };
                                            
                                            $badgeLabel = match($status) {
                                                'active' => 'Aktywna',
                                                'scheduled' => 'Zaplanowana',
                                                'completed' => 'Zakończona',
                                                'cancelled' => 'Anulowana',
                                                default => '-'
                                            };
                                        @endphp
                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $badgeLabel }}</x-ui.badge>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}</small>
                                    </td>
                                    <td>
                                        <x-ui.action-buttons
                                            viewRoute="{{ route('employees.rotations.show', [$rotation->employee, $rotation]) }}"
                                            editRoute="{{ route('employees.rotations.edit', [$rotation->employee, $rotation]) }}"
                                            deleteRoute="{{ route('employees.rotations.destroy', [$rotation->employee, $rotation]) }}"
                                            deleteMessage="Czy na pewno chcesz usunąć tę rotację?"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($rotations->hasPages())
                    <div class="mt-3">
                        {{ $rotations->links() }}
                    </div>
                @endif
        @else
            <x-ui.empty-state 
                icon="inbox"
                :message="!empty($search) || !empty($statusFilter) ? 'Nie znaleziono rotacji spełniających kryteria wyszukiwania.' : 'Brak rotacji w systemie.'"
                :has-filters="!empty($search) || !empty($statusFilter)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && empty($statusFilter))
                    <x-ui.button variant="primary" href="{{ route('rotations.create') }}">
                        <i class="bi bi-plus-circle"></i> Dodaj pierwszą rotację
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
