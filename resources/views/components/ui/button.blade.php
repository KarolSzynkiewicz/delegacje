@props([
    'variant' => 'primary', // primary, ghost, danger, warning, success
    'type' => 'button',
    'href' => null,
])

@php
    $classes = 'btn';
    if ($variant === 'primary') {
        $classes .= ' btn-primary';
    } elseif ($variant === 'ghost') {
        $classes .= ' btn-outline-secondary';
    } elseif ($variant === 'danger') {
        $classes .= ' btn-danger';
    } elseif ($variant === 'warning') {
        $classes .= ' btn-warning';
    } elseif ($variant === 'success') {
        $classes .= ' btn-success';
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
