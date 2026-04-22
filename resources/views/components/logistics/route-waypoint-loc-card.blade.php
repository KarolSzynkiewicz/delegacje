{{--
  Przystanek loc: wiersz — dane | notatka | akcje (zawsze na końcu, na lg z prawej).
--}}
@props([
    'wireKeyPrefix' => 'rwp-loc',
    'listPosition' => 1,
    'routeIndex' => 0,
    'locId',
    'typeLabel' => null,
    'name' => '—',
    'city' => null,
    'address' => null,
    'canMoveUp' => false,
    'canMoveDown' => false,
    'moveUpMethod' => 'moveWaypointUp',
    'moveDownMethod' => 'moveWaypointDown',
    'removeMethod' => 'removeWaypoint',
    'removeConfirm' => null,
    'notesWirePrefix' => 'locationStopNotes',
    'notesLabel' => 'Notatka do przystanku',
    'notesPlaceholder' => 'Krótka notatka (np. odbiór dokumentów)…',
    'showStopBadge' => false,
    'stopBadge' => '',
    'showReorder' => true,
    /** Unikalny identyfikator wiersza (np. token trasy loc:5) — stabilny przy zmianie kolejności; inaczej morph Livewire psuje modale. */
    'stableRowKey' => null,
])

@php
    $locIdStr = (string) $locId;
    $rowWireKey = $stableRowKey ?? ($wireKeyPrefix.'-'.$locIdStr.'-'.(int) $routeIndex);
@endphp

@once('logistics-rwp-card-styles')
    <style>
        .logistics-rwp-stop {
            --rwp-border: rgba(148, 163, 184, 0.28);
            --rwp-bg: rgba(30, 41, 59, 0.55);
            background: var(--rwp-bg);
            border: 1px solid var(--rwp-border) !important;
            border-radius: 0.65rem;
            padding: 0.65rem 0.85rem;
            margin-bottom: 0.5rem;
        }
        .logistics-rwp-stop-row {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }
        @media (min-width: 992px) {
            .logistics-rwp-stop-row {
                flex-direction: row;
                align-items: center;
                gap: 0.75rem;
            }
            .logistics-rwp-stop-main {
                flex: 0 1 14rem;
                min-width: 0;
            }
            .logistics-rwp-notes-col {
                flex: 1 1 10rem;
                min-width: 0;
            }
            .logistics-rwp-toolbar {
                flex: 0 0 auto;
                margin-left: auto;
            }
        }
        .logistics-rwp-stop-num {
            width: 1.85rem;
            height: 1.85rem;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            background: rgba(99, 102, 241, 0.2);
            color: #c7d2fe;
            border: 1px solid rgba(129, 140, 248, 0.35);
        }
        .logistics-rwp-stop-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main, #f1f5f9);
            line-height: 1.25;
        }
        .logistics-rwp-stop-kind {
            font-size: 0.62rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgba(148, 163, 184, 0.95);
            font-weight: 700;
        }
        .logistics-rwp-stop-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.3;
            margin-top: 0.1rem;
        }
        .logistics-rwp-icon-btn {
            width: 2rem;
            height: 2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(15, 23, 42, 0.45);
            color: #e2e8f0;
            font-size: 0.85rem;
            line-height: 1;
            transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease;
        }
        .logistics-rwp-icon-btn:hover:not(:disabled) {
            background: rgba(51, 65, 85, 0.75);
            border-color: rgba(148, 163, 184, 0.55);
            color: #fff;
        }
        .logistics-rwp-icon-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }
        .logistics-rwp-icon-btn--danger {
            border-color: rgba(248, 113, 113, 0.35);
            color: #fca5a5;
            background: rgba(127, 29, 29, 0.2);
        }
        .logistics-rwp-icon-btn--danger:hover:not(:disabled) {
            background: rgba(185, 28, 28, 0.35);
            border-color: rgba(252, 165, 165, 0.55);
            color: #fecaca;
        }
        .logistics-rwp-notes {
            min-height: 2.35rem;
            resize: vertical;
            font-size: 0.8rem;
            border-radius: 0.5rem;
            background: rgba(15, 23, 42, 0.35);
            border-color: rgba(148, 163, 184, 0.35);
        }
        .logistics-rwp-notes:focus {
            border-color: rgba(129, 140, 248, 0.55);
            box-shadow: 0 0 0 0.15rem rgba(99, 102, 241, 0.2);
        }
        .logistics-rwp-toolbar .logistics-rwp-icon-btn + .logistics-rwp-icon-btn {
            margin-left: 0.15rem;
        }
    </style>
@endonce

<div class="logistics-rwp-stop"
     wire:key="{{ $rowWireKey }}">
    <div class="logistics-rwp-stop-row">
        <div class="logistics-rwp-stop-main">
            <div class="d-flex align-items-start gap-2">
                <div class="logistics-rwp-stop-num">{{ $listPosition }}</div>
                <div class="min-w-0 flex-grow-1">
                    @if($showStopBadge && $stopBadge !== '')
                        <span class="badge rounded-pill border fw-normal mb-1"
                              style="font-size: 0.62rem; background: rgba(51,65,85,0.5); color: #cbd5e1; border-color: rgba(148,163,184,0.35) !important;">{{ $stopBadge }}</span>
                    @endif
                    @if($typeLabel)
                        <div class="logistics-rwp-stop-kind mb-1">{{ $typeLabel }}</div>
                    @endif
                    <div class="logistics-rwp-stop-title text-break">{{ $name }}</div>
                    @if(! empty($address) || ! empty($city))
                        <div class="logistics-rwp-stop-meta text-break">
                            @if(! empty($address))
                                {{ $address }}@if(! empty($city)), {{ $city }}@endif
                            @else
                                {{ $city }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="logistics-rwp-notes-col">
            <label class="visually-hidden" for="rwp-notes-{{ $wireKeyPrefix }}-{{ $locIdStr }}">{{ $notesLabel }}</label>
            <textarea
                id="rwp-notes-{{ $wireKeyPrefix }}-{{ $locIdStr }}"
                class="form-control logistics-rwp-notes"
                rows="2"
                placeholder="{{ $notesPlaceholder }}"
                aria-label="{{ $notesLabel }}"
                wire:model.live.debounce.300ms="{{ $notesWirePrefix }}.{{ $locIdStr }}"
            ></textarea>
        </div>

        <div class="d-flex align-items-center justify-content-end logistics-rwp-toolbar gap-1 pt-1 pt-lg-0">
            @if($showReorder)
                <button type="button" class="logistics-rwp-icon-btn"
                        wire:click="{{ $moveUpMethod }}({{ (int) $routeIndex }})"
                        @if(! $canMoveUp) disabled @endif
                        title="Wyżej">
                    <i class="bi bi-chevron-up"></i>
                </button>
                <button type="button" class="logistics-rwp-icon-btn"
                        wire:click="{{ $moveDownMethod }}({{ (int) $routeIndex }})"
                        @if(! $canMoveDown) disabled @endif
                        title="Niżej">
                    <i class="bi bi-chevron-down"></i>
                </button>
            @endif
            <button type="button"
                    class="logistics-rwp-icon-btn logistics-rwp-icon-btn--danger"
                    wire:click="{{ $removeMethod }}({{ (int) $routeIndex }})"
                    @if($removeConfirm) wire:confirm="{{ $removeConfirm }}" @endif
                    title="Usuń z trasy">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
