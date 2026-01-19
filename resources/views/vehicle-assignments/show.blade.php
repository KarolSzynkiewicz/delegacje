<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Przypisania Pojazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('employees.vehicles.index', $vehicleAssignment->employee_id) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicle-assignments.edit', $vehicleAssignment) }}"
                    routeName="vehicle-assignments.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Szczegóły Przypisania Pojazdu">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <a href="{{ route('employees.show', $vehicleAssignment->employee) }}" class="text-primary text-decoration-none">
                            {{ $vehicleAssignment->employee->full_name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Pojazd:">
                        <a href="{{ route('vehicles.show', $vehicleAssignment->vehicle) }}" class="text-primary text-decoration-none">
                            {{ $vehicleAssignment->vehicle->registration_number }} - {{ $vehicleAssignment->vehicle->brand }} {{ $vehicleAssignment->vehicle->model }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Rola w pojeździe:">
                        @php
                            $position = $vehicleAssignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                            $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                            $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                            $isDriver = $positionValue === 'driver';
                        @endphp
                        <x-ui.badge variant="{{ $isDriver ? 'success' : 'accent' }}">
                            {{ $positionLabel }}
                        </x-ui.badge>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data Rozpoczęcia:">{{ $vehicleAssignment->start_date->format('Y-m-d') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Data Zakończenia:">{{ $vehicleAssignment->end_date ? $vehicleAssignment->end_date->format('Y-m-d') : 'Bieżące' }}</x-ui.detail-item>
                    @if($vehicleAssignment->notes)
                    <x-ui.detail-item label="Uwagi:" :full-width="true">{{ $vehicleAssignment->notes }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
