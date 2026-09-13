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

@if(! $sprint)
    @php
        $seedList = function (string $key) {
            $items = old($key, ['']);
            if (! is_array($items) || $items === []) {
                $items = [''];
            }

            return array_values($items);
        };
    @endphp
    <div class="row">
        <div class="col-md-6 mb-3"
             x-data="{ items: {{ json_encode($seedList('readiness_items'), JSON_UNESCAPED_UNICODE) }} }">
            <label class="form-label">Co potrzeba, by zacząć pracę?</label>
            <p class="small text-muted mb-2">Co musi być, byśmy mogli w ogóle zacząć nad tym pracować.</p>
            <template x-for="(item, index) in items" :key="index">
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control" name="readiness_items[]" x-model="items[index]" placeholder="np. Design zatwierdzony">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="if (items.length > 1) items.splice(index, 1)" x-show="items.length > 1">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </template>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="items.push('')">Dodaj</button>
        </div>
        <div class="col-md-6 mb-3"
             x-data="{ items: {{ json_encode($seedList('done_items'), JSON_UNESCAPED_UNICODE) }} }">
            <label class="form-label">Kiedy uznamy, że zrobione?</label>
            <p class="small text-muted mb-2">Warunki, bez których zadanie nie schodzi z tablicy.</p>
            <template x-for="(item, index) in items" :key="index">
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control" name="done_items[]" x-model="items[index]" placeholder="np. Na produkcji">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="if (items.length > 1) items.splice(index, 1)" x-show="items.length > 1">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </template>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="items.push('')">Dodaj</button>
        </div>
    </div>
@endif

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
