@props(['summary'])

@if($summary && $summary->hasAssignedEmployees())
@php
    $allHaveVehicle = $summary->allHaveVehicle();
    $employeesWithoutVehicle = $summary->getEmployeesWithoutVehicle();
    $overcrowdedVehicles = $summary->getOvercrowdedVehicles();
@endphp

<x-ui.card label="Auta">
    @if($allHaveVehicle)
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill text-success fs-3"></i>
            <div>
                <div class="fw-bold text-success">Status OK</div>
                <div class="text-muted small">Wszyscy mają auto</div>
            </div>
        </div>
        @if($overcrowdedVehicles->isNotEmpty())
            @foreach($overcrowdedVehicles as $vehicleData)
                <div class="alert alert-danger mt-2">
                    <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                    <div>
                        <div class="fw-bold text-danger">Alert Logistyczny</div>
                        <div class="text-muted small">{{ $vehicleData['vehicle_name'] }} jest przepełnione ({{ $vehicleData['usage'] }})</div>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        <div class="alert alert-danger">
            <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
            <div>
                <div class="fw-bold text-danger">Alert Logistyczny</div>
                <div class="text-muted small">Brakuje {{ $employeesWithoutVehicle->count() }} {{ $employeesWithoutVehicle->count() == 1 ? 'auta' : 'aut' }}</div>
            </div>
        </div>
        @if($overcrowdedVehicles->isNotEmpty())
            @foreach($overcrowdedVehicles as $vehicleData)
                <div class="alert alert-danger mt-2">
                    <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                    <div>
                        <div class="fw-bold text-danger">Alert Logistyczny</div>
                        <div class="text-muted small">{{ $vehicleData['vehicle_name'] }} jest przepełnione ({{ $vehicleData['usage'] }})</div>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</x-ui.card>
@endif
