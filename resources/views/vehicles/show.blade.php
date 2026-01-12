<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Pojazd: {{ $vehicle->registration_number }}</h2>
    </x-slot>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <x-ui.card label="Szczegóły Pojazdu">
                @if($vehicle->image_path)
                    <div class="mb-4 text-center">
                        <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->registration_number }}" class="img-fluid rounded">
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
                            @php
                                $badgeVariant = match($vehicle->technical_condition) {
                                    'excellent' => 'success',
                                    'good' => 'info',
                                    'fair' => 'warning',
                                    'poor' => 'danger',
                                    default => 'info'
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($vehicle->technical_condition) }}</x-ui.badge>
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
                                <x-ui.badge variant="{{ $vehicle->inspection_valid_to < now() ? 'danger' : 'success' }}">
                                    {{ $vehicle->inspection_valid_to->format('Y-m-d') }}
                                </x-ui.badge>
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

                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <x-ui.button variant="primary" href="{{ route('vehicles.edit', $vehicle) }}">Edytuj</x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('vehicles.index') }}">Wróć do Listy</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card label="Przypisania do pojazdu" class="mt-4">
                @if($assignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Rola</th>
                                    <th>Okres</th>
                                    <th>Status</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $assignment)
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
                                                $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                            @endphp
                                            <x-ui.badge variant="{{ $positionValue === 'driver' ? 'accent' : 'info' }}">
                                                {{ $positionLabel }}
                                            </x-ui.badge>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $assignment->start_date->format('Y-m-d') }}
                                                @if($assignment->end_date)
                                                    - {{ $assignment->end_date->format('Y-m-d') }}
                                                @else
                                                    - ...
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @php
                                                $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                                $badgeVariant = match($statusValue) {
                                                    'active' => 'success',
                                                    'completed' => 'info',
                                                    'cancelled' => 'danger',
                                                    'in_transit' => 'warning',
                                                    'at_base' => 'info',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                        </td>
                                        <td class="text-end">
                                            <x-ui.button variant="ghost" href="{{ route('vehicle-assignments.show', $assignment) }}">Szczegóły</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted fs-1 d-block mb-2"></i>
                        <p class="text-muted mb-0">Brak przypisań do tego pojazdu.</p>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
