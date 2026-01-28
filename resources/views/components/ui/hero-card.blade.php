@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'primary',
    'imagePosition' => 'right', // left, right, top, bottom
    'variant' => 'default', // default, gradient, outlined
])

@php
    $classes = 'hero-card';
    if ($variant === 'gradient') {
        $classes .= ' hero-card-gradient';
    } elseif ($variant === 'outlined') {
        $classes .= ' hero-card-outlined';
    }
    
    $imagePositionClass = 'hero-card-image-' . $imagePosition;
    
    $iconColorClass = 'text-' . $iconColor;
@endphp

<div {{ $attributes->merge(['class' => $classes . ' ' . $imagePositionClass]) }}>
    <div class="hero-card-content">
        @if($icon)
            <div class="hero-card-icon {{ $iconColorClass }}">
                <i class="bi bi-{{ $icon }}"></i>
            </div>
        @endif
        
        @if($title)
            <h2 class="hero-card-title">{{ $title }}</h2>
        @endif
        
        @if($subtitle)
            <p class="hero-card-subtitle">{{ $subtitle }}</p>
        @endif
        
        <div class="hero-card-body">
            {{ $slot }}
        </div>
    </div>
    
    @if(isset($image))
        <div class="hero-card-image">
            {{ $image }}
        </div>
    @endif
</div>
