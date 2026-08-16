<div class="mb-3">
    <label class="form-label" for="consume-destination-type">Przeznaczenie</label>
    <select
        id="consume-destination-type"
        class="form-select @error('destinationType') is-invalid @enderror"
        wire:model.live="destinationType"
    >
        <option value="">Wybierz…</option>
        @foreach($destinationTypes as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
        @endforeach
    </select>
    @error('destinationType')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@if($destinationType)
    <div class="mb-3">
        <label class="form-label">
            {{ \App\Enums\ConsumptionDestination::tryFrom($destinationType)?->label() ?? 'Wskazanie' }}
        </label>
        @if($destinationId)
            <div class="d-flex align-items-center justify-content-between gap-2 border rounded px-3 py-2">
                <span class="fw-semibold">{{ $selectedDestinationLabel ?? $destinationSearch }}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearDestination">Zmień</button>
            </div>
        @else
            <input
                type="search"
                class="form-control @error('destinationId') is-invalid @enderror"
                placeholder="{{ \App\Enums\ConsumptionDestination::tryFrom($destinationType)?->placeholder() }}"
                autocomplete="off"
                wire:model.live.debounce.300ms="destinationSearch"
            >
            @if(filled($destinationSearch))
                <div class="border rounded mt-1" style="max-height:12rem;overflow:auto;">
                    @forelse($destinationMatches as $match)
                        <button type="button" class="dropdown-item" wire:click="selectDestination({{ $match['id'] }})">
                            {{ $match['label'] }}
                        </button>
                    @empty
                        <div class="px-3 py-2 small text-muted">Brak wyników.</div>
                    @endforelse
                </div>
            @endif
        @endif
        @error('destinationId')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
@endif
