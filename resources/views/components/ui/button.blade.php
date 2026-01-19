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
    
    // Automatyczne generowanie uprawnienia z routeName i action
    // Używa tej samej logiki co CheckResourcePermission middleware
    $permissionName = $permission;
    if (!$permissionName && $routeName) {
        $routeParts = explode('.', $routeName);
        
        // Dla resource routes: usuń ostatnią część (action) aby uzyskać resource
        // equipment.create -> equipment (resource) + create (action)
        // projects.assignments.create -> assignments (resource) + create (action)
        array_pop($routeParts);
        $resource = implode('.', $routeParts);
        
        // Dla nested resources, weź ostatnią część
        // projects.assignments -> assignments
        // employees.vehicles -> vehicles
        if (str_contains($resource, '.')) {
            $nestedParts = explode('.', $resource);
            $lastPart = end($nestedParts);
            
            // Mapowanie nested resources (zgodne z middleware)
            $nestedMappings = [
                'vehicles' => 'vehicle-assignments',
                'accommodations' => 'accommodation-assignments',
            ];
            
            $resource = $nestedMappings[$lastPart] ?? $lastPart;
        }
        
        // Pobierz akcję route (ostatnia część route name)
        $routeAction = explode('.', $routeName);
        $routeAction = end($routeAction);
        
        // Mapowanie akcji route na akcje uprawnień (zgodne z CheckResourcePermission middleware)
        $actionMap = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
        
        if (isset($actionMap[$routeAction])) {
            $permissionAction = $actionMap[$routeAction];
            $permissionName = "{$resource}.{$permissionAction}";
        } else {
            // Fallback: użyj route name jako uprawnienia (dla view routes i action routes)
            // np. profitability.index -> profitability.view (ale to wymaga sprawdzenia permission_type)
            // Dla prostoty używamy route name jako fallback
            $permissionName = $routeName;
        }
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
