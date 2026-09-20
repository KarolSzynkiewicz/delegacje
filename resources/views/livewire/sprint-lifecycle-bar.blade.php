<div class="d-inline-flex flex-wrap align-items-center gap-2">
    @if($canMutate && ! $sprint->isClosed())
        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="startClose({{ $sprint->id }})">
            <i class="bi bi-check2-square me-1"></i>Zakończ sprint
        </button>
        @if($sprint->isParked())
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="unparkSprint({{ $sprint->id }})">
                <i class="bi bi-play-circle me-1"></i>Przywróć z później
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="parkSprint({{ $sprint->id }})">
                <i class="bi bi-clock me-1"></i>Odstaw na później
            </button>
        @endif
    @endif

    @include('livewire.partials.sprint-close-modal')
</div>
