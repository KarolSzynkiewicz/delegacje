@php
    $showSubmit = $showSubmit ?? false;
    $selectedPeople = collect($allUsers)->whereIn('id', $newMeetingParticipantIds);
    $peopleLabel = $selectedPeople->isEmpty()
        ? 'Uczestnicy'
        : 'Uczestnicy ('.$selectedPeople->count().')';
@endphp
<div class="tg-meeting-fields">
    <input type="text"
           wire:model="newTaskName"
           class="form-control form-control-sm tg-meeting-fields__title @error('newTaskName') is-invalid @enderror"
           placeholder="Temat spotkania *"
           wire:keydown.enter="submitAdd"
           wire:keydown.escape="cancelAdd"
           x-data x-init="$el.focus()">

    <input type="date" wire:model="newMeetingDate" class="form-control form-control-sm tg-meeting-fields__date @error('newMeetingDate') is-invalid @enderror" title="Data">
    <input type="time" wire:model="newMeetingStart" class="form-control form-control-sm tg-meeting-fields__time @error('newMeetingStart') is-invalid @enderror" title="Od">
    <input type="time" wire:model="newMeetingEnd" class="form-control form-control-sm tg-meeting-fields__time @error('newMeetingEnd') is-invalid @enderror" title="Do">

    <div class="tg-meeting-people" x-data="{ open: false, top: 0, left: 0, pw: 260 }">
        <button type="button"
                class="form-select form-select-sm tg-meeting-people__toggle @error('newMeetingParticipantIds') is-invalid @enderror"
                @click.stop="if (open) { open = false; return } const r = $el.getBoundingClientRect(); pw = Math.min(280, window.innerWidth - 24); top = r.bottom + 4; left = Math.max(4, Math.min(r.left, window.innerWidth - pw - 4)); open = true">
            <span>{{ $peopleLabel }}</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <template x-teleport="body">
            <div x-show="open" x-cloak
                 @click.outside="open = false"
                 @click.stop
                 :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;width:${pw}px;max-width:calc(100vw - 24px)`"
                 class="tg-meeting-people__menu">
                @foreach($allUsers as $u)
                    <x-ui.input
                        type="checkbox"
                        :id="'tg-meet-u-'.$u->id"
                        :value="$u->id"
                        :label="$u->name"
                        wire:model="newMeetingParticipantIds"
                        class="form-check-compact mb-0"
                    />
                @endforeach
            </div>
        </template>
    </div>

    <input type="text"
           wire:model="newMeetingLocation"
           class="form-control form-control-sm tg-meeting-fields__where meeting-where-input @error('newMeetingLocation') is-invalid @enderror"
           placeholder="Gdzie">

    @if($showSubmit)
        <button type="button" wire:click="submitAdd" class="btn btn-sm tg-add-submit flex-shrink-0">
            <i class="bi bi-calendar-plus me-1"></i>Umów
        </button>
    @endif
</div>
@error('newTaskName') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
@error('newMeetingDate') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
@error('newMeetingStart') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
@error('newMeetingEnd') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
@error('newMeetingParticipantIds') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
@error('newMeetingParticipantIds.*') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
@error('newMeetingLocation') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
