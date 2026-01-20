@props([
    'label' => null,
    'icon' => null,
    'permission' => null,
    'routePatterns' => [], // Tablica patternów do sprawdzania active (np. ['projects.*', 'vehicles.*'])
    'dropdownId' => null,
])

@php
    // Sprawdź uprawnienia - jeśli ustawione i użytkownik nie ma dostępu, nie renderuj dropdownu
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
    
    // Konwertuj routePatterns na tablicę jeśli jest stringiem (dla Blade)
    $patterns = is_array($routePatterns) ? $routePatterns : [];
    if (is_string($routePatterns) && !empty($routePatterns)) {
        // Jeśli to string z tablicą PHP, spróbuj go sparsować
        // W przeciwnym razie traktuj jako pojedynczy pattern
        $patterns = [$routePatterns];
    }
    
    // Sprawdź czy dropdown jest aktywny (na podstawie route patterns)
    $isActive = false;
    foreach ($patterns as $pattern) {
        if (request()->routeIs($pattern)) {
            $isActive = true;
            break;
        }
    }
    
    $toggleClasses = 'nav-link dropdown-toggle';
    if ($isActive) {
        $toggleClasses .= ' active';
    }
    
    // Generuj unikalne ID jeśli nie podano
    $id = $dropdownId ?? 'dropdown-' . str_replace(' ', '-', strtolower($label ?? 'menu'));
@endphp

<li class="nav-item dropdown">
    <a class="{{ $toggleClasses }}" href="#" id="{{ $id }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        @if($icon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $label }}
    </a>
    <ul class="dropdown-menu" aria-labelledby="{{ $id }}">
        {{ $slot }}
    </ul>
</li>
