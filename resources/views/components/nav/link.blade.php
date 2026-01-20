@props([
    'route' => null,
    'icon' => null,
    'permission' => null,
    'routePattern' => null, // Dla sprawdzania active (np. 'projects.*')
])

@php
    // Sprawdź uprawnienia - jeśli ustawione i użytkownik nie ma dostępu, nie renderuj linku
    $hasPermission = true;
    if ($permission && auth()->check()) {
        $hasPermission = auth()->user()->hasPermission($permission);
    } elseif ($permission && !auth()->check()) {
        $hasPermission = false;
    }
    
    // Jeśli nie ma uprawnienia, nie renderuj
    if (!$hasPermission) {
        return;
    }
    
    // Sprawdź czy link jest aktywny
    $isActive = false;
    if ($routePattern) {
        $isActive = request()->routeIs($routePattern);
    } elseif ($route) {
        // Jeśli nie podano pattern, sprawdź konkretny route
        $isActive = request()->routeIs($route);
    }
    
    $classes = 'nav-link';
    if ($isActive) {
        $classes .= ' active';
    }
    
    $href = $route ? route($route) : '#';
@endphp

<li class="nav-item">
    <a class="{{ $classes }}" href="{{ $href }}">
        @if($icon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $slot }}
    </a>
</li>
