<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Szczegóły Przypisania Pojazdu</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold">Pracownik:</dt>
                        <dd>{{ $vehicleAssignment->employee->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Pojazd:</dt>
                        <dd>{{ $vehicleAssignment->vehicle->registration_number }} - {{ $vehicleAssignment->vehicle->brand }} {{ $vehicleAssignment->vehicle->model }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Rola w pojeździe:</dt>
                        <dd>
                            @php
                                $position = $vehicleAssignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($positionValue === 'driver') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $positionLabel }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Data Rozpoczęcia:</dt>
                        <dd>{{ $vehicleAssignment->start_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Data Zakończenia:</dt>
                        <dd>{{ $vehicleAssignment->end_date ? $vehicleAssignment->end_date->format('Y-m-d') : 'Bieżące' }}</dd>
                    </div>
                    @if($vehicleAssignment->notes)
                    <div class="md:col-span-2">
                        <dt class="font-semibold">Uwagi:</dt>
                        <dd>{{ $vehicleAssignment->notes }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-6">
                    <a href="{{ route('vehicle-assignments.edit', $vehicleAssignment) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                    <a href="{{ route('employees.vehicles.index', $vehicleAssignment->employee_id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
