@props(['summary'])

@if($summary && $summary->hasAssignedEmployees())
@php
    $allHaveAccommodation = $summary->allHaveAccommodation();
    $employeesWithoutAccommodation = $summary->getEmployeesWithoutAccommodation();
    $overcrowdedAccommodations = $summary->getOvercrowdedAccommodations();
@endphp

<div class="bg-success bg-opacity-10 rounded p-2 border border-success mb-2">
        <h5 class="small fw-bold text-dark mb-1">Domy</h5>
        @if($allHaveAccommodation)
            <div class="small text-success fw-semibold mb-1">
                <i class="bi bi-check-circle"></i> Wszyscy mają dom
            </div>
            @if($overcrowdedAccommodations->isNotEmpty())
                <div class="small text-danger fw-semibold">
                    <i class="bi bi-exclamation-triangle"></i> Przepełnione:
                    @foreach($overcrowdedAccommodations as $accommodationData)
                        <div class="ms-2">
                            {{ $accommodationData['accommodation']->name }} 
                            ({{ $accommodationData['usage'] }})
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="small text-warning">
                Brakuje {{ $employeesWithoutAccommodation->count() }} {{ $employeesWithoutAccommodation->count() == 1 ? 'domu' : 'domów' }}
            </div>
            @if($overcrowdedAccommodations->isNotEmpty())
                <div class="small text-danger fw-semibold mt-1">
                    <i class="bi bi-exclamation-triangle"></i> Przepełnione:
                    @foreach($overcrowdedAccommodations as $accommodationData)
                        <div class="ms-2">
                            {{ $accommodationData['accommodation']->name }} 
                            ({{ $accommodationData['usage'] }})
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
@endif
