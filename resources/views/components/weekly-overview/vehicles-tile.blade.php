@props(['summary'])

@if($summary && $summary->hasAssignedEmployees())
@php
    $allHaveVehicle = $summary->allHaveVehicle();
    $employeesWithoutVehicle = $summary->getEmployeesWithoutVehicle();
    $weekData = $summary->getWeekData();
@endphp

<div class="bg-primary bg-opacity-10 rounded p-2 border border-primary mb-2">
        <h5 class="small fw-bold text-dark mb-1">Auta</h5>
        @if($allHaveVehicle)
            <div class="small text-success fw-semibold">
                <i class="bi bi-check-circle"></i> Wszyscy mają auto
            </div>
        @else
            <div class="small text-warning fw-medium mb-1">
                {{ $employeesWithoutVehicle->count() }} {{ $employeesWithoutVehicle->count() == 1 ? 'osobie brakuje auta' : 'osobom brakuje auta' }}
            </div>
            <div>
                @foreach($employeesWithoutVehicle as $employeeData)
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-dark">
                            <a href="{{ route('employees.show', $employeeData['employee']) }}" class="text-primary text-decoration-none">
                                {{ $employeeData['employee']->full_name }}
                            </a>
                        </span>
                        <a href="{{ route('employees.vehicles.create', ['employee' => $employeeData['employee'], 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Przypisz auto
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif
