<div>
    <x-ui.card class="mb-4">
            <x-ui.table-header title="Filtry" class="mb-3">
                <x-slot name="actions">
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                </x-slot>
            </x-ui.table-header>
            
            <div class="row g-3">
                <!-- Pracownik -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pracownik</label>
                    <input type="text" wire:model.live.debounce.300ms="searchEmployee" 
                        placeholder="Szukaj pracownika..."
                        class="form-control form-control-sm">
                </div>

                <!-- Pojazd -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pojazd</label>
                    <input type="text" wire:model.live.debounce.300ms="searchVehicle" 
                        placeholder="Nr rej., marka, model..."
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
    </x-ui.card>

    @php
        $groupedAssignments = $assignments->groupBy(function($assignment) {
            return $assignment->vehicle->id;
        });
    @endphp

    @foreach($groupedAssignments as $vehicleId => $vehicleAssignments)
        @php
            $vehicle = $vehicleAssignments->first()->vehicle;
        @endphp
        <x-ui.card class="mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-semibold">
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="text-decoration-none text-dark">
                        <i class="bi bi-car-front me-2"></i>{{ $vehicle->registration_number }}
                        @if($vehicle->brand)
                            <small class="text-muted">({{ $vehicle->brand }}{{ $vehicle->model ? ' ' . $vehicle->model : '' }})</small>
                        @endif
                    </a>
                </h5>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-start">Pracownik</th>
                                <th class="text-start">Rola</th>
                                <th class="text-start">Od - Do</th>
                                <th class="text-start">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicleAssignments->sortBy('start_date') as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                            {{ $assignment->employee->full_name }}
                                        </a>
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
                                            {{ $assignment->start_date->format('Y-m-d') }} - 
                                            {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                        </small>
                                    </td>
                                    <td>
                                        <x-ui.action-buttons
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
            </div>
        </x-ui.card>
    @endforeach

    @if($assignments->hasPages())
        <div class="mt-3">
            {{ $assignments->links() }}
        </div>
    @endif

    @if($assignments->isEmpty())
        <x-ui.card>
            <x-ui.empty-state 
                icon="car-front"
                message="Brak przypisań pojazdów"
            />
        </x-ui.card>
    @endif
</div>
