<div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filtry -->
            <div class="mb-4 bg-light rounded p-3 border">
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
                                <th class="text-start" style="cursor: pointer;" wire:click="sortBy('employee_id')">
                                    Pracownik
                                    @if($sortField === 'employee_id')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short"></i>
                                    @endif
                                </th>
                                <th class="text-start" style="cursor: pointer;" wire:click="sortBy('start_date')">
                                    Data rozpoczęcia
                                    @if($sortField === 'start_date')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short"></i>
                                    @endif
                                </th>
                                <th class="text-start" style="cursor: pointer;" wire:click="sortBy('end_date')">
                                    Data zakończenia
                                    @if($sortField === 'end_date')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-short"></i>
                                    @endif
                                </th>
                                <th class="text-start">Status</th>
                                <th class="text-start">Notatki</th>
                                <th class="text-start">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rotations as $rotation)
                                <tr wire:key="rotation-{{ $rotation->id }}">
                                    <td>
                                        <a href="{{ route('employees.show', $rotation->employee) }}" 
                                           class="text-primary text-decoration-none fw-medium">
                                            {{ $rotation->employee->full_name }}
                                        </a>
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
                                            
                                            $badgeClass = match($status) {
                                                'active' => 'bg-success',
                                                'scheduled' => 'bg-primary',
                                                'completed' => 'bg-secondary',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            @if($status === 'active') Aktywna
                                            @elseif($status === 'scheduled') Zaplanowana
                                            @elseif($status === 'completed') Zakończona
                                            @elseif($status === 'cancelled') Anulowana
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}</small>
                                    </td>
                                    <td>
                                        <x-action-buttons
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
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-3">
                        @if(!empty($search) || !empty($statusFilter))
                            Nie znaleziono rotacji spełniających kryteria wyszukiwania.
                        @else
                            Brak rotacji w systemie.
                        @endif
                    </p>
                    @if(empty($search) && empty($statusFilter))
                        <x-ui.button variant="primary" href="{{ route('rotations.create') }}">
                            <i class="bi bi-plus-circle"></i> Dodaj pierwszą rotację
                        </x-ui.button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
