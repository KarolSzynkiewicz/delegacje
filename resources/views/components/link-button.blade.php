@props([
    'href',
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, link
    'size' => null, // null, sm, lg
    'outline' => false,
    'icon' => null, // Bootstrap icon name (bez bi- prefixu)
    'iconPosition' => 'left', // left, right
])

@php
    $classes = 'btn';
    
    // Variant
    $validVariants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'link'];
    $variant = in_array($variant, $validVariants) ? $variant : 'primary';
    
    if ($outline) {
        $classes .= ' btn-outline-' . $variant;
    } else {
        $classes .= ' btn-' . $variant;
    }
    
    // Size
    if ($size === 'sm') {
        $classes .= ' btn-sm';
    } elseif ($size === 'lg') {
        $classes .= ' btn-lg';
    }
    
    $iconClass = $icon ? 'bi bi-' . $icon : '';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon && $iconPosition === 'left')
        <i class="{{ $iconClass }} me-1"></i>
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right')
        <i class="{{ $iconClass }} ms-1"></i>
    @endif
</a>
