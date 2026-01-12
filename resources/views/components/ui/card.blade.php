@props([
    'label' => null,
    'variant' => 'default', // default, hover, elevated
])

@php
    $classes = 'card';
    if ($variant === 'hover') {
        $classes .= ' card-hover';
    } elseif ($variant === 'elevated') {
        $classes .= ' card-elevated';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($label)
        <span class="card-label">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
