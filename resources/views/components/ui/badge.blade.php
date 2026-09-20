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
        'accent' => 'accent',
        'secondary' => 'secondary',
        default => 'info'
    };
    $classes = 'badge badge-' . $bootstrapVariant;
    $tip = $attributes->get('title');
    $attributes = $attributes->except('title');
@endphp

<span {{ $attributes->merge(['class' => $classes]) }} @if(filled($tip)) data-tip="{{ $tip }}" @endif>
    {{ $slot }}
</span>
