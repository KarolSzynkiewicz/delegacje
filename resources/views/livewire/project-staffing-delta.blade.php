<div>
    <button
        type="button"
        class="wo-delta-btn"
        wire:click="openModal"
        title="Zmiany w obsadzie"
        aria-label="Zmiany w obsadzie"
    >
        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
        <span class="wo-delta-btn__label">Zmiany</span>
    </button>

    @if($show)
        @teleport('body')
            <div
                class="modal fade show d-block wo-delta-modal"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                wire:click.self="closeModal"
                wire:keydown.escape.window="closeModal"
                wire:key="staffing-delta-{{ $projectId }}"
            >
                <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                    <div class="modal-content wo-delta-panel">
                        <div class="modal-header wo-delta-panel__head">
                            <div>
                                <h5 class="modal-title mb-0">Zmiany w obsadzie</h5>
                                <div class="wo-delta-panel__sub text-muted small">
                                    Ruch osobowy
                                    @if($delta['week_label'] ?? null)
                                        · {{ $delta['week_label'] }}
                                    @endif
                                    @if($projectName !== '')
                                        · {{ $projectName }}
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">
                            <div wire:loading.flex wire:target="openModal" class="align-items-center gap-2 text-muted small mb-3">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Ładuję zmiany…
                            </div>

                            <div wire:loading.remove wire:target="openModal">
                                @if($loaded && $delta)
                                    @php
                                        $bands = [
                                            [
                                                'key' => 'past',
                                                'label' => 'Zeszły tydzień',
                                                'span' => $delta['band_labels']['past'] ?? null,
                                                'columns' => [
                                                    [
                                                        'key' => 'left',
                                                        'title' => 'Odeszli',
                                                        'tone' => 'left',
                                                        'rows' => $delta['left'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'key' => 'now',
                                                'label' => 'Ten tydzień',
                                                'span' => $delta['band_labels']['now'] ?? null,
                                                'columns' => [
                                                    [
                                                        'key' => 'arrived',
                                                        'title' => 'Przybyli',
                                                        'tone' => 'arrived',
                                                        'rows' => $delta['arrived'],
                                                    ],
                                                    [
                                                        'key' => 'ending',
                                                        'title' => 'Nie będzie w przyszłym',
                                                        'tone' => 'ending',
                                                        'rows' => $delta['ending'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'key' => 'next',
                                                'label' => 'Przyszły tydzień',
                                                'span' => $delta['band_labels']['next'] ?? null,
                                                'columns' => [
                                                    [
                                                        'key' => 'arriving',
                                                        'title' => 'Przyjeżdżają',
                                                        'tone' => 'arriving',
                                                        'rows' => $delta['arriving'],
                                                    ],
                                                ],
                                            ],
                                        ];
                                    @endphp

                                    <div class="wo-delta-bands">
                                        @foreach($bands as $band)
                                            <section class="wo-delta-band wo-delta-band--{{ $band['key'] }}">
                                                <header class="wo-delta-band__head">
                                                    <span class="wo-delta-band__label">{{ $band['label'] }}</span>
                                                    @if($band['span'])
                                                        <span class="wo-delta-band__span font-mono">{{ $band['span'] }}</span>
                                                    @endif
                                                </header>
                                                <div class="wo-delta-band__cols">
                                                    @foreach($band['columns'] as $column)
                                                        <div class="wo-delta-col wo-delta-col--{{ $column['tone'] }}">
                                                            <header class="wo-delta-col__head">
                                                                <span class="wo-delta-col__dot" aria-hidden="true"></span>
                                                                <span class="wo-delta-col__title">{{ $column['title'] }}</span>
                                                                <span class="wo-delta-col__count font-mono">{{ $column['rows']->count() }}</span>
                                                            </header>
                                                            <div class="wo-delta-col__list">
                                                                @forelse($column['rows'] as $row)
                                                                    @include('weekly-overview.partials.staffing-delta-person', ['row' => $row])
                                                                @empty
                                                                    <p class="wo-delta-empty text-muted small mb-0">Brak zmian.</p>
                                                                @endforelse
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </section>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="modal-footer wo-delta-panel__foot">
                            <span class="text-muted small">Delta tygodniowa — nie pełny planer</span>
                            <span class="text-muted small font-mono">Esc zamknij</span>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
