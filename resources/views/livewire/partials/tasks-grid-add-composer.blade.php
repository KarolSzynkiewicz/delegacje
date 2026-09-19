@unless($this->isPlanQueue() || $this->isEdiReviewing())
    @if($showAddRow && $addKind !== 'task')
        <div class="tg-add-composer tg-add-composer--wide">
            <div class="tg-add-composer__head">
                <span class="tg-add-composer__kind">
                    @if($addKind === 'procedure') Uruchom procedurę
                    @elseif($addKind === 'approval') Poproś o zatwierdzenie
                    @else Umów spotkanie
                    @endif
                </span>
                <button type="button" wire:click="cancelAdd" class="comments-icon-btn" title="Anuluj" aria-label="Anuluj">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="d-flex flex-column gap-2">
                @if($addKind === 'procedure')
                    @include('livewire.partials.tasks-grid-procedure-start-fields')
                @elseif($addKind === 'meeting')
                    @include('livewire.partials.tasks-grid-meeting-start-fields', ['showSubmit' => false])
                @else
                    <input type="text"
                           wire:model="newTaskName"
                           class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
                           placeholder="O co prosisz? *"
                           wire:keydown.enter="submitAdd"
                           wire:keydown.escape="cancelAdd"
                           x-data x-init="$el.focus()">
                    @error('newTaskName')
                        <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
                    @enderror
                @endif

                @if($addKind !== 'meeting')
                    <select wire:model="newTaskAssignedTo" class="form-select form-select-sm @error('newTaskAssignedTo') is-invalid @enderror">
                        <option value="">{{ $addKind === 'approval' ? 'Zatwierdzający *' : 'Nieprzypisane' }}</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('newTaskAssignedTo')
                        <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
                    @enderror
                @endif

                <button type="button" wire:click="submitAdd" class="comments-icon-btn comments-send-btn align-self-end" title="Zapisz (Enter)" aria-label="Zapisz">
                    <i class="bi bi-arrow-return-left"></i>
                </button>
            </div>
        </div>
    @else
        <div class="tg-add-composer">
            <button type="button"
                    wire:click="clearAddComposer"
                    class="comments-icon-btn"
                    title="Wyczyść"
                    aria-label="Wyczyść">
                <i class="bi bi-x-lg"></i>
            </button>
            <input id="tg-add-name"
                   type="text"
                   wire:model="newTaskName"
                   class="tg-add-composer__name @error('newTaskName') is-invalid @enderror"
                   placeholder="Nazwa zadania"
                   autocomplete="off"
                   wire:keydown.enter.prevent="submitAdd"
                   aria-label="Nazwa zadania">
            <select wire:model="newTaskAssignedTo" class="form-select form-select-sm tg-add-composer__select" aria-label="Przypisany">
                <option value="">Nieprzypisane</option>
                @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <input type="text"
                   wire:model="newTaskCategory"
                   class="form-control form-control-sm tg-add-composer__select"
                   placeholder="Kategoria"
                   maxlength="255"
                   aria-label="Kategoria"
                   wire:keydown.enter.prevent="submitAdd">
            <button type="button"
                    wire:click="submitAdd"
                    class="comments-icon-btn comments-send-btn"
                    title="Dodaj zadanie (Enter)"
                    aria-label="Dodaj zadanie">
                <i class="bi bi-arrow-return-left"></i>
            </button>
        </div>
        @error('newTaskName')
            <div class="invalid-feedback d-block mb-2" style="font-size:0.72rem">{{ $message }}</div>
        @enderror
        @if($this->usesWorkItems())
            <div class="tg-add-kinds">
                <button type="button" wire:click="startAdd('procedure')" class="comments-icon-btn" title="Uruchom procedurę" aria-label="Uruchom procedurę">
                    <i class="bi bi-play-circle"></i>
                </button>
                <button type="button" wire:click="startAdd('approval')" class="comments-icon-btn" title="Poproś o zatwierdzenie" aria-label="Poproś o zatwierdzenie">
                    <i class="bi bi-check2-circle"></i>
                </button>
                <button type="button" wire:click="startAdd('meeting')" class="comments-icon-btn" title="Umów spotkanie" aria-label="Umów spotkanie">
                    <i class="bi bi-calendar-plus"></i>
                </button>
            </div>
        @endif
    @endif
@endunless
