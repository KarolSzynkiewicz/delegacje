@props([
    'label' => null,
    'variant' => 'default', // default, hover, elevated
    'reveal' => false, // scroll/load entrance via global .cl-reveal
])

@php
    $classes = 'card';
    if ($variant === 'hover') {
        $classes .= ' card-hover';
    } elseif ($variant === 'elevated') {
        $classes .= ' card-elevated';
    }
    if ($reveal) {
        $classes .= ' cl-reveal';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($label)
        <span class="card-label">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
