@foreach($priorityMap as $value => $meta)
    <li>
        <button type="button"
                class="dropdown-item py-2 {{ (int) $task->priority === (int) $value ? 'active' : '' }}"
                wire:click="quickPriorityChange({{ $task->id }}, '{{ $value }}')"
                @click="open=false">
            {{ $meta['label'] }}
        </button>
    </li>
@endforeach
<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->priority ? '' : 'active' }}"
            wire:click="quickPriorityChange({{ $task->id }}, '')"
            @click="open=false">
        Brak
    </button>
</li>
