{{--
  Uniwersalny panel: kolejność przystanków (loc:…), notatka na przystanek, dodawanie z listy lokalizacji,
  przesuwanie w górę/dół. Stan i akcje Livewire pozostają w rodzicu — przekazuj nazwy metod i kolekcje.

  Tablica $stops (np. z GroundTransferSlot::routeTiles): index, id, name, city?, address?, can_move_up, can_move_down
--}}
@props([
    'stops' => [],
    'title' => 'Plan trasy',
    'titleIcon' => 'bi-map',
    'titleIconClass' => 'text-info',
    'wireKeyPrefix' => 'rwp',
    'notesWirePrefix' => 'locationStopNotes',
    'stopBadge' => '',
    'showStopBadge' => false,
    'notesLabel' => 'Notatka do przystanku',
    'notesPlaceholder' => 'Krótka notatka (np. odbiór dokumentów, spotkanie)…',
    'emptyHint' => 'Brak przystanków — dodaj pierwszy punkt poniżej (kolejność = kolejność przejazdu).',
    'addSelectPlaceholder' => '— dodaj przystanek z ręki —',
    'addButtonLabel' => 'Dodaj',
    'showAddForm' => true,
    'addSubmitMethod' => 'addWaypoint',
    'pendingLocationModel' => 'pendingWaypointLocationId',
    'moveUpMethod' => 'moveWaypointUp',
    'moveDownMethod' => 'moveWaypointDown',
    'removeMethod' => 'removeWaypoint',
    'removeConfirm' => null,
    'availableLocations' => null,
    'addDisabled' => false,
])

@php
    $locList = $availableLocations ?? collect();
    $showBadge = $showStopBadge && $stopBadge !== '';
@endphp

@once('logistics-rwp-plan-styles')
    <style>
        .logistics-route-waypoints-plan .logistics-rwp-add-strip {
            background: rgba(51, 65, 85, 0.35);
            border: 1px dashed rgba(129, 140, 248, 0.35);
            border-radius: 0.65rem;
        }
        .logistics-route-waypoints-plan .logistics-rwp-add-strip .form-select {
            border-radius: 0.5rem;
            background: rgba(15, 23, 42, 0.45);
            border-color: rgba(148, 163, 184, 0.35);
        }
        .logistics-route-waypoints-plan .logistics-rwp-empty {
            background: rgba(30, 41, 59, 0.4);
            border: 1px dashed rgba(148, 163, 184, 0.28);
            border-radius: 0.65rem;
            color: #94a3b8;
        }
        .logistics-route-waypoints-plan .logistics-rwp-add-btn {
            border-radius: 999px;
            padding-left: 1rem;
            padding-right: 1rem;
            font-weight: 600;
            font-size: 0.8rem;
        }
    </style>
@endonce

<div {{ $attributes->class(['logistics-route-waypoints-plan']) }}>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0 fw-semibold" style="font-size: 0.92rem;">
            <i class="bi {{ $titleIcon }} me-2 {{ $titleIconClass }}"></i>{{ $title }}
        </h6>
    </div>

    @if($showAddForm)
        <form wire:submit.prevent="{{ $addSubmitMethod }}"
              class="logistics-rwp-add-strip d-flex flex-wrap gap-2 align-items-center mb-3 p-2">
            <i class="bi bi-plus-circle text-info ps-1" style="font-size: 1rem; flex-shrink: 0;"></i>
            <select wire:model.live="{{ $pendingLocationModel }}" class="form-select form-select-sm flex-grow-1" style="min-width: 12rem;">
                <option value="">{{ $addSelectPlaceholder }}</option>
                @foreach($locList as $loc)
                    @php
                        $type = 'LOKALIZACJA';
                        if (! empty($loc->is_base)) {
                            $type = 'BAZA';
                        } elseif (method_exists($loc, 'hasPurpose') && $loc->hasPurpose(\App\Enums\LocationPurposeType::AIRPORT)) {
                            $type = 'LOTNISKO';
                        } elseif (method_exists($loc, 'hasPurpose') && $loc->hasPurpose(\App\Enums\LocationPurposeType::STATION)) {
                            $type = 'DWORZEC';
                        } elseif ((int) ($loc->accommodations_count ?? 0) > 0) {
                            $type = 'DOM';
                        }
                        $label = $type.': '.$loc->name.(! empty($loc->city) ? ' – '.$loc->city : '');
                    @endphp
                    <option value="{{ $loc->id }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary logistics-rwp-add-btn flex-shrink-0"
                    wire:loading.attr="disabled"
                    @if($addDisabled) disabled @endif>
                <span wire:loading.remove wire:target="{{ $addSubmitMethod }}"><i class="bi bi-plus-lg me-1"></i>{{ $addButtonLabel }}</span>
                <span wire:loading wire:target="{{ $addSubmitMethod }}"><span class="spinner-border spinner-border-sm"></span></span>
            </button>
        </form>
    @endif

    @if($stops === [] || count($stops) === 0)
        <div class="logistics-rwp-empty small mb-0 p-3 text-center">
            <i class="bi bi-signpost-2 me-1"></i> {{ $emptyHint }}
        </div>
    @else
        <div class="trip-plan-list">
            @foreach($stops as $stop)
                <x-logistics.route-waypoint-loc-card
                    :wire-key-prefix="$wireKeyPrefix"
                    :stable-row-key="isset($stop['key']) ? $wireKeyPrefix.'-'.str_replace(':', '-', (string) $stop['key']) : null"
                    :list-position="$loop->iteration"
                    :route-index="(int) ($stop['index'] ?? 0)"
                    :loc-id="$stop['id'] ?? ''"
                    :type-label="$stop['type_label'] ?? null"
                    :name="$stop['name'] ?? '—'"
                    :city="$stop['city'] ?? null"
                    :address="$stop['address'] ?? null"
                    :can-move-up="! empty($stop['can_move_up'])"
                    :can-move-down="! empty($stop['can_move_down'])"
                    :move-up-method="$moveUpMethod"
                    :move-down-method="$moveDownMethod"
                    :remove-method="$removeMethod"
                    :remove-confirm="$removeConfirm"
                    :notes-wire-prefix="$notesWirePrefix"
                    :notes-label="$notesLabel"
                    :notes-placeholder="$notesPlaceholder"
                    :show-stop-badge="$showBadge"
                    :stop-badge="$stopBadge"
                />
            @endforeach
        </div>
    @endif

    @isset($distance)
        <div class="mt-3">
            {{ $distance }}
        </div>
    @endisset
</div>
