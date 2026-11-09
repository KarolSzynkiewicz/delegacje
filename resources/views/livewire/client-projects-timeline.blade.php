<div>
<div
    class="atl pdt pdt--client cl-reveal"
    data-start="{{ $board['start'] }}"
    data-weeks="{{ $board['week_count'] }}"
    style="--atl-day: {{ $board['week_width'] }}px; --atl-days: {{ $board['week_count'] }}"
    x-data="clientProjectsTimeline()"
>
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <div>
            <div class="atl-heading">Oś zapotrzebowania</div>
            <div class="font-mono text-muted small">
                Portfolio
                {{ \Carbon\Carbon::parse($board['portfolio_start'])->format('j.m.Y') }}
                –
                {{ \Carbon\Carbon::parse($board['portfolio_end'])->format('j.m.Y') }}
                · {{ $board['project_count'] }} {{ $board['project_count'] === 1 ? 'projekt' : 'projekty' }}
                · numery tygodni ISO
            </div>
        </div>
    </div>

    <p class="atl-phone-note mb-2">
        Tory = projekty klienta. Paski = suma osób z zapotrzebowań w danym okresie.
        Edycja zakresów w karcie projektu.
    </p>

    @if(empty($board['lanes']))
        <div class="pda-empty">
            <div class="small mb-0">Brak projektów — oś pojawi się po dodaniu pierwszego.</div>
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
                            >
                                <span class="atl-marker__label font-mono">{{ $marker['label'] }}</span>
                            </span>
                        @endforeach
                    </div>

                    @foreach($board['lanes'] as $lane)
                        <div
                            class="atl-lane pdt-lane {{ !empty($lane['empty']) ? 'pdt-lane--empty' : 'pdt-lane--active' }}"
                            wire:key="cpt-lane-{{ $lane['key'] }}"
                        >
                            <div class="atl-label">
                                <a href="{{ $lane['url'] }}" class="cpt-lane-link">{{ $lane['label'] }}</a>
                                @if(($lane['peak'] ?? 0) > 0)
                                    <span class="cpt-lane-peak font-mono">max {{ $lane['peak'] }}</span>
                                @endif
                            </div>
                            <div class="atl-track" style="--atl-rows: {{ $lane['rows'] ?? 1 }}">
                                @foreach($lane['bars'] as $bar)
                                    <div
                                        class="atl-bar atl-bar--project is-locked"
                                        style="left: {{ $bar['left'] }}px; width: {{ $bar['width'] }}px; --row: {{ $bar['row'] ?? 0 }}"
                                        title="{{ $bar['label'] }} · {{ \Carbon\Carbon::parse($bar['start'])->format('d.m') }}–{{ \Carbon\Carbon::parse($bar['end'])->format('d.m.Y') }}"
                                        wire:key="cpt-bar-{{ $lane['key'] }}-{{ $bar['id'] }}"
                                    >
                                        <span class="atl-bar__label">{{ $bar['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="atl-lane pdt-agg" wire:key="cpt-aggregate">
                        <div class="atl-label">
                            <span>Razem</span>
                            @if(($board['aggregate']['max'] ?? 0) > 0)
                                <span class="pdt-agg__max font-mono">max {{ $board['aggregate']['max'] }}</span>
                            @endif
                        </div>
                        <div class="atl-track pdt-agg__track" style="--atl-rows: 1">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@include('livewire.partials.project-demand-timeline-styles')
</div>
