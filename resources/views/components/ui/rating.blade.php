@props([
    'score' => null,
    'evaluation' => null,
    'showEmpty' => false,
])

@php
    $eval = $evaluation;
    $resolvedScore = $score;
    if ($resolvedScore === null && $eval) {
        $resolvedScore = $eval->average_score;
    }
    $hasScore = $resolvedScore !== null && $resolvedScore !== '';
@endphp

@if($hasScore)
    <span {{ $attributes->merge(['class' => 'ui-rating'.($eval ? ' ui-rating--tip' : '')]) }}>
        <i class="bi bi-star-fill" aria-hidden="true"></i>
        <span class="ui-rating__value font-mono">{{ number_format((float) $resolvedScore, 1, ',', '') }}</span>
        @if($eval)
            <span class="ui-rating__tip" role="tooltip">
                <span class="ui-rating__tip-head">
                    Ostatnia ocena
                    @if($eval->created_at)
                        <span class="ui-rating__tip-date">· {{ $eval->created_at->format('d.m.Y') }}</span>
                    @endif
                </span>
                <span class="ui-rating__tip-row">Zaangażowanie <span class="font-mono">{{ $eval->engagement }}</span></span>
                <span class="ui-rating__tip-row">Umiejętności <span class="font-mono">{{ $eval->skills }}</span></span>
                <span class="ui-rating__tip-row">Porządek <span class="font-mono">{{ $eval->orderliness }}</span></span>
                <span class="ui-rating__tip-row">Zachowanie <span class="font-mono">{{ $eval->behavior }}</span></span>
                <span class="ui-rating__tip-avg">
                    Średnia {{ number_format((float) $eval->average_score, 2, ',', '') }}
                    @if($eval->relationLoaded('createdBy') && $eval->createdBy)
                        <span class="ui-rating__tip-by">Ocenił: {{ $eval->createdBy->name }}</span>
                    @endif
                </span>
            </span>
        @endif
    </span>
@elseif($showEmpty)
    <span {{ $attributes->merge(['class' => 'ui-rating ui-rating--empty']) }}>
        <i class="bi bi-star" aria-hidden="true"></i>
        Brak oceny
    </span>
@endif
