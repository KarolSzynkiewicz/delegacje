@props([
    'viewRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'deleteMessage' => 'Czy na pewno chcesz usunąć ten element?',
    'size' => 'sm', // sm, null, lg
])

<div class="btn-group" role="group">
    @if($viewRoute)
        <x-ui.button variant="ghost" href="{{ $viewRoute }}" title="Zobacz">
            <i class="bi bi-eye"></i>
        </x-ui.button>
    @endif
    
    @if($editRoute)
        <x-ui.button variant="ghost" href="{{ $editRoute }}" title="Edytuj">
            <i class="bi bi-pencil"></i>
        </x-ui.button>
    @endif
    
    @if($deleteRoute)
        <form action="{{ $deleteRoute }}" 
              method="POST" 
              class="d-inline"
              onsubmit="return confirm('{{ $deleteMessage }}')">
            @csrf
            @method('DELETE')
            <x-ui.button variant="danger" type="submit" title="Usuń">
                <i class="bi bi-trash"></i>
            </x-ui.button>
        </form>
    @endif
</div>
