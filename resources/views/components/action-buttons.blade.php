@props([
    'viewRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'deleteMessage' => 'Czy na pewno chcesz usunąć ten element?',
    'size' => 'sm', // sm, null, lg
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<div class="btn-group btn-group-{{ $size }}" role="group">
    @if($viewRoute)
        <a href="{{ $viewRoute }}" 
           class="btn btn-outline-primary {{ $sizeClass }}" 
           title="Zobacz">
            <i class="bi bi-eye"></i>
        </a>
    @endif
    
    @if($editRoute)
        <a href="{{ $editRoute }}" 
           class="btn btn-outline-secondary {{ $sizeClass }}" 
           title="Edytuj">
            <i class="bi bi-pencil"></i>
        </a>
    @endif
    
    @if($deleteRoute)
        <form action="{{ $deleteRoute }}" 
              method="POST" 
              class="d-inline"
              onsubmit="return confirm('{{ $deleteMessage }}')">
            @csrf
            @method('DELETE')
            <button type="submit" 
                    class="btn btn-outline-danger {{ $sizeClass }}"
                    title="Usuń">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @endif
</div>
