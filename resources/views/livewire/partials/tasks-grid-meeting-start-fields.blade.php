<div class="tg-meeting-fields">
    <input type="text"
           wire:model="newTaskName"
           class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
           placeholder="Temat spotkania *"
           wire:keydown.enter="submitAdd"
           wire:keydown.escape="cancelAdd"
           x-data x-init="$el.focus()">
    @error('newTaskName')
        <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
    @enderror

    <div class="tg-meeting-fields__times">
        <input type="date" wire:model="newMeetingDate" class="form-control form-control-sm @error('newMeetingDate') is-invalid @enderror" title="Data">
        <input type="time" wire:model="newMeetingStart" class="form-control form-control-sm @error('newMeetingStart') is-invalid @enderror" title="Od">
        <input type="time" wire:model="newMeetingEnd" class="form-control form-control-sm @error('newMeetingEnd') is-invalid @enderror" title="Do">
    </div>
    @error('newMeetingDate') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
    @error('newMeetingStart') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
    @error('newMeetingEnd') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror

    <div class="rp-meeting-people">
        @foreach($allUsers as $u)
            <label class="rp-meeting-person">
                <input type="checkbox" wire:model="newMeetingParticipantIds" value="{{ $u->id }}">
                <span>{{ $u->name }}</span>
            </label>
        @endforeach
    </div>
    @error('newMeetingParticipantIds') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
    @error('newMeetingParticipantIds.*') <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div> @enderror
</div>
