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

<style>
    .pdt .atl-marker__label { white-space: nowrap; }
    .pdt-marker--project-start { border-left: 2px solid #34d399; }
    .pdt-marker--project-end { border-left: 2px solid #f472b6; }
    .pdt-marker--today { border-left: 2px dashed #fbbf24; }
    .pdt .atl-bar { cursor: grab; }
    .pdt .atl-bar:active { cursor: grabbing; }
    .pdt .atl-lane[data-can-create="1"] .atl-track { cursor: crosshair; }

    .pdt-scale .atl-scale-track { height: 2.35rem; }
    .pdt-tick {
        top: 0.2rem;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.05rem;
        padding-left: 0.35rem;
        box-sizing: border-box;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--text-muted);
        line-height: 1.05;
    }
    .pdt-tick.is-pad { opacity: 0.45; }
    .pdt-tick.is-project { color: var(--text-main); }
    .pdt-tick.is-current {
        color: #fbbf24;
        border-left-color: rgba(251, 191, 36, 0.55);
    }
    .pdt-tick__week {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .pdt-tick__day {
        font-size: 0.58rem;
        opacity: 0.72;
        letter-spacing: 0.02em;
    }

    .pdt-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }
    .pdt-grid__line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 0;
        border-left: 1px solid rgba(255, 255, 255, 0.06);
    }
    .pdt-grid__line.is-project { border-left-color: rgba(148, 163, 184, 0.14); }
    .pdt-grid__line.is-current { border-left: 1px dashed rgba(251, 191, 36, 0.45); }

    .pdt-lane {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    .pdt-lane--active {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.05), transparent 18%);
    }
    .pdt-lane--active .atl-label {
        color: var(--text-main);
        font-weight: 700;
    }
    .pdt-lane--active .atl-track {
        background:
            repeating-linear-gradient(
                90deg,
                transparent 0,
                transparent calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.035) calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.035) var(--atl-day)
            );
    }
    .pdt-lane--empty { opacity: 0.55; }
    .pdt-lane--empty .atl-label { font-weight: 550; }
    .pdt-lane--empty .atl-track {
        background: rgba(255, 255, 255, 0.015);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    }
    .pdt .atl-bar--project {
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.12),
            0 6px 16px rgba(59, 130, 246, 0.18);
    }

    .pdt-agg {
        margin-top: 0.45rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 0.45rem;
        background: linear-gradient(90deg, rgba(168, 85, 247, 0.06), transparent 22%);
    }
    .pdt-agg .atl-label {
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 0.15rem;
        line-height: 1.1;
        color: var(--text-main);
        font-weight: 700;
    }
    .pdt-agg__max {
        font-size: 0.58rem;
        letter-spacing: 0.04em;
        text-transform: none;
        color: rgba(148, 163, 184, 0.85);
    }
    .pdt-agg__track {
        height: 4.6rem !important;
        cursor: default !important;
        background:
            repeating-linear-gradient(
                90deg,
                transparent 0,
                transparent calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.04) calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.04) var(--atl-day)
            );
    }
    .pdt-agg__band {
        position: absolute;
        top: 0.2rem;
        bottom: 0.2rem;
        border-radius: 8px;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12), rgba(168, 85, 247, 0.1));
        box-shadow: inset 0 0 0 1px rgba(168, 85, 247, 0.28);
        pointer-events: none;
        z-index: 0;
    }
    .pdt-agg__col {
        position: absolute;
        bottom: 0.35rem;
        top: 0.45rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 0.15rem;
        pointer-events: none;
        z-index: 1;
    }
    .pdt-agg__bar {
        width: calc(100% - 12px);
        min-height: 3px;
        border-radius: 5px 5px 2px 2px;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.95), rgba(168, 85, 247, 0.75));
        box-shadow: 0 0 12px rgba(59, 130, 246, 0.18);
    }
    .pdt-agg__n {
        font-size: 0.62rem;
        color: var(--text-main);
        line-height: 1;
    }
    .pdt-bound {
        position: absolute;
        top: 0.15rem;
        bottom: 0.15rem;
        width: 18px;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        transform: translateX(-50%);
        z-index: 5;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.15rem;
        pointer-events: auto;
    }
    .pdt-bound.is-draggable { cursor: ew-resize; touch-action: none; }
    .pdt-bound__grip {
        flex: 1 1 auto;
        width: 3px;
        border-radius: 3px;
        background: #34d399;
        box-shadow: 0 0 0 1px rgba(7, 10, 19, 0.55);
    }
    .pdt-bound--end .pdt-bound__grip { background: #f472b6; }
    .pdt-bound__label {
        font-size: 0.55rem;
        letter-spacing: 0.06em;
        color: var(--text-main);
        background: rgba(13, 18, 30, 0.92);
        border: 1px solid var(--glass-border);
        border-radius: 4px;
        padding: 0.05rem 0.25rem;
        white-space: nowrap;
        pointer-events: none;
    }
    .pdt-bound-menu { min-width: 260px; max-width: 320px; }
</style>
</div>
