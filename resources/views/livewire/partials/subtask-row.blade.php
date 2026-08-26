@php
    $isEditing = $editingSubtaskId === $subtask->id;
    $isAssigning = $assigningSubtaskId === $subtask->id;
@endphp

<li
    class="st-item @if($done) st-item--done @endif @if($isEditing || $isAssigning) st-item--editing @endif"
    wire:key="subtask-{{ $subtask->id }}"
>
    <span class="badge badge-secondary subtask-num st-item__num" title="Numer podzadania w tym zadaniu">
        #{{ $subtaskNumbers[$subtask->id] }}
    </span>

    @if($isEditing)
        <div
            class="st-item__editor"
            x-data="subtaskMention(@js($mentionUsersForAutocomplete), 'editingSubtaskName')"
        >
            <input
                type="text"
                class="form-control form-control-sm @error('editingSubtaskName') is-invalid @enderror"
                wire:model.defer="editingSubtaskName"
                x-ref="inp"
                placeholder="Nazwa… albo @osoba żeby przypisać"
                @input="onInput($event)"
                @keydown.escape="close()"
            />
            @include('livewire.partials.mention-dropdown')
            @error('editingSubtaskName')
                <span class="text-danger small d-block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="st-item__actions" wire:click.stop>
            <button type="button" class="btn btn-sm btn-primary" wire:click="saveSubtaskEdits({{ $subtask->id }})">
                Zapisz
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelEditSubtask">
                Anuluj
            </button>
        </div>
    @elseif($isAssigning)
        <div class="st-item__editor" wire:click.stop>
            <select class="form-select form-select-sm" wire:model="assignSubtaskUserId">
                <option value="">Bez przypisania</option>
                @foreach($assignUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            <span class="small text-muted d-block mt-1">Albo wpisz <code>@osoba</code> przy edycji nazwy.</span>
        </div>

        <div class="st-item__actions" wire:click.stop>
            <button type="button" class="btn btn-sm btn-primary" wire:click="saveSubtaskAssignment({{ $subtask->id }})">
                Zapisz
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelAssignSubtask">
                Anuluj
            </button>
        </div>
    @else
        <div class="st-item__body">
            <span
                class="st-item__name"
                role="button"
                tabindex="0"
                wire:click="toggleSubtask({{ $subtask->id }})"
                @keydown.enter.prevent="$wire.toggleSubtask({{ $subtask->id }})"
                @keydown.space.prevent="$wire.toggleSubtask({{ $subtask->id }})"
                title="{{ $done ? 'Oznacz jako do zrobienia' : 'Oznacz jako wykonane' }}"
            >
                {!! \App\Services\UserMentionService::highlightMentions(e($subtask->name), $mentionUsersForAutocomplete) !!}
            </span>

            @if($subtask->assignedTo)
                <div class="st-item__assignee" wire:click.stop title="Przypisany">
                    <x-ui.person
                        :user="$subtask->assignedTo"
                        avatar-size="22px"
                        :show-email="false"
                        name-class="small fw-medium"
                    />
                </div>
            @endif
        </div>

        <div class="st-item__actions" wire:click.stop>
            <button
                type="button"
                class="st-action"
                title="Przypisz osobę (@)"
                aria-label="Przypisz osobę do podzadania"
                wire:click.stop="startAssignSubtask({{ $subtask->id }})"
            >
                <i class="bi bi-person-plus"></i>
            </button>
            <button
                type="button"
                class="st-action"
                title="Edytuj podzadanie"
                aria-label="Edytuj podzadanie"
                wire:click.stop="startEditSubtask({{ $subtask->id }})"
            >
                <i class="bi bi-pencil"></i>
            </button>
            <button
                type="button"
                class="st-action st-action--danger"
                title="Usuń podzadanie"
                aria-label="Usuń podzadanie"
                x-on:click.stop="confirm('Na pewno usunąć to podzadanie?') && $wire.deleteSubtask({{ $subtask->id }})"
            >
                <i class="bi bi-trash"></i>
            </button>
        </div>
    @endif
</li>
