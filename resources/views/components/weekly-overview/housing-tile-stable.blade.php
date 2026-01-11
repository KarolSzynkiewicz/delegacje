@props(['stability', 'weekStart', 'weekEnd'])

@if($stability && $stability['employees']->isNotEmpty())
@php
    $employees = $stability['assigned_employees'] ?? collect();
    $accommodations = $stability['accommodations'] ?? [];
    
    $allHaveAccommodation = $employees->every(function($emp) {
        return $emp['accommodation_stable'] && $emp['accommodation'] !== null;
    });
    
    $employeesWithoutAccommodation = $employees->filter(function($emp) {
        return !$emp['accommodation_stable'] || $emp['accommodation'] === null;
    });
    
    $overcrowdedAccommodations = collect($accommodations)->filter(function($acc) {
        return $acc['max_occupancy'] > ($acc['accommodation']->capacity ?? 0);
    });
@endphp

<div class="bg-success bg-opacity-10 rounded p-2 border border-success mb-2">
    <h5 class="small fw-bold text-dark mb-1">Domy</h5>
    @if($allHaveAccommodation && $employees->every(fn($e) => $e['accommodation_stable']))
        <div class="small text-success fw-semibold mb-1">
            <i class="bi bi-check-circle"></i> Wszyscy mają dom
        </div>
        @if($overcrowdedAccommodations->isNotEmpty())
            <div class="small text-danger fw-semibold">
                <i class="bi bi-exclamation-triangle"></i> Przepełnione:
                @foreach($overcrowdedAccommodations as $accommodationData)
                    <div class="ms-2">
                        {{ $accommodationData['accommodation']->name }} 
                        ({{ $accommodationData['min_occupancy'] }}-{{ $accommodationData['max_occupancy'] }}/{{ $accommodationData['accommodation']->capacity ?? '?' }})
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="small text-warning">
            @if($employeesWithoutAccommodation->isNotEmpty())
                Brakuje {{ $employeesWithoutAccommodation->count() }} {{ $employeesWithoutAccommodation->count() == 1 ? 'domu' : 'domów' }}
            @else
                <span class="badge bg-warning">
                    <i class="bi bi-arrow-left-right"></i> Domy zmienne w tygodniu
                </span>
            @endif
        </div>
        @if($overcrowdedAccommodations->isNotEmpty())
            <div class="small text-danger fw-semibold mt-1">
                <i class="bi bi-exclamation-triangle"></i> Przepełnione:
                @foreach($overcrowdedAccommodations as $accommodationData)
                    <div class="ms-2">
                        {{ $accommodationData['accommodation']->name }} 
                        ({{ $accommodationData['min_occupancy'] }}-{{ $accommodationData['max_occupancy'] }}/{{ $accommodationData['accommodation']->capacity ?? '?' }})
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endif
