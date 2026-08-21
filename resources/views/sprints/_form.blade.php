@php
    $sprint = $sprint ?? null;
@endphp

<x-ui.errors />

<div class="mb-3">
    <x-ui.input
        type="text"
        name="name"
        label="Nazwa"
        value="{{ old('name', $sprint?->name) }}"
        required
        placeholder="np. Sprint 24"
    />
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input
            type="date"
            name="start_date"
            label="Od"
            value="{{ old('start_date', $sprint?->start_date?->format('Y-m-d')) }}"
            required
        />
    </div>
    <div class="col-md-6">
        <x-ui.input
            type="date"
            name="end_date"
            label="Do"
            value="{{ old('end_date', $sprint?->end_date?->format('Y-m-d')) }}"
            required
        />
    </div>
</div>

<div class="mb-3">
    <x-ui.input
        type="textarea"
        name="goal"
        label="Cel sprintu"
        value="{{ old('goal', $sprint?->goal) }}"
        rows="3"
        placeholder="Co ten sprint ma dostarczyć?"
    />
</div>

<div class="mb-3">
    <x-ui.input
        type="textarea"
        name="definition_of_done"
        label="Definition of Done"
        value="{{ old('definition_of_done', $sprint?->definition_of_done) }}"
        rows="4"
        placeholder="Kiedy zadanie jest uznane za zrobione?"
    />
</div>

<div class="mb-3">
    <label class="form-label">Załączniki</label>
    @if($sprint && $sprint->relationLoaded('attachments') && $sprint->attachments->isNotEmpty())
        <div class="mb-2">
            <x-attachment-list :attachments="$sprint->attachments" />
        </div>
    @endif
    <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
    <small class="text-muted d-block mt-1">Do 15 plików, każdy max. 15 MB.</small>
</div>
