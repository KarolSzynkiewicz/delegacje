{{-- Edycja przystanków po locie (lotnisko docelowe → …) — tylko tryb autem (własny pojazd). --}}
<div class="rounded-2 p-3 mb-3" style="background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.28);">
    <div class="small fw-semibold mb-2"><i class="bi bi-signpost-split me-1"></i> Trasa (wstępnie)</div>

    <label class="form-label small text-muted mb-1">Opcjonalny punkt przed lotniskiem (start trasy na mapie)</label>
    <select wire:model.live="transferPickupLocationId" class="form-select form-select-sm mb-3">
        <option value="">— brak (start: lotnisko docelowe) —</option>
        @foreach($availableLocations as $loc)
            <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city) – {{ $loc->city }}@endif</option>
        @endforeach
    </select>

    @if(empty($waypointStops))
        <div class="alert alert-info mb-3 py-2 small">
            <i class="bi bi-info-circle"></i> Brak przystanków — wróć do kroku 2 i przypisz mieszkania albo dodaj przystanek poniżej.
        </div>
    @else
        <div class="small text-muted mb-2 fw-semibold">Kolejność (lotnisko docelowe → …)</div>
        <div class="vstack gap-2">
            @foreach($waypointStops as $wp)
                @if(($wp['type'] ?? '') === 'loc')
                    @php
                        $locIdStr = (string) ($wp['id'] ?? '');
                        $locRow = is_array($wp['location'] ?? null) ? $wp['location'] : [];
                    @endphp
                    <div class="d-flex align-items-start gap-2 p-2 rounded border" style="border-color: rgba(251,191,36,0.35) !important; background: rgba(251,191,36,0.04);" wire:key="pt3-loc-{{ $locIdStr }}">
                        <span class="badge rounded-pill align-self-start" style="background: rgba(251,191,36,0.2); color: #fcd34d;">{{ $loop->iteration }}</span>
                        <div class="flex-grow-1 min-w-0 small">
                            <div class="d-flex justify-content-between gap-1">
                                <span class="fw-semibold">Przystanek · {{ $locRow['name'] ?? '—' }}</span>
                                <div class="d-flex flex-column gap-0 flex-shrink-0">
                                    <button type="button" class="rtp-icon-btn" wire:click="moveUp({{ $loop->index }})" @disabled($loop->first)><i class="bi bi-chevron-up"></i></button>
                                    <button type="button" class="rtp-icon-btn" wire:click="moveDown({{ $loop->index }})" @disabled($loop->last)><i class="bi bi-chevron-down"></i></button>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0" wire:click="removeWaypoint({{ $loop->index }})" wire:confirm="Usunąć ten przystanek z trasy?"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $locRow['address'] ?? '' }}@if(!empty($locRow['city'])), {{ $locRow['city'] }}@endif</div>
                            <textarea class="form-control form-control-sm mt-1" rows="2" placeholder="Po co tam jedziemy? …"
                                wire:model.live.debounce.300ms="locationStopNotes.{{ $locIdStr }}"></textarea>
                        </div>
                    </div>
                @else
                    @php
                        $accId = (int) ($wp['id'] ?? 0);
                        $stop = collect($tripPlan)->first(function ($s) use ($accId) {
                            return (int) ($s['accommodation']['id'] ?? 0) === $accId;
                        });
                        $destNames = $stop
                            ? collect($stop['employees'])->pluck('full_name')->filter()->values()
                            : collect($wp['employees'] ?? [])->pluck('full_name')->filter()->values();
                        $accName = $stop['accommodation']['name'] ?? ($wp['accommodation']['name'] ?? 'Dom');
                        $accAddr = $stop['accommodation']['address'] ?? ($wp['accommodation']['address'] ?? '');
                    @endphp
                    <div class="d-flex align-items-start gap-2 p-2 rounded border" style="border-color: rgba(34,197,94,0.35) !important; background: rgba(34,197,94,0.05);" wire:key="pt3-acc-{{ $accId }}">
                        <span class="badge rounded-pill align-self-start bg-success bg-opacity-25 text-success-emphasis">{{ $loop->iteration }}</span>
                        <div class="flex-grow-1 min-w-0 small">
                            <div class="d-flex justify-content-between gap-1">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $accName }}</div>
                                    @if($destNames->isNotEmpty())
                                        <div style="font-size: 0.72rem; color: #86efac;">{{ $destNames->join(', ') }}</div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column gap-0 flex-shrink-0">
                                    <button type="button" class="rtp-icon-btn" wire:click="moveUp({{ $loop->index }})" @disabled($loop->first)><i class="bi bi-chevron-up"></i></button>
                                    <button type="button" class="rtp-icon-btn" wire:click="moveDown({{ $loop->index }})" @disabled($loop->last)><i class="bi bi-chevron-down"></i></button>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary" wire:click="removeWaypoint({{ $loop->index }})"
                                            wire:confirm="Usunąć ten dom z kolejności trasy?"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                            @if($accAddr)
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $accAddr }}</div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <form wire:submit.prevent="addExtraLocationToPostTransfer" class="d-flex gap-2 align-items-center mt-2 mb-0">
        <select wire:model.live="extraStopLocationId" class="form-select form-select-sm flex-grow-1">
            <option value="">— Dodaj przystanek (lokalizacja) —</option>
            @foreach($availableLocations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city) – {{ $loc->city }}@endif</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-outline-success flex-shrink-0" wire:loading.attr="disabled">Dodaj</button>
    </form>
</div>
