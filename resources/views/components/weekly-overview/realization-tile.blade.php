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

<x-ui.card>
    <span class="card-label">Realizacja</span>
    <div class="stat-value">{{ $totalAssigned }}/{{ $totalNeeded }}</div>
    <x-ui.progress value="{{ $percentage }}" max="100" />
    
    {{-- Informacje tekstowe --}}
    <div class="mt-3">
        @if(!$hasIssues)
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                <div>
                    <div class="fw-bold text-success">Status OK</div>
                    <div class="text-muted small">Wszystko OK – pełny skład</div>
                </div>
            </div>
        @else
            {{-- Braki --}}
            @if(!empty($missingRoles))
                @foreach($missingRoles as $roleDetail)
                    <div class="alert alert-danger mb-2">
                        <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                        <div>
                            <div class="fw-bold text-danger">Alert Logistyczny</div>
                            <div class="text-muted small">Za mało {{ Str::lower($roleDetail['role']->name) }}: {{ $roleDetail['missing'] }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
            {{-- Nadmiary --}}
            @if(!empty($excessRoles))
                @foreach($excessRoles as $roleDetail)
                    <div class="alert alert-danger mb-2">
                        <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                        <div>
                            <div class="fw-bold text-danger">Alert Logistyczny</div>
                            <div class="text-muted small">Za dużo {{ Str::lower($roleDetail['role']->name) }}: +{{ $roleDetail['excess'] }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif
    </div>
</x-ui.card>
</div>
@endif
