<x-ui.card class="rax-card">
    <div
        class="rax"
        wire:key="rax-axis-{{ $board['start'] }}-{{ $board['end'] }}"
        x-data="assignmentTimeline()"
        data-start="{{ $board['start'] }}"
        data-days="{{ $board['day_count'] }}"
        style="--atl-day: {{ $board['day_width'] }}px; --atl-days: {{ $board['day_count'] }}; --rax-day: {{ $board['day_width'] }}px; --rax-days: {{ $board['day_count'] }}"
    >
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <div class="rax-heading">Rotacje · oś</div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                    <div class="rax-period btn-group" role="group" aria-label="Okres osi">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="previousPeriod" title="Poprzedni okres">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary font-mono {{ $periodOffset === 0 ? 'disabled' : '' }}"
                            wire:click="resetPeriod"
                            @disabled($periodOffset === 0)
                            title="Wróć do bieżącego okna"
                        >
                            {{ $board['range_label'] }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="nextPeriod" title="Następny okres">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    @if($periodOffset !== 0)
                        <span class="small text-muted">przesunięcie {{ $periodOffset > 0 ? '+'.$periodOffset : $periodOffset }}</span>
                    @endif
                </div>
                <p class="rax-hint">Pusty tor rysujesz w bok, daty ciągniesz za krawędź, środek paska — szczegóły / usuwanie. W torze: rotacja · projekt · dom · auto.</p>
            </div>
            <div class="d-flex flex-column align-items-stretch align-items-md-end gap-2">
                <div class="rax-legend">
                    @foreach([
                        'past' => 'Historyczne',
                        'active' => 'Aktywne',
                        'soon' => 'Wygasają wkrótce',
                        'future' => 'Przyszłe',
                    ] as $tone => $label)
                        <button
                            type="button"
                            class="rax-legend__btn {{ in_array($tone, $tones, true) ? 'is-active' : '' }}"
                            @if($tone === 'soon') title="Kończą się w ciągu 7 dni (nadal aktywne)" @endif
                            @if($tone === 'active') title="Trwające teraz — w tym te, które wygasają wkrótce" @endif
                            wire:click="toggleTone('{{ $tone }}')"
                        >
                            <i class="rax-dot rax-dot--{{ $tone }}"></i> {{ $label }}
                        </button>
                    @endforeach
                </div>
                <label class="rax-sort small text-muted mb-0 d-flex align-items-center gap-2">
                    <span class="text-nowrap">Sortuj</span>
                    <select class="form-select form-select-sm rax-sort__select" wire:model.live="sort">
                        <option value="ends_soon">Najszybciej kończące się</option>
                        <option value="starts_soon">Najszybciej zaczynające się</option>
                        <option value="name">Nazwisko A→Z</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="rax-board">
            <div class="rax-body" x-ref="scroller">
                <div class="rax-sheet">
                    <div class="rax-tools">
                        <div class="rax-place">
                            <button type="button" class="rax-place__btn {{ $place === 'base' ? 'is-active' : '' }}" wire:click="setPlace('base')">W bazie</button>
                            <button type="button" class="rax-place__btn {{ $place === 'away' ? 'is-active' : '' }}" wire:click="setPlace('away')">Poza bazą</button>
                        </div>
                        <div class="rax-search">
                            <input
                                type="search"
                                class="form-control form-control-sm"
                                placeholder="Imię, telefon…"
                                wire:model.live.debounce.300ms="search"
                            >
                            <span class="rax-count font-mono">{{ count($board['rows']) }}</span>
                        </div>
                    </div>
                    <div class="rax-canvas rax-canvas--scale">
                        <div class="rax-scale">
                            @foreach($board['days'] as $day)
                                <span class="rax-day {{ $day['weekend'] ? 'is-weekend' : '' }}">
                                    <span class="rax-day__week">{{ $day['weekday'] }}</span>
                                    <span class="rax-day__num font-mono">{{ $day['number'] }}</span>
                                </span>
                            @endforeach
                            @if($board['today_left'] !== null)
                                <span class="rax-marker rax-marker--today" style="left: {{ $board['today_left'] }}px">Dziś</span>
                            @endif
                            @if($board['wednesday_left'] !== null)
                                <span class="rax-marker rax-marker--wed" style="left: {{ $board['wednesday_left'] }}px">Nast. śr.</span>
                            @endif
                        </div>
                    </div>
                    <div class="rax-side" x-ref="side">
                        @forelse($board['rows'] as $row)
                            <div class="rax-person {{ $loop->even ? 'is-alt' : '' }}" wire:key="rax-person-{{ $row['id'] }}">
                                <div class="wo-emp">
                                    <x-employee-cell :employee="$row['employee']" />
                                    <div class="wo-emp-meta">
                                        <x-ui.rating
                                            :score="$row['score']"
                                            :evaluation="$row['evaluation']"
                                            :show-empty="true"
                                        />
                                        <span class="wo-emp-meta__rule" aria-hidden="true"></span>
                                        <x-planner-document-icons
                                            :documents="$row['documents']"
                                            :show-empty="true"
                                            stacked
                                        />
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="rax-empty text-muted small mb-0">Brak aktywnych pracowników dla tego filtra.</p>
                        @endforelse
                    </div>
                    <div class="rax-canvas">
                        <div class="rax-plot">
                            @foreach($board['days'] as $index => $day)
                                @if($day['weekend'])
                                    <span class="rax-weekend" style="left: {{ $index * $board['day_width'] }}px"></span>
                                @endif
                            @endforeach
                            @if($board['today_left'] !== null)
                                <span class="rax-line rax-line--today" style="left: {{ $board['today_left'] }}px"></span>
                            @endif
                            @if($board['wednesday_left'] !== null)
                                <span class="rax-line rax-line--wed" style="left: {{ $board['wednesday_left'] }}px"></span>
                            @endif
                            @foreach($board['rows'] as $row)
                                <div
                                    class="rax-lane atl-lane {{ $loop->even ? 'is-alt' : '' }}"
                                    data-lane="rotation"
                                    data-employee="{{ $row['id'] }}"
                                    data-can-create="{{ $board['can_create'] ? '1' : '0' }}"
                                    data-can-delete="{{ $board['can_delete'] ? '1' : '0' }}"
                                    data-gaps='@json($row['gaps'])'
                                    style="--atl-rows: {{ (int) ($row['track_rows'] ?? 4) }}"
                                    wire:key="rax-lane-{{ $row['id'] }}"
                                >
                                    <div class="atl-track rax-track">
                                        @foreach(($row['context'] ?? []) as $ctx)
                                            <div
                                                class="atl-bar rax-bar rax-bar--context atl-bar--{{ $ctx['kind'] }} {{ !empty($ctx['open']) ? 'is-open' : '' }}"
                                                style="left: {{ $ctx['left'] }}px; width: {{ $ctx['width'] }}px; --row: {{ (int) $ctx['row'] }}"
                                                title="{{ $ctx['title'] }}"
                                                wire:key="rax-ctx-{{ $row['id'] }}-{{ $ctx['id'] }}"
                                            >
                                                <span class="atl-bar__label">{{ $ctx['label'] }}</span>
                                            </div>
                                        @endforeach
                                        @foreach($row['bars'] as $bar)
                                            <div
                                                class="atl-bar rax-bar rax-bar--{{ $bar['tone'] }} {{ !empty($bar['pending']) ? 'is-pending' : '' }} {{ ($selection['id'] ?? null) === $bar['id'] && ($selection['employee_id'] ?? null) === $row['id'] ? 'is-selected' : '' }}"
                                                style="left: {{ $bar['left'] }}px; width: {{ $bar['width'] }}px; --row: {{ (int) ($bar['row'] ?? 0) }}"
                                                data-id="{{ $bar['id'] }}"
                                                data-start="{{ $bar['start'] }}"
                                                data-end="{{ $bar['end'] }}"
                                                data-min="{{ $bar['min'] }}"
                                                data-max="{{ $bar['max'] }}"
                                                data-open="0"
                                                title="{{ $bar['title'] }}"
                                                wire:key="rax-bar-{{ $row['id'] }}-{{ $bar['id'] }}-{{ $bar['start'] }}"
                                            >
                                                @if($board['can_update'] && empty($bar['pending']) && empty($bar['locked']))
                                                    <span class="atl-handle atl-handle--start" data-edge="start"></span>
                                                    <span class="atl-handle atl-handle--end" data-edge="end"></span>
                                                @endif
                                                <span class="atl-bar__label">{{ $bar['label'] ?? 'Rotacja' }}</span>
                                            </div>
                                        @endforeach
                                        <div class="atl-rubber" x-show="dragging && employeeId === {{ (int) $row['id'] }}" x-cloak :style="rubberStyle"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
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
                $person = collect($board['rows'])->firstWhere('id', $proposal['employee_id'] ?? null);
                $personName = $proposal['employee_name'] ?? ($person['name'] ?? null);
                $proposalNotes = trim((string) ($proposal['notes'] ?? ''));
                $proposalShowUrl = $proposal['show_url'] ?? null;
            @endphp
            <div class="atl-popover rax-menu" style="{{ $menuCss }}">
                <div class="atl-popover__dates font-mono">
                    @if($personName)
                        {{ $personName }} ·
                    @endif
                    {{ \Carbon\Carbon::parse($proposal['start'])->format('j.m.Y') }}
                    –
                    {{ \Carbon\Carbon::parse($proposal['end'])->format('j.m.Y') }}
                </div>
                @if($proposal['id'] ?? null)
                    <div class="rax-menu__notes small {{ $proposalNotes !== '' ? '' : 'text-muted' }}">
                        <span class="text-muted d-block" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Notatka</span>
                        {{ $proposalNotes !== '' ? $proposalNotes : 'Brak notatki' }}
                    </div>
                    @if($proposalShowUrl)
                        <a href="{{ $proposalShowUrl }}" class="btn btn-sm btn-outline-info w-100 text-start mb-2">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Otwórz kartę rotacji
                        </a>
                    @endif
                @endif
                @if($error)
                    <div class="atl-popover__error">{{ $error }}</div>
                @endif
                <div class="rax-menu__actions d-flex gap-2 flex-wrap justify-content-end">
                    <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
                    <button type="button" class="btn btn-primary" wire:click="confirm" wire:loading.attr="disabled">
                        Zapisz
                    </button>
                </div>
            </div>
        @elseif($selection)
            @php
                $person = collect($board['rows'])->firstWhere('id', $selection['employee_id']);
                $personName = $selection['employee_name'] ?? ($person['name'] ?? null);
                $showUrl = $selection['show_url'] ?? null;
                $canDelete = ! empty($selection['can_delete']);
                $canUpdate = ! empty($selection['can_update']);
            @endphp
            <div class="atl-popover rax-menu" style="{{ $menuCss }}">
                <div class="atl-popover__dates font-mono">
                    @if($personName)
                        {{ $personName }} ·
                    @endif
                    {{ \Carbon\Carbon::parse($selection['start'])->format('j.m.Y') }}
                    –
                    {{ \Carbon\Carbon::parse($selection['end'])->format('j.m.Y') }}
                </div>
                <div class="rax-menu__notes">
                    <label class="text-muted d-block mb-1" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;" for="rax-selection-notes">
                        Notatka
                    </label>
                    @if($canUpdate)
                        <textarea
                            id="rax-selection-notes"
                            class="form-control form-control-sm rax-menu__notes-input"
                            rows="3"
                            placeholder="Dodaj notatkę do rotacji…"
                            wire:model="selectionNotes"
                            wire:keydown.ctrl.enter="saveSelectionNotes"
                        ></textarea>
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                            <span class="small {{ $notesSaved ? 'text-success' : 'text-muted' }}">
                                @if($notesSaved)
                                    <i class="bi bi-check2 me-1"></i>Zapisano
                                @else
                                    Ctrl+Enter zapisuje
                                @endif
                            </span>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                wire:click="saveSelectionNotes"
                                wire:loading.attr="disabled"
                                wire:target="saveSelectionNotes"
                            >
                                <span wire:loading.remove wire:target="saveSelectionNotes">Zapisz notatkę</span>
                                <span wire:loading wire:target="saveSelectionNotes">Zapisuję…</span>
                            </button>
                        </div>
                    @else
                        <div class="small {{ trim($selectionNotes) !== '' ? '' : 'text-muted' }}">
                            {{ trim($selectionNotes) !== '' ? $selectionNotes : 'Brak notatki' }}
                        </div>
                    @endif
                </div>
                @if($showUrl)
                    <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-info w-100 text-start mb-2 mt-2">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Otwórz kartę rotacji
                    </a>
                @endif
                @if($canDelete)
                    <p class="text-muted small mb-0">Usunąć tę rotację?</p>
                @endif
                @if($error)
                    <div class="atl-popover__error">{{ $error }}</div>
                @endif
                <div class="rax-menu__actions d-flex gap-2 flex-wrap justify-content-end">
                    <x-ui.button type="button" variant="ghost" wire:click="cancel">Zamknij</x-ui.button>
                    @if($canDelete)
                        <button type="button" class="btn btn-danger" wire:click="deleteSelected" wire:loading.attr="disabled">
                            Usuń
                        </button>
                    @endif
                </div>
            </div>
        @elseif($error)
            <div class="atl-popover rax-menu" style="{{ $menuCss }}">
                <div class="atl-popover__error">{{ $error }}</div>
                <div class="d-flex justify-content-end">
                    <x-ui.button type="button" variant="ghost" wire:click="cancel">Cofnij</x-ui.button>
                </div>
            </div>
        @endif
    </div>
</x-ui.card>
