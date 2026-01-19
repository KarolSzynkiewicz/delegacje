@props([
    'viewRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'resource' => null, // Nazwa zasobu (np. 'equipment') - automatycznie generuje route names (opcjonalne, wyciągane z route names)
    'deleteMessage' => 'Czy na pewno chcesz usunąć ten element?',
    'size' => 'sm', // sm, null, lg
])

@php
    use Illuminate\Support\Facades\Route;
    
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
    
    // Automatycznie wyciągnij resource z route names, jeśli nie podano
    if (!$resource) {
        // Spróbuj wyciągnąć z viewRoute używając Route facade
        if ($viewRoute) {
            try {
                // Parsuj URL - usuń domenę jeśli jest
                $url = parse_url($viewRoute, PHP_URL_PATH);
                if (!$url) {
                    $url = $viewRoute;
                }
                
                // Utwórz request i znajdź route
                $request = request()->create($url, 'GET');
                $route = Route::getRoutes()->match($request);
                $routeName = $route->getName();
                
                if ($routeName) {
                    // Wyciągnij resource z route name (np. equipment.show -> equipment)
                    $parts = explode('.', $routeName);
                    if (count($parts) >= 2) {
                        // Usuń ostatnią część (show/edit/destroy)
                        array_pop($parts);
                        $resource = implode('.', $parts);
                        
                        // Dla nested routes, weź ostatnią część
                        // np. projects.assignments -> assignments
                        if (str_contains($resource, '.')) {
                            $nestedParts = explode('.', $resource);
                            $resource = end($nestedParts);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Jeśli nie udało się wyciągnąć, zostaw null
            }
        }
    }
    
    // Automatycznie generuj route names z resource
    $viewRouteName = $resource ? "{$resource}.show" : null;
    $editRouteName = $resource ? "{$resource}.edit" : null;
    $deleteRouteName = $resource ? "{$resource}.destroy" : null;
@endphp

<div class="btn-group" role="group">
    @if($viewRoute)
        <x-ui.button 
            variant="ghost" 
            href="{{ $viewRoute }}" 
            routeName="{{ $viewRouteName }}"
            action="view"
            title="Zobacz"
            class="{{ $sizeClass }}"
        />
    @endif
    
    @if($editRoute)
        <x-ui.button 
            variant="ghost" 
            href="{{ $editRoute }}" 
            routeName="{{ $editRouteName }}"
            action="edit"
            title="Edytuj"
            class="{{ $sizeClass }}"
        />
    @endif
    
    @if($deleteRoute)
        <form action="{{ $deleteRoute }}" 
              method="POST" 
              class="d-inline"
              onsubmit="return confirm('{{ $deleteMessage }}')">
            @csrf
            @method('DELETE')
            <x-ui.button 
                variant="danger" 
                type="submit" 
                routeName="{{ $deleteRouteName }}"
                action="delete"
                title="Usuń"
                class="{{ $sizeClass }}"
            />
        </form>
    @endif
</div>
