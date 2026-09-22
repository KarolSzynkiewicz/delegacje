@if($task->sprint)
<li>
    <a href="{{ route('sprints.show', $task->sprint) }}" class="dropdown-item py-2" @click="open=false">
        Otwórz sprint
    </a>
</li>
<li><hr class="dropdown-divider my-1"></li>
@endif
<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->sprint_id ? '' : 'active' }}"
            wire:click="quickSprintChange({{ $task->id }}, '')"
            @click="open=false">
        Poza sprintem
    </button>
</li>
@foreach($allSprints as $sprintOption)
    <li>
        <button type="button"
                class="dropdown-item py-2 {{ (int) $task->sprint_id === (int) $sprintOption->id ? 'active' : '' }}"
                wire:click="quickSprintChange({{ $task->id }}, '{{ $sprintOption->id }}')"
                @click="open=false">
            {{ $sprintOption->label() }}
        </button>
    </li>
@endforeach
