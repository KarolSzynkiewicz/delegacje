@props(['summary'])

@if($summary && $summary->hasAssignedEmployees())
@php
    $allHaveAccommodation = $summary->allHaveAccommodation();
    $employeesWithoutAccommodation = $summary->getEmployeesWithoutAccommodation();
@endphp

<div class="bg-success bg-opacity-10 rounded p-2 border border-success mb-2">
        <h5 class="small fw-bold text-dark mb-1">Domy</h5>
        @if($allHaveAccommodation)
            <div class="small text-success fw-semibold">
                <i class="bi bi-check-circle"></i> Wszyscy mają dom
            </div>
        @else
            <div class="small text-warning">
                Brakuje {{ $employeesWithoutAccommodation->count() }} {{ $employeesWithoutAccommodation->count() == 1 ? 'domu' : 'domów' }}
            </div>
        @endif
    </div>
</div>
@endif
