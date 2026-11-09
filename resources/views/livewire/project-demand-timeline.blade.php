@php
    $menuCss = $menuX === null
        ? ''
        : 'left: '.$menuX.'px; top: '.$menuY.'px; transform: '.($menuAbove ? 'translate(-50%, -100%)' : 'translate(-50%, 0)');
    $isCreate = ($proposal['mode'] ?? null) === 'create';
    $boundEdge = $boundProposal['edge'] ?? null;
    $deltaWeeks = (int) ($boundProposal['delta_weeks'] ?? 0);
@endphp

<div>
<div
    class="atl pdt"
    data-start="{{ $board['start'] }}"
    data-weeks="{{ $board['week_count'] }}"
    data-project-start="{{ $board['project_start'] }}"
    data-project-end="{{ $board['project_end'] }}"
    style="--atl-day: {{ $board['week_width'] }}px; --atl-days: {{ $board['week_count'] }}"
    x-data="demandTimeline()"
>
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <div>
            <div class="atl-heading">Oś zapotrzebowania</div>
            <div class="font-mono text-muted small">
                Projekt
                {{ \Carbon\Carbon::parse($board['project_start'])->format('j.m.Y') }}
                –
                {{ \Carbon\Carbon::parse($board['project_end'])->format('j.m.Y') }}
                · numery tygodni ISO (jak w planerze)
            </div>
        </div>
        <a href="{{ route('projects.demands.index', $project) }}" class="btn btn-sm btn-outline-secondary">
            Zarządzaj listą
        </a>
    </div>

    @if($canManage)
        <p class="atl-phone-note mb-2">
            Oś: <span class="font-mono">T44</span> = numer tygodnia. Przeciągnij po wolnym torze, by dodać zakres.
            Na agregacie złap Start / Koniec projektu.
        </p>
    @else
        <p class="atl-phone-note d-lg-none">Na desktopie: oś jest tylko do podglądu.</p>
    @endif

    @if(empty($board['lanes']))
        <div class="pda-empty">
            <div class="small mb-2">Brak profesji w systemie — dodaj role, żeby rysować zapotrzebowanie.</div>
            <a href="{{ route('projects.demands.create', $project) }}" class="btn btn-sm btn-primary">Dodaj zapotrzebowanie</a>
        </div>
    @else
        <div class="atl-scroll" x-ref="scroller">
            <div class="atl-canvas">
                <div class="atl-scale pdt-scale">
                    <div class="atl-label"></div>
                    <div class="atl-scale-track">
                        @foreach($board['ticks'] as $tick)
                            <span
                                class="atl-tick pdt-tick font-mono {{ !empty($tick['is_current']) ? 'is-current' : '' }} {{ !empty($tick['in_project']) ? 'is-project' : 'is-pad' }}"
                                style="left: {{ $tick['left'] }}px; width: {{ $board['week_width'] }}px"
                                title="{{ $tick['title'] ?? $tick['label'] }}"
                            >
                                <span class="pdt-tick__week">{{ $tick['label'] }}</span>
                                @if(!empty($tick['sub']))
                                    <span class="pdt-tick__day">{{ $tick['sub'] }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="atl-body">
                    <div class="atl-overlay">
                        <div class="pdt-grid" aria-hidden="true">
                            @foreach($board['ticks'] as $tick)
                                <span
                                    class="pdt-grid__line {{ !empty($tick['is_current']) ? 'is-current' : '' }} {{ !empty($tick['in_project']) ? 'is-project' : 'is-pad' }}"
                                    style="left: {{ $tick['left'] }}px"
                                ></span>
                            @endforeach
                        </div>
                        @foreach($board['markers'] as $marker)
                            <span
                                class="atl-marker pdt-marker pdt-marker--{{ $marker['kind'] }} {{ $marker['kind'] === 'today' ? 'rax-line--today' : '' }}"
                                style="left: {{ $marker['left'] }}px"
                                @if(!empty($marker['edge']) && !empty($marker['draggable']))
                                    :style="boundMarkerStyle(@js($marker['edge'])) || 'left: {{ $marker['left'] }}px'"
                                @endif
                            >
                                <span class="atl-marker__label font-mono">{{ $marker['label'] }}</span>
                            </span>
                        @endforeach
                    </div>
                    @foreach($board['lanes'] as $lane)
                        <div
                            class="atl-lane pdt-lane {{ !empty($lane['empty']) ? 'pdt-lane--empty' : 'pdt-lane--active' }}"
                            data-lane="{{ $lane['key'] }}"
                            data-can-create="{{ !empty($lane['can_create']) ? '1' : '0' }}"
                            data-can-delete="{{ !empty($lane['can_delete']) ? '1' : '0' }}"
                            data-gaps='@json($lane['gaps'] ?? [])'
                            wire:key="pdt-lane-{{ $lane['key'] }}"
                        >
                            <div class="atl-label">{{ $lane['label'] }}</div>
                            <div class="atl-track" style="--atl-rows: {{ $lane['rows'] ?? 1 }}">
                                @foreach($lane['bars'] as $bar)
                                    <div
                                        class="atl-bar atl-bar--project {{ !empty($bar['pending']) ? 'is-pending' : '' }} {{ ($selection['id'] ?? null) === $bar['id'] ? 'is-selected' : '' }}"
                                        style="left: {{ $bar['left'] }}px; width: {{ $bar['width'] }}px; --row: {{ $bar['row'] ?? 0 }}"
                                        data-id="{{ $bar['id'] }}"
                                        data-start="{{ $bar['start'] }}"
                                        data-end="{{ $bar['end'] ?? '' }}"
                                        data-min="{{ $bar['min'] }}"
                                        data-max="{{ $bar['max'] }}"
                                        data-open="0"
                                        data-locked="{{ $canManage && empty($bar['pending']) ? '0' : '1' }}"
                                        wire:key="pdt-bar-{{ $bar['id'] }}-{{ $bar['start'] }}"
                                    >
                                        @if($lane['can_update'] && empty($bar['pending']))
                                            <span class="atl-handle atl-handle--start" data-edge="start"></span>
                                            <span class="atl-handle atl-handle--end" data-edge="end"></span>
                                        @endif
                                        <span class="atl-bar__label">{{ $bar['label'] }}</span>
                                    </div>
                                @endforeach
                                <div
                                    class="atl-rubber"
                                    x-show="dragging && mode !== 'bound' && laneKey === @js($lane['key'])"
                                    x-cloak
                                    :style="rubberStyle"
                                ></div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Agregat: suma osób / tydzień + uchwyty start/koniec projektu --}}
                    <div class="atl-lane pdt-agg" wire:key="pdt-aggregate">
                        <div class="atl-label">
                            <span>Razem</span>
                            @if(($board['aggregate']['max'] ?? 0) > 0)
                                <span class="pdt-agg__max font-mono">max {{ $board['aggregate']['max'] }}</span>
                            @endif
                        </div>
                        <div class="atl-track pdt-agg__track" style="--atl-rows: 1">
                            <div class="pdt-agg__band" :style="projectBandStyle"></div>
                            @foreach($board['aggregate']['columns'] ?? [] as $col)
                                <div
                                    class="pdt-agg__col"
                                    style="left: {{ $col['left'] }}px; width: {{ $board['week_width'] }}px"
                                    title="{{ $col['label'] }} · {{ $col['count'] }} os."
                                >
                                    @if($col['count'] > 0)
                                        <div class="pdt-agg__bar" style="height: {{ number_format($col['height'], 1, '.', '') }}%"></div>
                                        <span class="pdt-agg__n font-mono">{{ $col['count'] }}</span>
                                    @endif
                                </div>
                            @endforeach

                            @foreach($board['markers'] as $marker)
                                @if(($marker['edge'] ?? null) === 'start' || ($marker['edge'] ?? null) === 'end')
                                    <button
                                        type="button"
                                        class="pdt-bound pdt-bound--{{ $marker['edge'] }} {{ !empty($marker['draggable']) ? 'is-draggable' : '' }}"
                                        style="left: {{ $marker['left'] }}px"
                                        data-edge="{{ $marker['edge'] }}"
                                        data-draggable="{{ !empty($marker['draggable']) ? '1' : '0' }}"
                                        data-date="{{ $marker['date'] ?? '' }}"
                                        title="{{ $marker['label'] }}"
                                        aria-label="{{ $marker['label'] }}"
                                        :style="boundMarkerStyle(@js($marker['edge'])) || 'left: {{ $marker['left'] }}px'"
                                    >
                                        <span class="pdt-bound__grip" aria-hidden="true"></span>
                                        <span class="pdt-bound__label font-mono">{{ $marker['edge'] === 'start' ? 'START' : 'KONIEC' }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="atl-tip font-mono" x-show="dragging" x-cloak x-text="tip" :style="tipStyle"></div>

    @if($boundProposal)
        <div class="atl-popover atl-menu pdt-bound-menu" style="{{ $menuCss }}">
            <div class="atl-popover__dates font-mono">
                {{ $boundProposal['label'] }}
            </div>
            <div class="font-mono small mb-2">
                {{ \Carbon\Carbon::parse($boundProposal['old'])->format('j.m.Y') }}
                →
                {{ \Carbon\Carbon::parse($boundProposal['date'])->format('j.m.Y') }}
                @if($boundEdge === 'start' && $deltaWeeks !== 0)
                    <span class="text-muted">
                        ({{ $deltaWeeks > 0 ? '+'.$deltaWeeks : $deltaWeeks }} tyg.)
                    </span>
                @endif
            </div>
            @if($error)
                <div class="atl-popover__error">{{ $error }}</div>
            @endif
            <div class="atl-menu__actions d-flex gap-2 flex-wrap justify-content-end">
                <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
                @if($boundEdge === 'start')
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        wire:click="confirmBound(false)"
                        wire:loading.attr="disabled"
                    >
                        Tylko datę projektu
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        wire:click="confirmBound(true)"
                        wire:loading.attr="disabled"
                        @disabled($deltaWeeks === 0)
                    >
                        Przenieś też zapotrzebowania
                    </button>
                @else
                    <button type="button" class="btn btn-primary btn-sm" wire:click="confirmBound(false)" wire:loading.attr="disabled">
                        Zapisz koniec
                    </button>
                @endif
            </div>
        </div>
    @elseif($proposal)
        <div class="atl-popover atl-menu" style="{{ $menuCss }}">
            <div class="atl-popover__dates font-mono">
                {{ $proposal['label'] }}
                @if($isCreate)
                    · nowy zakres
                @endif
            </div>
            <div class="font-mono small mb-2">
                {{ \Carbon\Carbon::parse($proposal['start'])->format('j.m.Y') }}
                –
                {{ \Carbon\Carbon::parse($proposal['end'])->format('j.m.Y') }}
            </div>
            <div class="mb-2">
                <label class="form-label small mb-1" for="pdt-draft-count">Liczba osób</label>
                <input
                    id="pdt-draft-count"
                    type="number"
                    min="1"
                    max="999"
                    class="form-control form-control-sm font-mono"
                    wire:model="draftCount"
                    wire:keydown.enter="confirm"
                >
            </div>
            @if($error)
                <div class="atl-popover__error">{{ $error }}</div>
            @endif
            <div class="atl-menu__actions d-flex gap-2 flex-wrap justify-content-end">
                <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
                <button type="button" class="btn btn-primary" wire:click="confirm" wire:loading.attr="disabled">
                    {{ $isCreate ? 'Dodaj' : 'Zapisz' }}
                </button>
            </div>
        </div>
    @elseif($selection)
        <div class="atl-popover atl-menu" style="{{ $menuCss }}">
            <div class="atl-popover__dates font-mono">
                {{ $selection['label'] }}
            </div>
            <div class="font-mono small mb-2">
                {{ \Carbon\Carbon::parse($selection['start'])->format('j.m.Y') }}
                –
                {{ empty($selection['end']) ? 'otwarte' : \Carbon\Carbon::parse($selection['end'])->format('j.m.Y') }}
            </div>
            <p class="text-muted small mb-0">Usunąć to zapotrzebowanie?</p>
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

@include('livewire.partials.project-demand-timeline-styles')
</div>
