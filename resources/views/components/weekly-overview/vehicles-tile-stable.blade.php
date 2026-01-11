@props(['stability', 'weekStart', 'weekEnd'])

@if($stability && $stability['employees']->isNotEmpty())
@php
    $employees = $stability['assigned_employees'] ?? collect();
    $vehicles = $stability['vehicles'] ?? [];
    
    $allHaveVehicle = $employees->every(function($emp) {
        return $emp['vehicle_stable'] && $emp['vehicle'] !== null;
    });
    
    $employeesWithoutVehicle = $employees->filter(function($emp) {
        return !$emp['vehicle_stable'] || $emp['vehicle'] === null;
    });
    
    $overcrowdedVehicles = collect($vehicles)->filter(function($veh) {
        return $veh['max_occupancy'] > ($veh['vehicle']->capacity ?? 0);
    });
@endphp

<div class="bg-primary bg-opacity-10 rounded p-2 border border-primary mb-2">
    <h5 class="small fw-bold text-dark mb-1">Auta</h5>
    @if($allHaveVehicle && $employees->every(fn($e) => $e['vehicle_stable']))
        <div class="small text-success fw-semibold mb-1">
            <i class="bi bi-check-circle"></i> Wszyscy mają auto
        </div>
        @if($overcrowdedVehicles->isNotEmpty())
            <div class="small text-danger fw-semibold">
                <i class="bi bi-exclamation-triangle"></i> Przepełnione:
                @foreach($overcrowdedVehicles as $vehicleData)
                    <div class="ms-2">
                        {{ trim($vehicleData['vehicle']->brand . ' ' . $vehicleData['vehicle']->model . ' ' . $vehicleData['vehicle']->registration_number) }} 
                        ({{ $vehicleData['min_occupancy'] }}-{{ $vehicleData['max_occupancy'] }}/{{ $vehicleData['vehicle']->capacity ?? '?' }})
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="small text-warning fw-medium mb-1">
            @if($employeesWithoutVehicle->isNotEmpty())
                {{ $employeesWithoutVehicle->count() }} {{ $employeesWithoutVehicle->count() == 1 ? 'osobie brakuje auta' : 'osobom brakuje auta' }}
            @else
                <span class="badge bg-warning">
                    <i class="bi bi-arrow-left-right"></i> Auta zmienne w tygodniu
                </span>
            @endif
        </div>
        @if($overcrowdedVehicles->isNotEmpty())
            <div class="small text-danger fw-semibold mb-1">
                <i class="bi bi-exclamation-triangle"></i> Przepełnione:
                @foreach($overcrowdedVehicles as $vehicleData)
                    <div class="ms-2">
                        {{ trim($vehicleData['vehicle']->brand . ' ' . $vehicleData['vehicle']->model . ' ' . $vehicleData['vehicle']->registration_number) }} 
                        ({{ $vehicleData['min_occupancy'] }}-{{ $vehicleData['max_occupancy'] }}/{{ $vehicleData['vehicle']->capacity ?? '?' }})
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endif
