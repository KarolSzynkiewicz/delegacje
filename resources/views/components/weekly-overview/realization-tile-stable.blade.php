@props(['requirementsSummary'])

@if($requirementsSummary)
@php
    $totalNeeded = $requirementsSummary['total_needed'] ?? 0;
    $totalAssigned = $requirementsSummary['total_assigned'] ?? null;
    $totalAssignedMin = $requirementsSummary['total_assigned_min'] ?? 0;
    $totalAssignedMax = $requirementsSummary['total_assigned_max'] ?? 0;
    $isStable = $requirementsSummary['is_stable'] ?? false;
    $totalMissing = $requirementsSummary['total_missing'] ?? 0;
    $totalExcess = $requirementsSummary['total_excess'] ?? 0;
    $roleDetails = $requirementsSummary['role_details'] ?? [];
    
    $missingRoles = collect($roleDetails)->filter(fn($r) => $r['missing'] > 0)->values();
    $excessRoles = collect($roleDetails)->filter(fn($r) => $r['excess'] > 0)->values();
    $hasIssues = $missingRoles->isNotEmpty() || $excessRoles->isNotEmpty();
    
    if ($isStable && $totalAssigned !== null) {
        $percentage = $totalNeeded > 0 ? round(($totalAssigned / $totalNeeded) * 100, 0) : 0;
        $progressClass = $percentage >= 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
        $textClass = $percentage >= 100 ? 'text-success' : ($percentage >= 50 ? 'text-warning' : 'text-danger');
    } else {
        $percentage = null;
        $progressClass = 'bg-warning';
        $textClass = 'text-warning';
    }
@endphp

<div class="bg-info bg-opacity-10 rounded p-2 border border-info mb-2">
    <h5 class="small fw-bold text-dark mb-2">Realizacja</h5>
    
    @if($isStable && $totalAssigned !== null)
        {{-- Stable - show exact values --}}
        <div class="mb-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-bold text-dark">{{ $totalAssigned }}/{{ $totalNeeded }}</span>
                <span class="small fw-semibold {{ $textClass }}">
                    {{ $percentage }}%
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    @else
        {{-- Variable - show range --}}
        <div class="mb-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-bold text-dark">
                    {{ $totalAssignedMin }}-{{ $totalAssignedMax }}/{{ $totalNeeded }}
                    <span class="badge bg-warning ms-1" title="Pokrycie zmienia się w trakcie tygodnia">
                        <i class="bi bi-arrow-left-right"></i> Zmienne
                    </span>
                </span>
                <span class="small fw-semibold text-warning">
                    <i class="bi bi-exclamation-triangle"></i> Sprawdź dni
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $totalNeeded > 0 ? round(($totalAssignedMax / $totalNeeded) * 100, 0) : 0 }}%" aria-valuenow="{{ $totalAssignedMax }}" aria-valuemin="0" aria-valuemax="{{ $totalNeeded }}"></div>
            </div>
        </div>
    @endif
    
    {{-- Informacje tekstowe --}}
    <div>
        @if(!$hasIssues && $isStable)
            <div class="small text-success fw-semibold">
                <i class="bi bi-check-circle"></i> Wszystko OK – pełny skład
            </div>
        @else
            {{-- Braki --}}
            @if($missingRoles->isNotEmpty())
                <div class="small text-warning mb-1">
                    @foreach($missingRoles as $roleDetail)
                        <div>
                            Za mało {{ Str::lower($roleDetail['role']->name) }}: 
                            @if($roleDetail['is_stable'])
                                {{ $roleDetail['missing'] }}
                            @else
                                do {{ $roleDetail['missing'] }} (zmienne)
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            {{-- Nadmiary --}}
            @if($excessRoles->isNotEmpty())
                <div class="small text-danger fw-semibold">
                    @foreach($excessRoles as $roleDetail)
                        <div>
                            <i class="bi bi-exclamation-triangle"></i> Za dużo {{ Str::lower($roleDetail['role']->name) }}: 
                            @if($roleDetail['is_stable'])
                                +{{ $roleDetail['excess'] }}
                            @else
                                do +{{ $roleDetail['excess'] }} (zmienne)
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
@endif
