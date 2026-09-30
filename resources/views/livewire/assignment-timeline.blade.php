<x-ui.card class="atl-card">
<div class="atl" data-start="{{ $board['start'] }}" data-days="{{ $board['day_count'] }}" style="--atl-day: {{ $board['day_width'] }}px; --atl-days: {{ $board['day_count'] }}" x-data="assignmentTimeline()">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <div>
            <div class="atl-heading">{{ $heading }}</div>
            <div class="font-mono text-muted small">{{ \Carbon\Carbon::parse($board['start'])->format('j.m.Y') }} – {{ \Carbon\Carbon::parse($board['end'])->format('j.m.Y') }}</div>
        </div>
        <div class="d-flex gap-2">
            <x-ui.button type="button" variant="ghost" class="btn-sm" wire:click="shift(-4)">Wcześniej</x-ui.button>
            <x-ui.button type="button" variant="ghost" class="btn-sm" wire:click="shift(4)">Później</x-ui.button>
        </div>
    </div>

    <p class="atl-phone-note d-lg-none">Pusty tor rysujesz w bok, krawędź paska ciągniesz, środek usuwa.</p>

    <div class="atl-scroll" x-ref="scroller">
        <div class="atl-canvas">
            <div class="atl-scale">
                <div class="atl-label"></div>
                <div class="atl-scale-track">
                    @foreach($board['ticks'] as $tick)
                        <span class="atl-tick font-mono" style="left: {{ $tick['left'] }}px">{{ $tick['label'] }}</span>
                    @endforeach
                </div>
            </div>
            <div class="atl-body">
                <div class="atl-overlay">
                    @foreach($board['bands'] as $band)
                        <span class="atl-band" style="left: {{ $band['left'] }}px; width: {{ $band['width'] }}px"></span>
                    @endforeach
                    @foreach($board['markers'] as $marker)
                        <span class="atl-marker atl-marker--{{ $marker['kind'] }}" style="left: {{ $marker['left'] }}px">
                            <span class="atl-marker__label font-mono">{{ $marker['label'] }}</span>
                        </span>
                    @endforeach
                </div>
                @foreach($board['lanes'] as $lane)
                    <div
                        class="atl-lane"
                        data-lane="{{ $lane['key'] }}"
                        data-can-create="{{ $lane['can_create'] ? '1' : '0' }}"
                        data-can-delete="{{ !empty($lane['can_delete']) ? '1' : '0' }}"
                        data-gaps='@json($lane['gaps'])'
                        wire:key="atl-lane-{{ $lane['key'] }}"
                    >
                        <div class="atl-label">{{ $lane['label'] }}</div>
                        <div class="atl-track" style="--atl-rows: {{ $lane['rows'] ?? 1 }}">
                            @foreach($lane['bars'] as $bar)
                                <div
                                    class="atl-bar atl-bar--{{ $lane['key'] }} {{ $bar['open'] ? 'is-open' : '' }} {{ !empty($bar['pending']) ? 'is-pending' : '' }} {{ ($selection['lane'] ?? null) === $lane['key'] && ($selection['id'] ?? null) === $bar['id'] ? 'is-selected' : '' }}"
                                    style="left: {{ $bar['left'] }}px; width: {{ $bar['width'] }}px; --row: {{ $bar['row'] ?? 0 }}"
                                    data-id="{{ $bar['id'] }}"
                                    data-start="{{ $bar['start'] }}"
                                    data-end="{{ $bar['end'] ?? '' }}"
                                    data-min="{{ $bar['min'] }}"
                                    data-max="{{ $bar['max'] }}"
                                    data-open="{{ $bar['open'] ? '1' : '0' }}"
                                    data-locked="{{ !empty($bar['locked']) ? '1' : '0' }}"
                                    wire:key="atl-bar-{{ $lane['key'] }}-{{ $bar['id'] }}-{{ $bar['start'] }}"
                                >
                                    @if($lane['can_update'] && empty($bar['locked']) && empty($bar['pending']))
                                        <span class="atl-handle atl-handle--start" data-edge="start"></span>
                                        <span class="atl-handle atl-handle--end" data-edge="end"></span>
                                    @endif
                                    <span class="atl-bar__label">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                            <div class="atl-rubber" x-show="dragging && laneKey === @js($lane['key'])" x-cloak :style="rubberStyle"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="atl-tip font-mono" x-show="dragging" x-cloak x-text="tip" :style="tipStyle"></div>

    @php
        $menuCss = $menuX === null
            ? ''
            : 'left: '.$menuX.'px; top: '.$menuY.'px; transform: '.($menuAbove ? 'translate(-50%, -100%)' : 'translate(-50%, 0)');
    @endphp

    @if($proposal)
        @php
            $selected = collect($options)->firstWhere('key', $choice);
            $needsChoice = $proposal['id'] === null && $proposal['lane'] !== 'rotation';
            $driverTaken = (bool) ($selected['driver_taken'] ?? false);
        @endphp
        <div class="atl-popover atl-menu" style="{{ $menuCss }}">
            <div class="atl-popover__dates font-mono">
                {{ \Carbon\Carbon::parse($proposal['start'])->format('j.m.Y') }}
                –
                {{ $proposal['keep_open'] ? 'otwarte' : \Carbon\Carbon::parse($proposal['end'])->format('j.m.Y') }}
            </div>

            @if($error)
                <div class="atl-popover__error">{{ $error }}</div>
            @endif

            @if($needsChoice)
                <div class="atl-options">
                    @forelse($options as $option)
                        <button
                            type="button"
                            class="atl-option {{ $choice === $option['key'] ? 'is-selected' : '' }} {{ $option['enabled'] ? '' : 'is-disabled' }}"
                            @disabled(! $option['enabled'])
                            wire:click="$set('choice', '{{ $option['key'] }}')"
                            wire:key="atl-opt-{{ $option['key'] }}"
                        >
                            <span>{{ $option['label'] }}</span>
                            <span class="font-mono">{{ $option['meta'] }}</span>
                        </button>
                    @empty
                        <p class="text-muted small mb-0">
                            @if($proposal['lane'] === 'accommodation')
                                Brak domów z miejscem w tym zakresie.
                            @elseif($proposal['lane'] === 'project')
                                Brak ról z zapotrzebowaniem w tym zakresie.
                            @else
                                Brak pozycji w tym zakresie.
                            @endif
                        </p>
                    @endforelse
                </div>
            @endif

            @if($proposal['id'] === null && $proposal['lane'] === 'vehicle')
                <div class="atl-seats">
                    <button type="button" class="atl-option {{ $seat === 'driver' ? 'is-selected' : '' }} {{ $driverTaken ? 'is-disabled' : '' }}" @disabled($driverTaken) wire:click="$set('seat', 'driver')">
                        <span>Kierowca</span>
                        <span class="font-mono">{{ $driverTaken ? 'zajęty' : 'wolny' }}</span>
                    </button>
                    <button type="button" class="atl-option {{ $seat === 'passenger' ? 'is-selected' : '' }}" wire:click="$set('seat', 'passenger')">
                        <span>Pasażer</span>
                        <span class="font-mono">miejsce</span>
                    </button>
                </div>
            @endif

            <div class="atl-menu__actions d-flex gap-2 flex-wrap justify-content-end">
                <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
                <button type="button" class="btn btn-primary" wire:click="confirm" wire:loading.attr="disabled" @disabled($needsChoice && ! $choice)>
                    Zapisz
                </button>
            </div>
        </div>
    @elseif($selection)
        <div class="atl-popover atl-menu" style="{{ $menuCss }}">
            <div class="atl-popover__dates font-mono">
                @if(!empty($selection['label']))
                    {{ $selection['label'] }} ·
                @endif
                {{ \Carbon\Carbon::parse($selection['start'])->format('j.m.Y') }}
                –
                {{ empty($selection['end']) ? 'otwarte' : \Carbon\Carbon::parse($selection['end'])->format('j.m.Y') }}
            </div>
            <p class="text-muted small mb-0">Usunąć ten pasek?</p>
            @if($error)
                <div class="atl-popover__error">{{ $error }}</div>
            @endif
            <div class="atl-menu__actions d-flex gap-2 flex-wrap justify-content-end">
                <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
                <button type="button" class="btn btn-danger" wire:click="deleteSelected" wire:loading.attr="disabled">
                    Usuń
                </button>
            </div>
        </div>
    @elseif($error)
        <div class="atl-popover atl-menu" style="{{ $menuCss }}">
            <div class="atl-popover__error">{{ $error }}</div>
            <div class="d-flex justify-content-end">
                <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
            </div>
        </div>
    @endif
</div>
</x-ui.card>
