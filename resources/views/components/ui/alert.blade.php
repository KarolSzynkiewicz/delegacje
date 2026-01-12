@props([
    'variant' => 'info', // info, success, danger, warning
    'icon' => null,
    'title' => null,
    'dismissible' => false,
])

@php
    $classes = 'alert';
    $iconClass = '';
    $titleClass = '';
    
    if ($variant === 'success') {
        $classes .= ' alert-success';
        $iconClass = 'bi-check-circle-fill text-success';
        $titleClass = 'text-success';
        $defaultIcon = 'bi-check-circle-fill';
    } elseif ($variant === 'danger') {
        $classes .= ' alert-danger';
        $iconClass = 'bi-shield-lock-fill text-danger';
        $titleClass = 'text-danger';
        $defaultIcon = 'bi-shield-lock-fill';
    } elseif ($variant === 'warning') {
        $classes .= ' alert-warning';
        $iconClass = 'bi-exclamation-triangle-fill text-warning';
        $titleClass = 'text-warning';
        $defaultIcon = 'bi-exclamation-triangle-fill';
    } else {
        $classes .= ' alert-info';
        $iconClass = 'bi-info-circle-fill text-primary';
        $titleClass = 'text-primary';
        $defaultIcon = 'bi-info-circle-fill';
    }
    
    $finalIcon = $icon ?? $defaultIcon;
@endphp

<div {{ $attributes->merge(['class' => $classes . ($dismissible ? ' alert-dismissible fade show' : '')]) }} role="alert">
    <i class="{{ $finalIcon }} {{ $iconClass }} fs-3"></i>
    <div>
        @if($title)
            <div class="fw-bold {{ $titleClass }}">{{ $title }}</div>
        @endif
        <div class="text-muted small">
            {{ $slot }}
        </div>
    </div>
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
