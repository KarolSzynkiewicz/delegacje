@props([
    'variant' => 'info', // success, danger, warning, info, accent
    'href' => null,
    'route' => null,
    'routeParams' => [],
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
    $classes = 'badge badge-clickable badge-' . $bootstrapVariant;
    
    // Określ URL
    $url = $href ?? ($route ? route($route, $routeParams) : '#');
    $tip = $attributes->get('title');
    $attributes = $attributes->except('title');
@endphp

<a href="{{ $url }}" {{ $attributes->merge(['class' => $classes]) }} @if(filled($tip)) data-tip="{{ $tip }}" @endif>
    {{ $slot }}
</a>
