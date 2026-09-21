<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->status->value === 'pending' ? 'active' : '' }}"
            wire:click="quickStatusChange({{ $task->id }}, 'pending')"
            @click="open=false">
        ⏳ Oczekujące
    </button>
</li>
@unless($binaryStatus)
<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->status->value === 'in_progress' ? 'active' : '' }}"
            wire:click="quickStatusChange({{ $task->id }}, 'in_progress')"
            @click="open=false">
        ▶ W trakcie
    </button>
</li>
@endunless
<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->status->value === 'completed' ? 'active' : '' }}"
            wire:click="quickStatusChange({{ $task->id }}, 'completed')"
            @click="open=false">
        ✓ Ukończone
    </button>
</li>
@unless($binaryStatus)
<li>
    <button type="button"
            class="dropdown-item py-2 {{ $task->status->value === 'cancelled' ? 'active' : '' }}"
            wire:click="quickStatusChange({{ $task->id }}, 'cancelled')"
            @click="open=false">
        ✗ Anulowane
    </button>
</li>
@endunless
