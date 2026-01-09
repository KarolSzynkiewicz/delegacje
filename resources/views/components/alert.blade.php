@props([
    'type' => 'info', // success, danger, warning, info, primary, secondary
    'dismissible' => false,
    'icon' => null, // Bootstrap icon name (bez bi- prefixu)
])

@php
    $classes = 'alert';
    
    // Semantyczne kolory Bootstrap
    $validTypes = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
    $type = in_array($type, $validTypes) ? $type : 'info';
    $classes .= ' alert-' . $type;
    
    if ($dismissible) {
        $classes .= ' alert-dismissible fade show';
    }
    
    $iconClass = $icon ? 'bi bi-' . $icon . ' me-2' : '';
@endphp

<div {{ $attributes->merge(['class' => $classes, 'role' => 'alert']) }}>
    @if($icon)
        <i class="{{ $iconClass }}"></i>
    @endif
    
    {{ $slot }}
    
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
