@props([
    'driverId' => null,
    /** Uczestnicy wyjazdu (miejsca pasażerskie). */
    'employees' => [],
    /** Pełna lista pracowników do rozwiązania imienia kierowcy (dropdown może wskazywać osobę spoza listy biletów). */
    'driverPool' => null,
    'vehicle' => null,
    'accent' => 'info',
])

@php
    $employees = collect($employees);
    $pool = collect($driverPool ?? [])->concat($employees)->keyBy(fn ($e) => (int) $e->id)->values();
    $capRaw = $vehicle && isset($vehicle->capacity) ? (int) $vehicle->capacity : 0;
    // capacity w bazie = miejsca (w tym kierowca), sensowny zakres 2–16
    $totalSeats = $capRaw >= 2 ? min(16, max(2, $capRaw)) : 5;
    $driver = $driverId ? $pool->first(fn ($e) => (int) $e->id === (int) $driverId) : null;
    $passengers = $employees->filter(fn ($e) => (int) $e->id !== (int) $driverId)->values();
    $passengerSeatCount = max(0, $totalSeats - 1);
    $border = $accent === 'success' ? 'rgba(34,197,94,0.35)' : 'rgba(14,165,233,0.35)';
    $bg = $accent === 'success' ? 'rgba(34,197,94,0.05)' : 'rgba(14,165,233,0.06)';
    $seatBox = 'text-center p-2 rounded-3 border flex-grow-1';
    $seatSkin = 'min-width: 7.5rem; max-width: 11rem; border-color: rgba(148,163,184,0.28) !important; background: rgba(15,23,42,0.45);';
@endphp

<div {{ $attributes->class('rounded-3 p-3 mt-2') }} style="border: 1px solid {{ $border }}; background: {{ $bg }};">
    <div class="small fw-semibold mb-2"><i class="bi bi-layout-three-columns me-1"></i> Rozkład miejsc w pojeździe</div>
    <div class="d-flex flex-wrap gap-2 justify-content-start align-items-stretch">
        <div class="{{ $seatBox }}" style="{{ $seatSkin }} border-left: 3px solid rgba(251,191,36,0.65) !important;">
            <div class="text-secondary mb-1"><i class="bi bi-steering-wheel" style="font-size: 1.15rem;"></i></div>
            <div class="small text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: .04em;">Kierowca</div>
            <div class="small fw-semibold text-truncate" title="{{ $driver?->full_name }}">{{ $driver?->full_name ?? '—' }}</div>
        </div>
        @for($i = 0; $i < $passengerSeatCount; $i++)
            @php $p = $passengers->get($i); @endphp
            <div class="{{ $seatBox }}" style="{{ $seatSkin }}">
                <div class="text-secondary mb-1"><i class="bi bi-person" style="font-size: 1.15rem;"></i></div>
                <div class="small text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: .04em;">Pasażer {{ $i + 1 }}</div>
                <div class="small fw-semibold text-truncate" title="{{ $p?->full_name }}">{{ $p ? $p->full_name : 'wolne' }}</div>
            </div>
        @endfor
    </div>
    @if($vehicle && $capRaw > 0)
        <div class="small text-muted mt-2 mb-0" style="font-size: 0.72rem;">
            Pojemność (z bazy): {{ $capRaw }} miejsc (w tym kierowca).
        </div>
    @elseif(! $vehicle || ! $capRaw)
        <div class="small text-muted mt-2 mb-0" style="font-size: 0.72rem;">Wybierz pojazd — pokażemy miejsca wg pola pojemności.</div>
    @endif
</div>
