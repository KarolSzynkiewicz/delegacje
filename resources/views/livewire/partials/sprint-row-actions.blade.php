@props(['sprint', 'canMutate' => false])

<div class="dropdown" style="position: relative; z-index: 2;">
    <button
        type="button"
        class="btn btn-sm btn-outline-secondary"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        title="Akcje"
    >
        <i class="bi bi-three-dots"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="{{ route('sprints.show', $sprint) }}">Otwórz</a>
        </li>
        @if($canMutate && ! $sprint->isClosed())
            <li>
                <button type="button" class="dropdown-item" wire:click="startClose({{ $sprint->id }})">
                    Zakończ sprint
                </button>
            </li>
            @if($sprint->isParked())
                <li>
                    <button type="button" class="dropdown-item" wire:click="unparkSprint({{ $sprint->id }})">
                        Przywróć z później
                    </button>
                </li>
            @else
                <li>
                    <button type="button" class="dropdown-item" wire:click="parkSprint({{ $sprint->id }})">
                        Odstaw na później
                    </button>
                </li>
            @endif
            <li>
                <a class="dropdown-item" href="{{ route('sprints.edit', $sprint) }}">Edytuj</a>
            </li>
        @endif
        @if($canMutate)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="{{ route('sprints.destroy', $sprint) }}" method="POST" onsubmit="return confirm('Usunąć sprint „{{ $sprint->name }}”? Zadania wrócą do backlogu.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger">Usuń</button>
                </form>
            </li>
        @endif
    </ul>
</div>
