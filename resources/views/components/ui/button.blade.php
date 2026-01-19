@php
    use App\Enums\ButtonAction;
@endphp

@props([
    'variant' => 'primary', // primary, ghost, danger, warning, success
    'type' => 'button',
    'href' => null,
    'action' => null, // create, edit, save, delete, back, view, etc. - automatyczna ikona (string lub ButtonAction enum)
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
    
    // Automatyczne ikony na podstawie action
    // Obsługujemy zarówno string jak i ButtonAction enum
    $buttonAction = null;
    if ($action instanceof ButtonAction) {
        $buttonAction = $action;
    } elseif (is_string($action)) {
        $buttonAction = ButtonAction::tryFrom($action);
    }
    
    $icon = $buttonAction?->icon();
    $hasIcon = $icon !== null;
    $hasSlotContent = $slot->isNotEmpty();
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($hasIcon && $hasSlotContent)
            <i class="{{ $icon }} me-1"></i>
        @elseif($hasIcon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($hasIcon && $hasSlotContent)
            <i class="{{ $icon }} me-1"></i>
        @elseif($hasIcon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $slot }}
    </button>
@endif
