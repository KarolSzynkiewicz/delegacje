<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">
                @isset($employee)
                    Pojazdy pracownika: {{ $employee->full_name }}
                @else
                    Wszystkie przypisania pojazdów
                @endisset
            </h2>
            @isset($employee)
                <x-ui.button variant="primary" href="{{ route('employees.vehicles.create', $employee) }}">
                    <i class="bi bi-plus-circle"></i> Przypisz Pojazd
                </x-ui.button>
            @endisset
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            @isset($employee)
                {{-- Widok dla konkretnego pracownika - bez Livewire --}}
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        @if($assignments->count() > 0)
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-start">Pojazd</th>
                                            <th class="text-start">Rola</th>
                                            <th class="text-start">Od - Do</th>
                                            <th class="text-start">Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($assignments as $assignment)
                                            <tr>
                                                <td>{{ $assignment->vehicle->registration_number }} ({{ $assignment->vehicle->brand }})</td>
                                                <td>
                                                    @php
                                                        $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                        $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                        $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                                    @endphp
                                                    @if($positionValue === 'driver')
                                                        <span class="badge bg-primary">{{ $positionLabel }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $positionLabel }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $assignment->start_date->format('Y-m-d') }} - 
                                                        {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                                    </small>
                                                </td>
                                                <td>
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
                                <div class="mt-3">
                                    {{ $assignments->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                <p class="text-muted mb-3">Brak przypisanych pojazdów.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Globalny widok - z Livewire i filtrowaniem --}}
                <livewire:vehicle-assignments-table />
            @endisset
        </div>
    </div>
</x-app-layout>
