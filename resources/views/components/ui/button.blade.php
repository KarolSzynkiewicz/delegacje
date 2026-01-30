@php
    use App\Enums\ButtonAction;
@endphp

@props([
    'variant' => 'primary', // primary, ghost, danger, warning, success
    'type' => 'button',
    'href' => null,
    'action' => null, // create, edit, save, delete, back, view, etc. - automatyczna ikona (string lub ButtonAction enum)
    'permission' => null, // Nazwa uprawnienia - jeśli ustawione, przycisk jest renderowany tylko gdy użytkownik ma uprawnienie
    'routeName' => null, // Nazwa route - jeśli podana i permission nie jest ustawione, automatycznie generuje uprawnienie
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
    
    // Automatyczne generowanie uprawnienia z routeName używając RoutePermissionService
    $permissionName = $permission;
    if (!$permissionName && $routeName) {
        $routePermissionService = app(\App\Services\RoutePermissionService::class);
        $permissionName = $routePermissionService->getPermissionForRoute($routeName);
    }
    
    // Sprawdź uprawnienia - jeśli ustawione i użytkownik nie ma dostępu, nie renderuj przycisku
    $hasPermission = true;
    if ($permissionName && auth()->check()) {
        $hasPermission = auth()->user()->hasPermission($permissionName);
    } elseif ($permissionName && !auth()->check()) {
        $hasPermission = false;
    }
@endphp

@if(!$hasPermission)
    {{-- Użytkownik nie ma uprawnienia - nie renderuj przycisku --}}
@elseif($href)
    <a href="{!! $href !!}" {{ $attributes->merge(['class' => $classes]) }}>
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
