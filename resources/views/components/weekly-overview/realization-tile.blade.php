@props(['summary'])

@if($summary && $summary->hasData())
@php
    $totalNeeded = $summary->getTotalNeeded();
    $totalAssigned = $summary->getTotalAssigned();
    $percentage = $summary->getProgressPercentage();
    $progressClass = $summary->getProgressClass();
    $textClass = $summary->getTextClass();
    $missingRoles = $summary->getMissingRoles();
    $excessRoles = $summary->getExcessRoles();
    $hasIssues = $summary->hasIssues();
@endphp

<div class="bg-info bg-opacity-10 rounded p-2 border border-info mb-2">
        <h5 class="small fw-bold text-dark mb-2">Realizacja</h5>
        
        {{-- Progress bar z liczbą --}}
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
        
        {{-- Informacje tekstowe --}}
        <div>
            @if(!$hasIssues)
                <div class="small text-success fw-semibold">
                    <i class="bi bi-check-circle"></i> Wszystko OK – pełny skład
                </div>
            @else
                {{-- Braki --}}
                @if(!empty($missingRoles))
                    <div class="small text-warning mb-1">
                        @foreach($missingRoles as $roleDetail)
                            <div>Za mało {{ Str::lower($roleDetail['role']->name) }}: {{ $roleDetail['missing'] }}</div>
                        @endforeach
                    </div>
                @endif
                {{-- Nadmiary --}}
                @if(!empty($excessRoles))
                    <div class="small text-danger fw-semibold">
                        @foreach($excessRoles as $roleDetail)
                            <div>
                                <i class="bi bi-exclamation-triangle"></i> Za dużo {{ Str::lower($roleDetail['role']->name) }}: +{{ $roleDetail['excess'] }}
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endif
