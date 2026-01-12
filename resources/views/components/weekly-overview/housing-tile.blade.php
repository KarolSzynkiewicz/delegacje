@props(['summary'])

@if($summary && $summary->hasAssignedEmployees())
@php
    $allHaveAccommodation = $summary->allHaveAccommodation();
    $employeesWithoutAccommodation = $summary->getEmployeesWithoutAccommodation();
    $overcrowdedAccommodations = $summary->getOvercrowdedAccommodations();
@endphp

<x-ui.card label="Domy">
    @if($allHaveAccommodation)
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill text-success fs-3"></i>
            <div>
                <div class="fw-bold text-success">Status OK</div>
                <div class="text-muted small">Wszyscy mają dom</div>
            </div>
        </div>
        @if($overcrowdedAccommodations->isNotEmpty())
            @foreach($overcrowdedAccommodations as $accommodationData)
                <div class="alert alert-danger mt-2">
                    <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                    <div>
                        <div class="fw-bold text-danger">Alert Logistyczny</div>
                        <div class="text-muted small">{{ $accommodationData['accommodation']->name }} jest przepełnione ({{ $accommodationData['usage'] }})</div>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        <div class="alert alert-danger">
            <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
            <div>
                <div class="fw-bold text-danger">Alert Logistyczny</div>
                <div class="text-muted small">Brakuje {{ $employeesWithoutAccommodation->count() }} {{ $employeesWithoutAccommodation->count() == 1 ? 'domu' : 'domów' }}</div>
            </div>
        </div>
        @if($overcrowdedAccommodations->isNotEmpty())
            @foreach($overcrowdedAccommodations as $accommodationData)
                <div class="alert alert-danger mt-2">
                    <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                    <div>
                        <div class="fw-bold text-danger">Alert Logistyczny</div>
                        <div class="text-muted small">{{ $accommodationData['accommodation']->name }} jest przepełnione ({{ $accommodationData['usage'] }})</div>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</x-ui.card>
@endif
