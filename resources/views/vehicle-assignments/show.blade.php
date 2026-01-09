<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Szczegóły Przypisania Pojazdu</h2>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Pracownik:</dt>
                                <dd>
                                    <a href="{{ route('employees.show', $vehicleAssignment->employee) }}" class="text-primary text-decoration-none">
                                        {{ $vehicleAssignment->employee->full_name }}
                                    </a>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Pojazd:</dt>
                                <dd>
                                    <a href="{{ route('vehicles.show', $vehicleAssignment->vehicle) }}" class="text-primary text-decoration-none">
                                        {{ $vehicleAssignment->vehicle->registration_number }} - {{ $vehicleAssignment->vehicle->brand }} {{ $vehicleAssignment->vehicle->model }}
                                    </a>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Rola w pojeździe:</dt>
                                <dd>
                                    @php
                                        $position = $vehicleAssignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                        $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                        $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                        $isDriver = $positionValue === 'driver';
                                    @endphp
                                    <span class="badge {{ $isDriver ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $positionLabel }}
                                    </span>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Data Rozpoczęcia:</dt>
                                <dd>{{ $vehicleAssignment->start_date->format('Y-m-d') }}</dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Data Zakończenia:</dt>
                                <dd>{{ $vehicleAssignment->end_date ? $vehicleAssignment->end_date->format('Y-m-d') : 'Bieżące' }}</dd>
                            </div>
                            @if($vehicleAssignment->notes)
                            <div class="col-12 mb-3">
                                <dt class="fw-semibold mb-1">Uwagi:</dt>
                                <dd>{{ $vehicleAssignment->notes }}</dd>
                            </div>
                            @endif
                        </dl>

                        <div class="mt-4 pt-3 border-top">
                            <a href="{{ route('vehicle-assignments.edit', $vehicleAssignment) }}" class="btn btn-primary me-2">
                                <i class="bi bi-pencil me-1"></i> Edytuj
                            </a>
                            <a href="{{ route('employees.vehicles.index', $vehicleAssignment->employee_id) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Powrót
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
