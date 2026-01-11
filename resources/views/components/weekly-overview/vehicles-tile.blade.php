@props(['summary'])

@if($summary && $summary->hasAssignedEmployees())
@php
    $overcrowdedVehicles = $summary->getOvercrowdedVehicles();
@endphp

<div class="bg-primary bg-opacity-10 rounded p-2 border border-primary mb-2">
    <h5 class="small fw-bold text-dark mb-1">Auta</h5>
    <div class="small text-success fw-semibold mb-1">
        <i class="bi bi-check-circle"></i> Wszyscy mają auto
    </div>
    @if($overcrowdedVehicles->isNotEmpty())
        <div class="small text-danger fw-semibold">
            <i class="bi bi-exclamation-triangle"></i> Przepełnione:
            @foreach($overcrowdedVehicles as $vehicleData)
                <div class="ms-2">
                    {{ $vehicleData['vehicle_name'] }} ({{ $vehicleData['usage'] }})
                </div>
            @endforeach
        </div>
    @endif
</div>
@endif
