@php
    $hasSlot = isset($slot) && trim($slot);
@endphp

{{-- Mobile: tytuł na górze, akcje w kolumnie (pełna szerokość) --}}
<div class="d-flex flex-column d-md-none gap-3 w-100">
    <div class="text-center px-1">
        <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
    </div>

    <div class="d-flex flex-column gap-2 w-100 align-items-stretch">
        @isset($left)
            <div class="w-100">{{ $left }}</div>
        @endisset

        @isset($right)
            <div class="w-100">{{ $right }}</div>
        @endisset

        @if(isset($slot) && trim($slot))
            <div class="w-100">{{ $slot }}</div>
        @endif
    </div>
</div>

{{--
  Desktop: siatka 1fr | auto | 1fr — lewa i prawa kolumna mają tę samą szerokość,
  więc tytuł jest wizualnie na środku paska (nie „przesunięty” przy szerokim prawym slocie).
--}}
<div
    class="d-none d-md-grid align-items-center gap-2 gap-md-3 w-100"
    style="grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);"
>
    <div class="d-flex align-items-center justify-content-start gap-2 min-w-0">
        @isset($left)
            {{ $left }}
        @endisset
    </div>

    <div class="text-center px-2">
        <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
    </div>

    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap min-w-0">
        @isset($right)
            {{ $right }}
        @endisset
        @if($hasSlot)
            {{ $slot }}
        @endif
    </div>
</div>
