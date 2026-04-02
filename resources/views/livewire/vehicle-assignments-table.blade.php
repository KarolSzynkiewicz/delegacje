<div>
    <x-ui.card class="mb-4">
        @if($searchEmployee || $searchVehicle || $vehicleFilter || $statusFilter)
            <div class="d-flex justify-content-end mb-3">
                <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            </div>
        @endif

        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Pracownik</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchEmployee"
                    placeholder="Szukaj pracownika..."
                    class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Pojazd (tekst)</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchVehicle"
                    placeholder="Nr rej., marka, model..."
                    class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Status</label>
                <select
                    wire:model.live.debounce.300ms="statusFilter"
                    class="form-control form-select form-select-sm">
                    <option value="">Wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="scheduled">Przyszłe</option>
                    <option value="completed">Zakończone</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Pojazd</label>
                <select
                    wire:model.live.debounce.300ms="vehicleFilter"
                    class="form-control form-select form-select-sm">
                    <option value="">Wszystkie pojazdy</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->registration_number }}
                            @if($v->brand || $v->model)
                                — {{ trim($v->brand.' '.$v->model) }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pojazd</th>
                            <th>Pracownik</th>
                            <th>Rola</th>
                            <th>Od - Do</th>
                            <th>Status</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                            <tr wire:key="vehicle-assignment-{{ $assignment->id }}">
                                <td>
                                    @php $vehicle = $assignment->vehicle; @endphp
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="text-decoration-none fw-medium">
                                        <i class="bi bi-car-front me-1 text-muted"></i>{{ $vehicle->registration_number }}
                                        @if($vehicle->brand || $vehicle->model)
                                            <span class="text-muted small">({{ trim($vehicle->brand.' '.$vehicle->model) }})</span>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <x-employee-cell :employee="$assignment->employee" />
                                </td>
                                <td>
                                    @php
                                        $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                        $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                        $isDriver = $positionValue === 'driver';
                                    @endphp
                                    <x-ui.badge variant="{{ $isDriver ? 'success' : 'accent' }}">
                                        {{ $isDriver ? 'Kierowca' : 'Pasażer' }}
                                    </x-ui.badge>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $assignment->start_date->format('Y-m-d') }}
                                        –
                                        {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                    </small>
                                </td>
                                <td>
                                    @if($assignment->isScheduled())
                                        <x-ui.badge variant="info">Przyszłe</x-ui.badge>
                                    @elseif($assignment->isActive())
                                        <x-ui.badge variant="success">Aktywne</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="secondary">Zakończone</x-ui.badge>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-action-buttons
                                        viewRoute="{{ route('vehicle-assignments.show', $assignment) }}"
                                        editRoute="{{ route('vehicle-assignments.edit', $assignment) }}"
                                        deleteRoute="{{ route('vehicle-assignments.destroy', $assignment) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to przypisanie pojazdu?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="mt-3 pt-3 border-top">
                    {{ $assignments->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="car-front"
                message="Brak przypisań pojazdów"
            />
        @endif
    </x-ui.card>
</div>
