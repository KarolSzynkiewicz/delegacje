<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Pojazd: {{ $vehicle->registration_number }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicles.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicles.edit', $vehicle) }}"
                    routeName="vehicles.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
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
                                $condition = \App\Enums\VehicleCondition::tryFrom($vehicle->technical_condition);
                                $badgeVariant = match($vehicle->technical_condition) {
                                    'excellent' => 'success',
                                    'good' => 'info',
                                    'fair' => 'warning',
                                    'poor' => 'warning',
                                    'workshop' => 'danger',
                                    default => 'info'
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $condition?->label() ?? ucfirst($vehicle->technical_condition) }}</x-ui.badge>
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

            <x-ui.card class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Przypisania do pojazdu</h5>
                    <div>
                        <a href="{{ route('vehicles.show', ['vehicle' => $vehicle->id, 'filter' => $filter === 'active' ? 'all' : 'active']) }}" 
                           class="btn btn-sm {{ $filter === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
                            {{ $filter === 'active' ? 'Aktywne' : 'Wszystkie' }}
                        </a>
                    </div>
                </div>
                @if($assignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
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
                                            <x-employee-cell :employee="$assignment->employee"  />
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
                                            <x-ui.button variant="ghost" href="{{ route('vehicle-assignments.show', $assignment) }}" class="btn-sm">Szczegóły</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($assignments->hasPages())
                        <div class="mt-3 pt-3 border-top">
                            {{ $assignments->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="inbox"
                        message="Brak przypisań do tego pojazdu."
                    />
                @endif
            </x-ui.card>

            <x-ui.card class="mt-4">
                <x-comments :commentable="$vehicle" />
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
