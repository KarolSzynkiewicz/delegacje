@props([
    'type' => 'secondary', // primary, secondary, success, danger, warning, info
    'size' => null, // null, sm, lg
])

@php
    $classes = 'badge';
    
    // Semantyczne kolory Bootstrap
    $validTypes = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
    $type = in_array($type, $validTypes) ? $type : 'secondary';
    $classes .= ' bg-' . $type;
    
    // Rozmiar (opcjonalny)
    if ($size === 'sm') {
        $classes .= ' badge-sm';
    } elseif ($size === 'lg') {
        $classes .= ' badge-lg';
    }
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
