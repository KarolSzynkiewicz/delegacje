<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->assignedTo ? '' : 'active' }}"
            wire:click="quickAssigneeChange({{ $task->id }}, '')"
            @click="open=false">
        Brak
    </button>
</li>
@foreach($allUsers as $userOption)
    <li>
        <button type="button"
                class="dropdown-item py-2 {{ (int) ($task->assignedTo?->id) === (int) $userOption->id ? 'active' : '' }}"
                wire:click="quickAssigneeChange({{ $task->id }}, '{{ $userOption->id }}')"
                @click="open=false">
            {{ $userOption->name }}
        </button>
    </li>
@endforeach
