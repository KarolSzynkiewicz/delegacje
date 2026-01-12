@props([
    'variant' => 'info', // success, danger, warning, info, accent
])

@php
    // Mapowanie variantów na klasy Bootstrap
    $bootstrapVariant = match($variant) {
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'accent' => 'accent', // własna klasa
        default => 'info'
    };
    $classes = 'badge badge-' . $bootstrapVariant;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
