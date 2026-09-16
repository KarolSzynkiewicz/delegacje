@props([
    'mix' => [],
])

@php
    $mix = $mix + [4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0];
    $labels = [
        4 => 'Ekspert',
        3 => 'Samodzielny fachowiec',
        2 => 'Podstawowa samodzielność',
        1 => 'Przyuczenie',
        0 => 'Nieustalone',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'sr-mix font-mono']) }} title="Rozkład seniority">
    @foreach([4, 3, 2, 1] as $level)
        <span class="sr-mix__item sr-mix__item--{{ $level }}" title="{{ $labels[$level] }}">{{ (int) $mix[$level] }}×{{ $level }}</span>
    @endforeach
    @if((int) ($mix[0] ?? 0) > 0)
        <span class="sr-mix__item sr-mix__item--0" title="{{ $labels[0] }}">{{ (int) $mix[0] }}×?</span>
    @endif
</span>
