@props([
    'class' => 'mb-4',
])

@php
    $hasPrev = isset($prev) && ! $prev->isEmpty();
    $hasNext = isset($next) && ! $next->isEmpty();
@endphp

@if(! $hasPrev && ! $hasNext)
    <div {{ $attributes->class(['ui-period-nav', 'text-center', $class]) }}>
        <div class="ui-period-nav__title">{{ $slot }}</div>
    </div>
@else
    {{--
      Nawigacja: poprzedni | tytuł (slot) | następny
      Mobile (< md): tytuł na górze na całą szerokość, potem dwa przyciski obok siebie (50/50)
      md+: jeden rząd z wyrównaniem start | center | end
    --}}
    <div {{ $attributes->class(['ui-period-nav', 'row', 'g-3', 'align-items-center', $class]) }}>
        <div class="col-6 col-md-4 order-2 order-md-1 text-start">
            <div class="d-grid">{{ $prev }}</div>
        </div>
        <div class="col-12 col-md-4 order-1 order-md-2 text-center px-md-2">
            <div class="ui-period-nav__title">{{ $slot }}</div>
        </div>
        <div class="col-6 col-md-4 order-3 order-md-3 text-end">
            <div class="d-grid">{{ $next }}</div>
        </div>
    </div>
@endif
