{{--
    Pigułka metryk trasy — ten sam wygląd co wiersz „trail” w transfer-segment-compact-summary (ikona, km z przecinkiem, opcjonalnie czas i licznik).

    Kolejność treści: km · czas · licznik (jak w bloku „Zapisane” pod formularzem).

    @props accent: info | success
    @props stopWordSet: stops | route_points (słownictwo jak w trail: przystanki vs punkty trasy)
--}}
@props([
    'accent' => 'success',
    'distanceKm' => null,
    'durationSeconds' => null,
    'stopCount' => null,
    'stopWordSet' => 'stops',
])

@php
    $styles = [
        'info' => 'font-size: 0.72rem; background: rgba(14,165,233,0.1); color: #bae6fd; line-height: 1.45;',
        'success' => 'font-size: 0.72rem; background: rgba(34,197,94,0.12); color: #86efac; line-height: 1.45;',
    ];
    $pillStyle = $styles[$accent] ?? $styles['success'];
    $km = $distanceKm !== null && is_numeric($distanceKm) && (float) $distanceKm > 0 ? (float) $distanceKm : null;
    $dur = $durationSeconds !== null && (int) $durationSeconds > 0 ? (int) $durationSeconds : null;
    $h = $dur !== null ? (int) floor($dur / 3600) : null;
    $m = $dur !== null ? (int) floor(($dur % 3600) / 60) : null;
    $stops = $stopCount !== null ? (int) $stopCount : null;
    $hasAny = $km !== null || ($dur !== null && $m !== null) || ($stops !== null && $stops > 0);
@endphp
@if($hasAny)
    <span {{ $attributes->merge(['class' => 'badge rounded-pill text-start']) }} style="{{ $pillStyle }}">
        <i class="bi bi-signpost-2 me-1"></i>
        @if($km !== null)
            <span class="fw-semibold">{{ number_format($km, 1, ',', '') }} km</span>
        @endif
        @if($dur !== null && $m !== null)
            @if($km !== null)
                <span class="text-muted"> · </span>
            @endif
            <span class="fw-semibold">@if($h > 0){{ $h }}h @endif{{ $m }}min</span>
        @endif
        @if($stops !== null && $stops > 0)
            @if($km !== null || ($dur !== null && $m !== null))
                <span class="text-muted"> · </span>
            @endif
            {{ $stops }}
            @if($stopWordSet === 'route_points')
                @if($stops === 1)
                    punkt trasy
                @elseif($stops >= 2 && $stops <= 4)
                    punkty trasy
                @else
                    punktów trasy
                @endif
            @else
                @if($stops === 1)
                    przystanek
                @elseif($stops >= 2 && $stops <= 4)
                    przystanki
                @else
                    przystanków
                @endif
            @endif
        @endif
    </span>
@endif
