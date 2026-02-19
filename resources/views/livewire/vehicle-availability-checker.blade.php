<div>
    @if(!empty($validationErrors) || !empty($conflicts))
        <div class="alert alert-danger mt-2">
            <strong><i class="bi bi-exclamation-triangle"></i> Pojazd niedostępny:</strong>
            <ul class="mb-0 mt-2">
                @foreach($validationErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            
            @if(!empty($conflicts))
                <div class="mt-2">
                    <strong>Kolidujące przypisania/wyjazdy:</strong>
                    <div class="mt-1">
                        @foreach($conflicts as $conflict)
                            @php
                                $routeParams = $conflict['route_params'] ?? ['departure' => $conflict['id']];
                                if ($conflict['type'] === 'vehicle_assignment') {
                                    $routeParams = ['vehicle_assignment' => $conflict['id']];
                                }
                            @endphp
                            <a href="{{ route($conflict['route'], $routeParams) }}" 
                               target="_blank" 
                               class="badge bg-warning text-dark text-decoration-none me-1"
                               title="{{ $conflict['description'] }}">
                                <i class="bi bi-link-45deg"></i> 
                                @if($conflict['type'] === 'logistics_event')
                                    Wyjazd #{{ $conflict['id'] }}
                                @else
                                    Przypisanie #{{ $conflict['id'] }}
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
