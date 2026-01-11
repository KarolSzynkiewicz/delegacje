<div>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fs-5 fw-semibold text-dark mb-0">Filtry</h3>
                <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </button>
            </div>
            
            <div class="row g-3">
                <!-- Pracownik -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pracownik</label>
                    <input type="text" wire:model.live.debounce.300ms="searchEmployee" 
                        placeholder="Szukaj pracownika..."
                        class="form-control form-control-sm">
                </div>

                <!-- Mieszkanie -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Mieszkanie</label>
                    <input type="text" wire:model.live.debounce.300ms="searchAccommodation" 
                        placeholder="Nazwa, adres..."
                        class="form-control form-control-sm">
                </div>

                <!-- Data od -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Data od</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                </div>

                <!-- Data do -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Data do</label>
                    <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Pracownik</th>
                            <th class="text-start">Mieszkanie</th>
                            <th class="text-start">Od - Do</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td>
                                    <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                        {{ $assignment->employee->full_name }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('accommodations.show', $assignment->accommodation) }}" class="text-primary text-decoration-none">
                                        {{ $assignment->accommodation->name }}
                                    </a>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $assignment->start_date->format('Y-m-d') }} - 
                                        {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                    </small>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('accommodation-assignments.show', $assignment) }}"
                                        editRoute="{{ route('accommodation-assignments.edit', $assignment) }}"
                                        deleteRoute="{{ route('accommodation-assignments.destroy', $assignment) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to przypisanie mieszkania?"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-house-x"></i>
                                        <p class="text-muted small fw-medium mb-0">Brak przypisań mieszkań</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($assignments->hasPages())
                <div class="mt-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
