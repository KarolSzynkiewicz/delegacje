@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Pojazd: {{ $vehicle->registration_number }}</h1>

            <div class="card">
                <div class="card-body">
                    @if($vehicle->image_path)
                        <div class="mb-4 text-center">
                            <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->registration_number }}" class="img-fluid rounded" style="max-width: 500px; max-height: 400px; object-fit: cover;">
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Numer Rejestracyjny</h5>
                            <p><strong>{{ $vehicle->registration_number }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Stan Techniczny</h5>
                            <p>
                                <span class="badge bg-{{ $vehicle->technical_condition == 'excellent' ? 'success' : ($vehicle->technical_condition == 'good' ? 'info' : ($vehicle->technical_condition == 'fair' ? 'warning' : 'danger')) }}">
                                    {{ ucfirst($vehicle->technical_condition) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Marka</h5>
                            <p>{{ $vehicle->brand ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Model</h5>
                            <p>{{ $vehicle->model ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Pojemność</h5>
                            <p>{{ $vehicle->capacity ?? '-' }} osób</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Przegląd Ważny Do</h5>
                            <p>
                                @if ($vehicle->inspection_valid_to)
                                    <span class="badge bg-{{ $vehicle->inspection_valid_to < now() ? 'danger' : 'success' }}">
                                        {{ $vehicle->inspection_valid_to->format('Y-m-d') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($vehicle->notes)
                        <div class="mb-3">
                            <h5>Notatki</h5>
                            <p>{{ $vehicle->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>

            {{-- Sekcja z przypisaniami --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Przypisania do pojazdu</h3>
                </div>
                <div class="card-body">
                    @if($assignments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Pracownik</th>
                                        <th>Rola</th>
                                        <th>Okres</th>
                                        <th>Status</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignments as $assignment)
                                        <tr>
                                            <td>
                                                <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary">
                                                    {{ $assignment->employee->full_name }}
                                                </a>
                                            </td>
                                            <td>
                                                @php
                                                    $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                    $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                    $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                                @endphp
                                                <span class="badge 
                                                    @if($positionValue === 'driver') bg-primary
                                                    @else bg-secondary
                                                    @endif">
                                                    {{ $positionLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $assignment->start_date->format('Y-m-d') }}
                                                @if($assignment->end_date)
                                                    - {{ $assignment->end_date->format('Y-m-d') }}
                                                @else
                                                    - ...
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                    $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                    $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                                @endphp
                                                <span class="badge 
                                                    @if($statusValue === 'active') bg-success
                                                    @elseif($statusValue === 'completed') bg-info
                                                    @elseif($statusValue === 'cancelled') bg-danger
                                                    @elseif($statusValue === 'in_transit') bg-warning
                                                    @elseif($statusValue === 'at_base') bg-secondary
                                                    @else bg-secondary
                                                    @endif">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('vehicle-assignments.show', $assignment) }}" class="btn btn-sm btn-info">Szczegóły</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Brak przypisań do tego pojazdu.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
