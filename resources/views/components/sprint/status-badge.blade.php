@props(['sprint'])

@php
    $chip = $sprint->statusChip();
@endphp

<span {{ $attributes->class(['tg-status-badge', 'tg-mono', $chip['cls']]) }}>{{ $chip['icon'] }} {{ $sprint->statusLabel() }}</span>
