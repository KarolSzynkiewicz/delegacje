@php
    use App\Enums\OfficeWorkLocation;
@endphp

<div
    class="oh-root"
    x-data="{
        formatTime(value) {
            if (value === null || value === undefined) return '';
            let s = String(value).trim().replace('.', ':').replace(',', ':');
            if (s === '') return '';
            if (/^\d{1,2}$/.test(s)) {
                const h = parseInt(s, 10);
                if (h <= 23) return String(h).padStart(2, '0') + ':00';
            }
            if (/^\d{3,4}$/.test(s)) {
                s = s.padStart(4, '0');
                return s.slice(0, 2) + ':' + s.slice(2, 4);
            }
            const m = s.match(/^(\d{1,2}):(\d{1,2})$/);
            if (m) {
                return String(m[1]).padStart(2, '0') + ':' + String(m[2]).padStart(2, '0');
            }
            return s;
        },
        onTimeBlur(event) {
            // capture phase — przed wire:model.blur, żeby Livewire dostał już HH:MM
            event.target.value = this.formatTime(event.target.value);
        },
        scrollToFirstError() {
            this.$nextTick(() => {
                const el = document.querySelector('.oh-root .oh-time--error, .oh-root .oh-cell--error');
                el?.scrollIntoView({ block: 'center', inline: 'center', behavior: 'smooth' });
            });
        }
    }"
    x-on:office-hours-highlight-errors.window="scrollToFirstError()"
>
    <div class="oh-toolbar">
        <div class="oh-toolbar__title">
            <h2 class="oh-title">
                {{ $onlyMine ? 'Mój czas pracy' : 'Czas pracy' }}
                <span class="oh-title__mode">— {{ $isWeekView ? 'widok tygodnia' : 'widok miesiąca' }}</span>
            </h2>
            <p class="oh-subtitle mb-0">
                @if($isWeekView)
                    {{ $weekStartDate->format('d.m.Y') }} – {{ $weekEndDate->format('d.m.Y') }}
                @else
                    {{ $monthStart->format('d.m.Y') }} – {{ $monthEnd->format('d.m.Y') }}
                @endif
                <span class="oh-subtitle__sep">·</span>
                wpisz od–do, godziny policzą się same
            </p>
        </div>

        <div class="oh-toolbar__nav">
            <button type="button" class="btn btn-sm btn-outline-secondary oh-nav-btn" wire:click="previousPeriod">
                <i class="bi bi-chevron-left"></i> Poprzedni
            </button>
            <div class="oh-month-label">
                @if($isWeekView)
                    {{ $weekStartDate->format('d.m') }} – {{ $weekEndDate->format('d.m.Y') }}
                @else
                    {{ $monthStart->locale('pl')->translatedFormat('F Y') }}
                @endif
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary oh-nav-btn" wire:click="nextPeriod">
                Następny <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <div class="oh-toolbar__actions">
            <div class="btn-group" role="group" aria-label="Widok">
                <button
                    type="button"
                    @class(['btn', 'btn-sm', $isWeekView ? 'btn-primary' : 'btn-outline-secondary'])
                    wire:click="setViewMode('week')"
                >Tydzień</button>
                <button
                    type="button"
                    @class(['btn', 'btn-sm', ! $isWeekView ? 'btn-primary' : 'btn-outline-secondary'])
                    wire:click="setViewMode('month')"
                >Miesiąc</button>
            </div>
            @unless($onlyMine)
                <a href="{{ route('office-profiles.index') }}" class="btn btn-sm btn-outline-secondary">Profile</a>
            @endunless
            <button
                type="button"
                class="btn btn-sm btn-primary"
                wire:click="save"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">Zapisz</span>
                <span wire:loading wire:target="save">Zapisuję…</span>
            </button>
        </div>
    </div>

    @if($flash)
        <x-ui.alert variant="success" class="mb-3">{{ $flash }}</x-ui.alert>
    @endif
    @if($errors->has('cells'))
        <x-ui.alert variant="danger" class="mb-3">{{ $errors->first('cells') }}</x-ui.alert>
    @elseif($errors->any())
        <x-ui.alert variant="danger" class="mb-3">
            Popraw podświetlone komórki ({{ $errors->count() }} {{ $errors->count() === 1 ? 'błąd' : 'błędy' }}).
        </x-ui.alert>
    @endif

    @if($profiles->isEmpty())
        <x-ui.card>
            <p class="mb-0 text-muted">
                @if($onlyMine)
                    Nie masz aktywnego profilu biurowego.
                @else
                    Brak aktywnych profili biurowych.
                    <a href="{{ route('office-profiles.index') }}">Włącz profil w Biuro</a>
                @endif
            </p>
        </x-ui.card>
    @else
        <div class="oh-kpis">
            <div class="oh-kpi">
                <div class="oh-kpi__label">Godziny w miesiącu</div>
                <div class="oh-kpi__value font-mono">{{ number_format($kpis['total_hours'], 1, ',', ' ') }} <span>h</span></div>
            </div>
            <div class="oh-kpi">
                <div class="oh-kpi__label">Średnio / dzień</div>
                <div class="oh-kpi__value font-mono">{{ number_format($kpis['avg_day'], 1, ',', ' ') }} <span>h</span></div>
            </div>
            <div class="oh-kpi">
                <div class="oh-kpi__label">Praca zdalna</div>
                <div class="oh-kpi__value font-mono">{{ $kpis['remote_pct'] }}<span>%</span></div>
            </div>
            <div class="oh-kpi">
                <div class="oh-kpi__label">Dni urlopu</div>
                <div class="oh-kpi__value font-mono">{{ $kpis['leave_days'] }} <span>dni</span></div>
            </div>
        </div>

        <div class="oh-legend">
            <div class="oh-legend__items">
                <span><x-ui.badge variant="success">B</x-ui.badge> biuro</span>
                <span><x-ui.badge variant="info">Z</x-ui.badge> zdalnie</span>
                <span><x-ui.badge variant="warning">±</x-ui.badge> vs norma dnia (8 h × część etatu)</span>
                <span><x-ui.badge variant="accent">U</x-ui.badge> urlop</span>
                <span class="text-muted small">kliknij badge, żeby przełączyć B → Z → U</span>
            </div>
            <div class="oh-legend__today text-muted small">
                Dziś: {{ $today->locale('pl')->isoFormat('dddd D.MM') }}
            </div>
        </div>

        <div @class(['oh-table-wrap', 'oh-table-wrap--week' => $isWeekView])>
            <table @class(['table', 'oh-table', 'oh-table--week' => $isWeekView])>
                <thead>
                    <tr>
                        <th class="oh-sticky oh-col-person">Osoba / suma</th>
                        @foreach($days as $day)
                            @php
                                $isToday = $day->isSameDay($today);
                                $isWeekend = $day->isWeekend();
                            @endphp
                            <th @class([
                                'oh-col-day',
                                'oh-weekend' => $isWeekend,
                                'oh-today' => $isToday,
                            ])>
                                <div class="oh-col-day__pill">
                                    <span class="oh-day-num">
                                        {{ $day->format('d') }}
                                        <span class="oh-day-wd">{{ $day->locale('pl')->isoFormat('dd') }}</span>
                                    </span>
                                    @if($isToday)
                                        <span class="oh-day-today-tag">dziś</span>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($profiles as $profile)
                        @php
                            $meta = $rowMeta[$profile->id] ?? [
                                'total' => 0, 'norm' => 0, 'diff' => 0, 'progress' => 0,
                                'initials' => '?', 'week_has_entries' => true,
                            ];
                            $diff = (float) $meta['diff'];
                            $progressPct = min(100, max(0, (int) $meta['progress']));
                            $personName = $profile->user?->name ?? '—';
                            $weekCollapsed = $isWeekView && ! ($meta['week_has_entries'] ?? true);
                        @endphp
                        <tr
                            @class(['oh-row', 'oh-row--collapsed' => $weekCollapsed])
                            wire:key="oh-row-{{ $profile->id }}-{{ $month }}"
                            @if($weekCollapsed)
                                x-data="{ expanded: false }"
                                x-bind:class="{ 'is-expanded': expanded }"
                            @endif
                        >
                            <td class="oh-sticky oh-col-person">
                                <div @class(['oh-person-card' => $isWeekView])>
                                    <div class="oh-person">
                                        <div class="oh-avatar">{{ $meta['initials'] }}</div>
                                        <div class="oh-person__text">
                                            <div class="oh-person__name" title="{{ $personName }}">{{ $personName }}</div>
                                            <div class="oh-person__meta">{{ $profile->contract_type?->label() }}</div>
                                        </div>
                                    </div>
                                    @if($weekCollapsed)
                                        <button
                                            type="button"
                                            class="oh-row-collapse-btn"
                                            @click="expanded = !expanded"
                                            x-text="expanded ? 'Zwiń tydzień' : 'Brak wpisów w tym tygodniu — rozwiń'"
                                        ></button>
                                    @endif
                                    <div class="oh-sum oh-sum--inline" @if($weekCollapsed) x-show="expanded" x-cloak @endif>
                                        <div class="oh-sum__total">
                                            {{ number_format($meta['total'], 1, ',', ' ') }}<span class="oh-sum__unit">h</span>
                                            <span class="oh-sum__norm">z {{ number_format($meta['norm'], 0, ',', ' ') }} h</span>
                                        </div>
                                        <div
                                            class="oh-sum__bar"
                                            title="Postęp vs norma miesiąca ({{ number_format($meta['norm'], 0, ',', ' ') }} h)"
                                        >
                                            <span class="oh-sum__bar-fill" style="width: {{ $progressPct }}%"></span>
                                            <i class="oh-sum__bar-norm" aria-hidden="true"></i>
                                        </div>
                                        <div
                                            @class([
                                                'oh-sum__diff',
                                                'oh-sum__diff--pos' => $diff >= 0,
                                                'oh-sum__diff--neg' => $diff < 0,
                                            ])
                                            title="{{ ($diff >= 0 ? '+' : '').number_format($diff, 1, ',', '') }} h vs norma do dziś"
                                        >
                                            {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 1, ',', '') }} h vs norma
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary oh-copy-btn"
                                            wire:click="copyFromFirstDay({{ $profile->id }})"
                                            title="Skopiuj godziny z pierwszego uzupełnionego dnia na wszystkie dni robocze"
                                        >
                                            <i class="bi bi-copy"></i>
                                            <span>Kopiuj z pierwszego dnia</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            @foreach($days as $day)
                                @php
                                    $key = $profile->id.'_'.$day->format('Ymd');
                                    $cell = $cells[$key] ?? ['starts_at' => '', 'ends_at' => '', 'location' => 'office'];
                                    $locEnum = OfficeWorkLocation::tryFrom($cell['location'] ?? '') ?? OfficeWorkLocation::Office;
                                    $hours = $cellHours[$key] ?? 0;
                                    $isLeave = $locEnum === OfficeWorkLocation::Leave;
                                    $isToday = $day->isSameDay($today);
                                    $isWeekend = $day->isWeekend();
                                    $startErr = $errors->first('cells.'.$key.'.starts_at');
                                    $endErr = $errors->first('cells.'.$key.'.ends_at');
                                    $hasCellErr = $startErr || $endErr;
                                    $starts = $cell['starts_at'] ?? '';
                                    $ends = $cell['ends_at'] ?? '';
                                    $hasRange = ! $isLeave && $starts !== '' && $ends !== '' && $hours > 0;
                                    $needsHours = ! $hasRange && ! $isLeave && (
                                        $locEnum !== OfficeWorkLocation::Office
                                        || $starts !== ''
                                        || $ends !== ''
                                    );
                                    $isEmptyDay = ! $hasRange && ! $isLeave && ! $needsHours;
                                    $locIcon = match ($locEnum) {
                                        OfficeWorkLocation::Remote => 'bi-wifi',
                                        OfficeWorkLocation::Leave => 'bi-calendar-x',
                                        default => 'bi-geo-alt-fill',
                                    };
                                    $dayExpected = \App\Services\OfficeHoursService::STANDARD_DAY_HOURS * (float) $profile->fte;
                                @endphp
                                <td
                                    id="oh-cell-{{ $key }}"
                                    @class([
                                        'oh-cell',
                                        'oh-cell--tile' => $isWeekView,
                                        'weekend-cell' => $isWeekend,
                                        'oh-weekend' => $isWeekend,
                                        'oh-today' => $isToday,
                                        'oh-cell--leave' => $isLeave && ! $isWeekView,
                                        'oh-cell--error' => $hasCellErr,
                                    ])
                                    @if($hasCellErr) title="{{ trim(($startErr ?: '').' '.($endErr ?: '')) }}" @endif
                                >
                                    @if($isWeekView)
                                        <div
                                            @class([
                                                'oh-day-card',
                                                'oh-day-card--'.$locEnum->value,
                                                'oh-day-card--weekend' => $isWeekend,
                                                'oh-day-card--today' => $isToday,
                                                'oh-day-card--error' => $hasCellErr,
                                                'oh-day-card--empty' => $isEmptyDay,
                                                'oh-day-card--needs-hours' => $needsHours,
                                                'is-open' => $isEmptyDay && $hasCellErr,
                                            ])
                                            @if($weekCollapsed)
                                                x-show="expanded"
                                                x-cloak
                                            @endif
                                            @if($isEmptyDay)
                                                @click="$el.classList.add('is-open')"
                                                @focusin="$el.classList.add('is-open')"
                                            @endif
                                        >
                                            <div class="oh-day-card__body">
                                                <div class="oh-week-main">
                                                    <div class="oh-week-clock-col">
                                                        @include('livewire.partials.office-hours-week-clock', [
                                                            'starts' => $starts,
                                                            'ends' => $ends,
                                                            'hours' => $hours,
                                                            'locEnum' => $locEnum,
                                                            'isLeave' => $isLeave,
                                                            'expectedHours' => $dayExpected,
                                                        ])
                                                        <div class="oh-week-hours">
                                                            @if($isLeave)
                                                                <div class="oh-week-total">{{ number_format($hours, 1, ',', '') }}<span class="oh-week-total__unit">h</span></div>
                                                                <div class="oh-week-range">urlop</div>
                                                            @elseif($hasRange)
                                                                <div class="oh-week-total">{{ number_format($hours, 1, ',', '') }}<span class="oh-week-total__unit">h</span></div>
                                                            @elseif($needsHours)
                                                                <div class="oh-week-empty oh-week-empty--needs">
                                                                    <span class="oh-week-empty__dot" aria-hidden="true"></span>
                                                                    brak godzin
                                                                </div>
                                                            @else
                                                                <div class="oh-week-empty">brak wpisu</div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="oh-week-side">
                                                        @if($isLeave)
                                                            <div class="oh-week-times oh-week-times--leave" aria-hidden="true">
                                                                <div class="oh-week-time-row">
                                                                    <span class="oh-week-time-label">OD</span>
                                                                    <div class="oh-time oh-time--placeholder">—</div>
                                                                </div>
                                                                <div class="oh-week-time-row">
                                                                    <span class="oh-week-time-label">DO</span>
                                                                    <div class="oh-time oh-time--placeholder">—</div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="oh-week-times">
                                                                <label class="oh-week-time-row">
                                                                    <span class="oh-week-time-label">OD</span>
                                                                    <input
                                                                        type="text"
                                                                        inputmode="numeric"
                                                                        placeholder="—"
                                                                        maxlength="5"
                                                                        autocomplete="off"
                                                                        @class(['oh-time', 'time-input', 'oh-time--error' => (bool) $startErr])
                                                                        wire:model.blur="cells.{{ $key }}.starts_at"
                                                                        x-on:blur.capture="onTimeBlur($event)"
                                                                        title="{{ $startErr ?: 'Od' }}"
                                                                        aria-label="Od"
                                                                    >
                                                                </label>
                                                                <label class="oh-week-time-row">
                                                                    <span class="oh-week-time-label">DO</span>
                                                                    <input
                                                                        type="text"
                                                                        inputmode="numeric"
                                                                        placeholder="—"
                                                                        maxlength="5"
                                                                        autocomplete="off"
                                                                        @class(['oh-time', 'time-input', 'oh-time--error' => (bool) $endErr])
                                                                        wire:model.blur="cells.{{ $key }}.ends_at"
                                                                        x-on:blur.capture="onTimeBlur($event)"
                                                                        title="{{ $endErr ?: 'Do' }}"
                                                                        aria-label="Do"
                                                                    >
                                                                </label>
                                                            </div>
                                                        @endif

                                                        @if($hasCellErr)
                                                            <div class="oh-cell__err">{{ $startErr ?: $endErr }}</div>
                                                        @endif

                                                        <div class="oh-week-actions">
                                                            <button
                                                                type="button"
                                                                class="oh-loc-chip oh-loc-chip--{{ $locEnum->value }}"
                                                                wire:click="toggleLocation('{{ $key }}')"
                                                                title="{{ $locEnum->label() }} — kliknij, by przełączyć"
                                                            >
                                                                <i class="bi {{ $locIcon }}"></i>
                                                                <span>{{ $locEnum->label() }}</span>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="oh-day-copy"
                                                                wire:click="copyPreviousDay('{{ $key }}')"
                                                                title="Kopiuj z poprzedniego dnia roboczego"
                                                            >
                                                                <i class="bi bi-copy"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if($weekCollapsed)
                                            <button
                                                type="button"
                                                class="oh-row-collapsed-cell"
                                                x-show="!expanded"
                                                x-cloak
                                                @click="expanded = true"
                                                title="Brak wpisów w tym tygodniu — kliknij, by rozwinąć"
                                                aria-label="Rozwiń tydzień"
                                            ></button>
                                        @endif
                                    @elseif($isLeave)
                                        <div class="oh-leave">
                                            <span class="oh-leave__label">Urlop</span>
                                            <div class="oh-cell__foot">
                                                <span class="oh-cell__hours font-mono">{{ number_format($hours, 1, ',', '') }}</span>
                                                <button
                                                    type="button"
                                                    class="oh-loc-btn"
                                                    wire:click="toggleLocation('{{ $key }}')"
                                                    title="{{ $locEnum->label() }}"
                                                >
                                                    <x-ui.badge :variant="$locEnum->badgeVariant()">{{ $locEnum->shortLabel() }}</x-ui.badge>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="08:00"
                                            maxlength="5"
                                            autocomplete="off"
                                            @class(['oh-time', 'time-input', 'font-mono', 'oh-time--error' => (bool) $startErr])
                                            wire:model.blur="cells.{{ $key }}.starts_at"
                                            x-on:blur.capture="onTimeBlur($event)"
                                            title="{{ $startErr ?: 'Od — np. 9, 900, 09:00' }}"
                                            aria-label="Od"
                                        >
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="16:00"
                                            maxlength="5"
                                            autocomplete="off"
                                            @class(['oh-time', 'time-input', 'font-mono', 'oh-time--error' => (bool) $endErr])
                                            wire:model.blur="cells.{{ $key }}.ends_at"
                                            x-on:blur.capture="onTimeBlur($event)"
                                            title="{{ $endErr ?: 'Do — np. 16, 1600, 16:00' }}"
                                            aria-label="Do"
                                        >
                                        @if($hasCellErr)
                                            <div class="oh-cell__err">{{ $startErr ?: $endErr }}</div>
                                        @endif
                                        <div class="oh-cell__foot">
                                            <span class="oh-cell__hours font-mono">
                                                {{ $hours > 0 ? number_format($hours, 1, ',', '') : '—' }}
                                            </span>
                                            <button
                                                type="button"
                                                class="oh-loc-btn"
                                                wire:click="toggleLocation('{{ $key }}')"
                                                title="{{ $locEnum->label() }}"
                                            >
                                                <x-ui.badge :variant="$locEnum->badgeVariant()">{{ $locEnum->shortLabel() }}</x-ui.badge>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="oh-sticky oh-col-person oh-foot-label">
                            <div class="oh-foot-label__title">Σ dzień</div>
                            <div class="oh-foot-grand">
                                {{ number_format($kpis['total_hours'], 1, ',', ' ') }}<span class="oh-foot-grand__unit">h</span>
                            </div>
                        </td>
                        @foreach($days as $day)
                            @php $dt = $dayTotals[$day->format('Y-m-d')] ?? 0; @endphp
                            <td @class([
                                'oh-foot-day',
                                'weekend-cell' => $day->isWeekend(),
                                'oh-weekend' => $day->isWeekend(),
                                'oh-today' => $day->isSameDay($today),
                            ])>
                                @if($dt > 0)
                                    <span class="oh-foot-day__val">{{ number_format($dt, 1, ',', '') }}<span class="oh-foot-day__unit">h</span></span>
                                @else
                                    <span class="oh-foot-day__empty">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <style>
        /* —— layout —— */
        .oh-root { --oh-cell-w: 4.6rem; }
        .oh-root:has(.oh-table--week) { --oh-cell-w: 0; }

        .oh-toolbar {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 0.75rem 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        .oh-toolbar__actions { display: flex; justify-content: flex-end; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
        .oh-toolbar__nav { display: flex; align-items: center; gap: 0.65rem; }
        .oh-title { font-size: 1.35rem; font-weight: 600; margin: 0; }
        .oh-title__mode { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); }
        .oh-subtitle { color: var(--text-muted); font-size: 0.82rem; }
        .oh-subtitle__sep { margin: 0 0.25rem; opacity: 0.5; }
        .oh-month-label { font-weight: 600; text-transform: lowercase; min-width: 9.5rem; text-align: center; }
        .oh-nav-btn { white-space: nowrap; }

        .oh-kpis {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.65rem;
            margin-bottom: 0.85rem;
        }
        .oh-kpi {
            background: var(--bg-card, rgba(13, 18, 30, 0.52));
            border: 1px solid var(--glass-border, rgba(255,255,255,0.1));
            border-radius: 0.65rem;
            padding: 0.75rem 0.9rem;
        }
        .oh-kpi__label {
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: #94a3b8; margin-bottom: 0.25rem;
        }
        .oh-kpi__value { font-size: 1.35rem; font-weight: 600; line-height: 1.1; }
        .oh-kpi__value span { font-size: 0.85rem; color: #94a3b8; font-weight: 500; }

        .oh-legend {
            display: flex; justify-content: space-between; align-items: center;
            gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.65rem; font-size: 0.82rem;
        }
        .oh-legend__items { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; align-items: center; }

        /* —— table shell —— */
        .oh-table-wrap {
            max-height: calc(100vh - 280px);
            overflow: auto;
            border: 1px solid var(--glass-border, rgba(255,255,255,0.1));
            border-radius: 0.65rem;
            background: var(--bg-card, rgba(13, 18, 30, 0.4));
        }
        .oh-table {
            width: max-content; min-width: 100%;
            border-collapse: separate; border-spacing: 0; margin: 0;
        }
        .oh-table th, .oh-table td {
            border-bottom: 1px solid var(--glass-border, rgba(255,255,255,0.08));
            border-right: 1px solid var(--glass-border, rgba(255,255,255,0.05));
            padding: 0.35rem; vertical-align: middle;
        }
        .oh-table thead th {
            position: sticky; top: 0; z-index: 3; background: #0b1020;
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; color: #94a3b8;
        }
        .oh-sticky {
            position: sticky; left: 0; z-index: 2; background: #0b1020;
            min-width: 13.5rem; max-width: 16rem; vertical-align: top !important;
        }
        thead .oh-sticky { z-index: 4; vertical-align: middle !important; }
        .oh-col-day { min-width: var(--oh-cell-w); width: var(--oh-cell-w); text-align: center; }
        .oh-day-num { color: var(--text-main); font-weight: 600; font-size: 0.8rem; text-transform: none; letter-spacing: 0; }
        .oh-day-wd { font-size: 0.75rem; opacity: 0.8; }

        .oh-table:not(.oh-table--week) thead th.oh-weekend {
            background: rgba(245, 158, 11, 0.15) !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
            color: #fbbf24;
        }
        .oh-table:not(.oh-table--week) tbody td.oh-weekend,
        .oh-table:not(.oh-table--week) tbody td.weekend-cell,
        .oh-table:not(.oh-table--week) tfoot td.oh-weekend {
            background: rgba(245, 158, 11, 0.1) !important;
        }
        .oh-table:not(.oh-table--week) tbody td.oh-weekend .oh-time,
        .oh-table:not(.oh-table--week) tbody td.weekend-cell .oh-time {
            background: rgba(245, 158, 11, 0.15) !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        .oh-table:not(.oh-table--week) .oh-today { box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.55); }

        /* —— week table —— */
        .oh-table-wrap--week {
            max-height: none; background: transparent; border: none;
            overflow-x: hidden; overflow-y: visible; padding: 0.35rem 0 0.25rem;
        }
        .oh-table--week {
            width: 100% !important; min-width: 0 !important; max-width: 100%;
            table-layout: fixed; border-collapse: separate; border-spacing: 0.3rem 0.45rem;
        }
        .oh-table--week thead th {
            background: #070a13 !important; border: none !important; box-shadow: none;
            padding: 0.45rem 0.2rem 0.35rem; vertical-align: middle; min-height: 2.25rem;
        }
        .oh-table--week .oh-col-day { min-width: 0 !important; width: auto; }
        .oh-table--week .oh-col-day__pill {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            flex-wrap: wrap; min-height: 2.25rem; padding: 0.35rem 0.55rem;
            border-radius: 0.5rem; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12); box-sizing: border-box;
        }
        .oh-table--week .oh-day-num {
            display: inline-flex; align-items: baseline; gap: 0.3rem;
            font-size: 0.95rem; font-weight: 700; color: var(--text-main);
            text-transform: none; letter-spacing: 0; line-height: 1.2;
        }
        .oh-table--week .oh-day-wd {
            font-size: 0.75rem; font-weight: 650; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.85;
        }
        .oh-table--week .oh-day-today-tag {
            margin: 0; font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em; padding: 0.15rem 0.4rem; border-radius: 999px;
            color: #c7d2fe; background: rgba(99, 102, 241, 0.3);
            border: 1px solid rgba(129, 140, 248, 0.45);
        }
        .oh-table--week .oh-col-day.oh-weekend .oh-col-day__pill {
            background: rgba(180, 120, 60, 0.18); border-color: rgba(245, 158, 11, 0.35); color: #fbbf24;
        }
        .oh-table--week .oh-col-day.oh-weekend .oh-day-num { color: #fbbf24; }
        .oh-table--week .oh-col-day.oh-today .oh-col-day__pill {
            background: rgba(99, 102, 241, 0.22); border-color: rgba(129, 140, 248, 0.5);
        }
        .oh-table--week tbody td {
            border: none !important; background: transparent !important;
            padding: 0; vertical-align: top !important;
        }
        .oh-table--week tbody td.oh-today {
            background: rgba(59, 130, 246, 0.08) !important;
            box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.28);
            border-radius: 0.65rem;
        }
        .oh-table--week tbody td.oh-weekend {
            background: rgba(146, 98, 48, 0.1) !important; border-radius: 0.65rem;
        }
        .oh-table--week .oh-cell--tile { padding: 0.15rem; vertical-align: top !important; box-sizing: border-box; }
        .oh-table--week .oh-sticky.oh-col-person,
        .oh-table--week thead .oh-sticky,
        .oh-table--week tfoot .oh-sticky {
            background: #070a13 !important; box-shadow: none; padding: 0; vertical-align: top;
            width: 15rem; min-width: 15rem !important; max-width: 15rem; box-sizing: border-box;
        }
        .oh-table--week thead .oh-sticky { padding: 0.45rem 0.35rem 0.35rem; vertical-align: middle; }
        .oh-table--week tfoot td { border: none !important; background: transparent !important; padding: 0.35rem 0.2rem; }
        .oh-table--week tfoot .oh-sticky { padding: 0.45rem 0.55rem; }
        .oh-table--week tfoot td.oh-today { background: rgba(59, 130, 246, 0.08) !important; border-radius: 0.55rem; }
        .oh-table--week tfoot td.oh-weekend { background: rgba(146, 98, 48, 0.1) !important; border-radius: 0.55rem; }
        .oh-table--week tfoot .oh-foot-day { text-align: center; }
        .oh-table--week .oh-foot-day__val {
            display: inline-flex; align-items: baseline; gap: 0.15rem; justify-content: center;
            min-width: 3.5rem; padding: 0.35rem 0.45rem; border-radius: 0.45rem;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            font-size: 1rem; font-weight: 700; color: #f8fafc; font-variant-numeric: tabular-nums;
        }
        .oh-table--week .oh-foot-day__unit { font-size: 0.72rem; font-weight: 600; color: #cbd5e1; }
        .oh-table--week .oh-foot-day__empty { color: #94a3b8; font-size: 0.85rem; }
        .oh-table--week .oh-person__name {
            font-size: 0.88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;
        }
        .oh-table--week .oh-person__meta { font-size: 0.75rem; color: #94a3b8; }
        .oh-table--week .oh-person__text { min-width: 0; overflow: hidden; }
        .oh-table--week .oh-copy-btn {
            display: flex !important; width: 100%; box-sizing: border-box;
            font-size: 0.75rem; padding: 0.35rem 0.55rem; white-space: nowrap; overflow: hidden;
        }
        .oh-table--week .oh-time {
            max-width: none; width: 100%; margin: 0; font-size: 0.85rem; padding: 0.32rem 0.4rem;
            border-radius: 0.35rem; box-sizing: border-box; min-height: 1.75rem; line-height: 1.15;
            text-align: left; font-family: 'Space Grotesk', sans-serif;
            font-variant-numeric: tabular-nums; font-feature-settings: "tnum" 1;
        }
        .oh-table--week .oh-time::placeholder { color: #94a3b8; opacity: 1; font-size: 0.75rem; font-weight: 500; }

        /* —— person / collapse —— */
        .oh-person { display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.55rem; min-width: 0; }
        .oh-avatar {
            width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center;
            font-size: 0.7rem; font-weight: 700; flex-shrink: 0;
            background: linear-gradient(135deg, rgba(59,130,246,0.35), rgba(168,85,247,0.35));
            border: 1px solid var(--glass-border);
        }
        .oh-person__name { font-weight: 600; font-size: 0.88rem; line-height: 1.2; }
        .oh-person__meta { font-size: 0.75rem; color: #94a3b8; }
        .oh-person-card {
            height: 100%; min-height: 11rem; padding: 0.7rem 0.75rem; border-radius: 0.6rem;
            background: linear-gradient(165deg, rgba(20, 28, 48, 0.92), rgba(12, 18, 32, 0.88));
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 1px 0 rgba(255,255,255,0.06) inset, 0 8px 20px rgba(0,0,0,0.26);
            box-sizing: border-box; overflow: hidden;
        }
        .oh-sum--inline { padding: 0 !important; min-width: 0; width: 100%; box-sizing: border-box; }
        .oh-sum__total { font-weight: 700; font-size: 0.95rem; }
        .oh-sum--inline .oh-sum__total {
            display: flex; align-items: baseline; gap: 0.2rem; flex-wrap: wrap; font-variant-numeric: tabular-nums;
        }
        .oh-sum__unit { font-size: 0.75rem; font-weight: 600; color: #cbd5e1; margin-right: 0.2rem; }
        .oh-sum--inline .oh-sum__norm { font-size: 0.75rem; font-weight: 500; color: #94a3b8; }
        .oh-sum__bar {
            position: relative; height: 6px; border-radius: 99px; background: rgba(255,255,255,0.12);
            overflow: visible; margin: 0.4rem 0 0.35rem;
        }
        .oh-sum__bar-fill {
            display: block; height: 100%; max-width: 100%; border-radius: inherit;
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }
        .oh-sum__bar-norm {
            position: absolute; top: -3px; bottom: -3px; left: 100%; width: 2px; margin-left: -2px;
            background: #e2e8f0; border-radius: 1px; box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.45);
        }
        .oh-sum__diff { font-size: 0.75rem; font-weight: 650; white-space: nowrap; }
        .oh-sum__diff--pos { color: #34d399; }
        .oh-sum__diff--neg { color: #fb923c; }
        .oh-copy-btn {
            margin-top: 0.45rem; width: 100%; box-sizing: border-box; font-size: 0.75rem;
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            white-space: nowrap; padding: 0.35rem 0.55rem;
        }
        .oh-row-collapse-btn {
            width: 100%; box-sizing: border-box; margin: 0.15rem 0 0.35rem; padding: 0.4rem 0.55rem;
            border-radius: 0.45rem; border: 1px dashed rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.03); color: #cbd5e1; font-size: 0.75rem; font-weight: 600;
            text-align: left; cursor: pointer;
        }
        .oh-row-collapse-btn:hover { border-color: rgba(255,255,255,0.3); color: #f1f5f9; }
        .oh-row--collapsed:not(.is-expanded) .oh-person-card { min-height: 3.5rem; padding: 0.55rem 0.7rem; }
        .oh-row--collapsed:not(.is-expanded) td.oh-cell--tile { height: 3.5rem; }
        .oh-row-collapsed-cell {
            display: block; width: 100%; height: 3.5rem; border-radius: 0.55rem;
            background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.12);
            cursor: pointer; padding: 0; box-sizing: border-box;
        }
        .oh-row-collapsed-cell:hover {
            background: rgba(59, 130, 246, 0.08); border-color: rgba(129, 140, 248, 0.35);
        }

        /* —— day card (week) —— */
        .oh-day-card {
            display: flex; flex-direction: column; height: 100%; min-height: 11rem; min-width: 0;
            border-radius: 0.6rem; overflow: hidden;
            background: linear-gradient(165deg, rgba(22, 30, 50, 0.95), rgba(13, 18, 30, 0.92));
            border: 1px solid rgba(255,255,255,0.11);
            box-shadow: 0 1px 0 rgba(255,255,255,0.05) inset, 0 6px 16px rgba(0,0,0,0.24);
            transition: border-color 0.15s ease, box-shadow 0.15s ease; box-sizing: border-box;
        }
        .oh-day-card:hover {
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 1px 0 rgba(255,255,255,0.07) inset, 0 8px 18px rgba(0,0,0,0.3);
        }
        .oh-day-card__body {
            display: flex; flex-direction: column; flex: 1;
            padding: 0.45rem 0.4rem 0.5rem; gap: 0.3rem; min-height: 0;
        }
        .oh-day-card--weekend {
            background: linear-gradient(165deg, rgba(48, 34, 22, 0.92), rgba(28, 20, 14, 0.9));
            border-color: rgba(180, 120, 60, 0.35);
        }
        .oh-day-card--today {
            border-color: rgba(129, 140, 248, 0.55);
            box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.25), 0 1px 0 rgba(255,255,255,0.05) inset, 0 8px 22px rgba(0,0,0,0.26);
            background: linear-gradient(165deg, rgba(28, 36, 64, 0.96), rgba(16, 22, 42, 0.94));
        }
        .oh-day-card--office { border-bottom: 3px solid rgba(34, 197, 94, 0.55); }
        .oh-day-card--remote { border-bottom: 3px solid rgba(56, 189, 248, 0.6); }
        .oh-day-card--leave { border-bottom: 3px solid rgba(168, 85, 247, 0.6); }
        .oh-day-card--empty { border-bottom-color: rgba(255,255,255,0.1); cursor: pointer; }
        .oh-day-card--empty:not(.is-open):not(:hover):not(:focus-within) { opacity: 0.55; filter: saturate(0.6); }
        .oh-day-card--empty:not(.is-open):not(:hover):not(:focus-within) .oh-week-main {
            grid-template-columns: 1fr; justify-items: center;
        }
        .oh-day-card--empty:not(.is-open):not(:hover):not(:focus-within) .oh-week-side { display: none; }
        .oh-day-card--empty.is-open,
        .oh-day-card--empty:hover,
        .oh-day-card--empty:focus-within { opacity: 1; filter: none; cursor: default; }
        .oh-day-card--needs-hours {
            border-color: rgba(245, 158, 11, 0.55);
            box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.22);
            background: linear-gradient(165deg, rgba(48, 40, 22, 0.95), rgba(28, 24, 14, 0.92));
        }
        .oh-day-card--error {
            border-color: rgba(248, 113, 113, 0.65) !important;
            box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.35), 0 8px 22px rgba(0,0,0,0.26);
        }

        .oh-week-main {
            display: grid; grid-template-columns: 4.5rem 1fr; gap: 0.4rem;
            align-items: center; flex: 1; min-width: 0;
        }
        .oh-week-clock-col {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.28rem; min-width: 0;
        }
        .oh-week-hours {
            display: flex; flex-direction: column; align-items: center; gap: 0.12rem;
            text-align: center; line-height: 1.05;
        }
        .oh-week-side {
            display: flex; flex-direction: column; gap: 0.3rem; min-width: 0; justify-content: center;
        }
        .oh-week-times { display: grid; gap: 0.28rem; min-height: 0; }
        .oh-week-times--leave { opacity: 0.55; }
        .oh-week-time-row {
            display: grid; grid-template-columns: 1.6rem 1fr; gap: 0.3rem;
            align-items: center; margin: 0; min-width: 0;
        }
        .oh-week-time-label {
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em;
            text-transform: uppercase; color: #cbd5e1; line-height: 1;
        }
        .oh-week-total {
            display: inline-flex; align-items: baseline; gap: 0.15rem;
            font-size: 1.2rem; font-weight: 750; line-height: 1.05; color: #f8fafc;
            letter-spacing: -0.02em; font-variant-numeric: tabular-nums;
        }
        .oh-week-total__unit { font-size: 0.75rem; font-weight: 650; color: #e2e8f0; }
        .oh-week-range { font-size: 0.75rem; color: #94a3b8; text-transform: lowercase; }
        .oh-week-empty { font-size: 0.75rem; font-weight: 600; color: #94a3b8; }
        .oh-week-empty--needs {
            display: inline-flex; align-items: center; gap: 0.3rem;
            color: #fbbf24; font-size: 0.75rem; font-weight: 650;
        }
        .oh-week-empty__dot {
            width: 0.45rem; height: 0.45rem; border-radius: 999px; background: #fbbf24;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.22); flex-shrink: 0;
        }
        .oh-week-delta {
            font-size: 0.75rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 999px;
            white-space: nowrap; font-variant-numeric: tabular-nums;
        }
        .oh-week-delta--pos {
            color: #fbbf24; background: rgba(245, 158, 11, 0.16); border: 1px solid rgba(245, 158, 11, 0.4);
        }
        .oh-week-delta--neg {
            color: #7dd3fc; background: rgba(56, 189, 248, 0.14); border: 1px solid rgba(56, 189, 248, 0.35);
        }
        .oh-week-actions { display: flex; align-items: center; gap: 0.2rem; margin-top: 0.05rem; }
        .oh-loc-chip {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.2rem;
            border-radius: 999px; padding: 0.22rem 0.3rem; font-size: 0.75rem; font-weight: 600;
            border: 1px solid transparent; background: transparent; cursor: pointer; line-height: 1; min-width: 0;
        }
        .oh-loc-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .oh-loc-chip--office { color: #86efac; border-color: rgba(34, 197, 94, 0.45); background: rgba(34, 197, 94, 0.12); }
        .oh-loc-chip--remote { color: #7dd3fc; border-color: rgba(56, 189, 248, 0.5); background: rgba(56, 189, 248, 0.14); }
        .oh-loc-chip--leave { color: #d8b4fe; border-color: rgba(168, 85, 247, 0.5); background: rgba(168, 85, 247, 0.14); }
        .oh-day-copy {
            width: 1.45rem; height: 1.45rem; border-radius: 0.35rem;
            border: 1px solid var(--glass-border); background: rgba(255,255,255,0.04);
            color: #94a3b8; display: grid; place-items: center; cursor: pointer; flex-shrink: 0; font-size: 0.7rem;
        }
        .oh-day-copy:hover { color: var(--text-main); border-color: rgba(255,255,255,0.25); }

        /* —— clock —— */
        .oh-clock { display: grid; place-items: center; margin: 0; flex-shrink: 0; width: 4.5rem; height: 4.5rem; }
        .oh-clock__svg { width: 4.5rem !important; height: 4.5rem !important; max-width: none !important; display: block; }
        .oh-clock__face { fill: rgba(255,255,255,0.08); stroke: rgba(255,255,255,0.32); stroke-width: 1.8; }
        .oh-clock__track { stroke: rgba(255,255,255,0.14); }
        .oh-clock--empty .oh-clock__face {
            stroke-dasharray: 3 3; stroke: rgba(255,255,255,0.24); fill: rgba(255,255,255,0.03);
        }
        .oh-clock--empty .oh-clock__track { stroke: rgba(255,255,255,0.12); stroke-dasharray: 4 4; }
        .oh-clock__tick { stroke: rgba(255,255,255,0.35); stroke-width: 1.1; }
        .oh-clock__tick--major { stroke: rgba(255,255,255,0.55); stroke-width: 1.5; }
        .oh-clock__num {
            fill: rgba(255,255,255,0.7); font-size: 8px; font-weight: 700;
            font-family: 'Space Grotesk', sans-serif;
        }
        .oh-clock__arc { stroke: #4ade80; }
        .oh-clock--remote .oh-clock__arc { stroke: #38bdf8; }
        .oh-clock--leave .oh-clock__arc { stroke: #c084fc; }
        .oh-clock--overnorm .oh-clock__arc { stroke: #fbbf24; }

        /* —— month cells —— */
        .oh-cell { text-align: center; }
        .oh-time {
            width: 100%; max-width: 4.2rem; margin: 0 auto 0.2rem; display: block;
            padding: 0.12rem 0.2rem; font-size: 0.75rem; text-align: center; border-radius: 0.35rem;
            border: 1px solid var(--glass-border); background: rgba(255,255,255,0.04); color: var(--text-main);
        }
        .oh-time:focus {
            outline: none; border-color: rgba(59, 130, 246, 0.65); background: rgba(59, 130, 246, 0.08);
        }
        .oh-time--placeholder {
            display: grid; place-items: center; opacity: 0.55;
            border: 1px dashed rgba(255,255,255,0.18); background: rgba(255,255,255,0.02);
            color: #94a3b8; pointer-events: none; user-select: none; font-variant-numeric: tabular-nums;
        }
        .oh-time--error {
            border-color: rgba(239, 68, 68, 0.85) !important;
            background: rgba(239, 68, 68, 0.18) !important; color: #fecaca;
        }
        .oh-cell--error {
            background: rgba(239, 68, 68, 0.14) !important;
            box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.65);
        }
        .oh-cell__err {
            font-size: 0.75rem; line-height: 1.15; color: #fca5a5;
            margin: 0.15rem 0 0.1rem; max-width: 100%;
        }
        .oh-cell__foot {
            display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 0.15rem;
        }
        .oh-cell__hours { font-size: 0.75rem; color: #94a3b8; min-width: 1.5rem; }
        .oh-loc-btn { border: 0; background: transparent; padding: 0; line-height: 1; cursor: pointer; }
        .oh-loc-btn .badge { font-size: 0.62rem; padding: 0.15rem 0.35rem; }
        .oh-cell--leave { background: rgba(168, 85, 247, 0.1) !important; }
        .oh-leave {
            display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
            min-height: 3.4rem; justify-content: center;
        }
        .oh-leave__label {
            writing-mode: vertical-rl; transform: rotate(180deg); font-size: 0.75rem;
            letter-spacing: 0.12em; text-transform: uppercase; color: #c4b5fd; font-weight: 600;
        }

        /* —— footer —— */
        .oh-foot-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #cbd5e1; }
        .oh-foot-label__title {
            font-size: 0.75rem; font-weight: 650; text-transform: uppercase; letter-spacing: 0.04em; color: #cbd5e1;
        }
        .oh-foot-grand {
            margin-top: 0.25rem; font-size: 1rem; font-weight: 700; color: #f8fafc; font-variant-numeric: tabular-nums;
        }
        .oh-foot-grand__unit { margin-left: 0.15rem; font-size: 0.72rem; font-weight: 600; color: #cbd5e1; }
        .oh-foot-day { text-align: center; font-size: 0.75rem; font-weight: 600; background: rgba(0,0,0,0.18); }
        tfoot td { position: sticky; bottom: 0; z-index: 2; background: #0b1020; }

        @media (max-width: 991.98px) {
            .oh-toolbar { grid-template-columns: 1fr; }
            .oh-toolbar__nav, .oh-toolbar__actions { justify-content: flex-start; }
            .oh-kpis { grid-template-columns: repeat(2, 1fr); }
            .oh-table-wrap { max-height: none; }
        }
    </style>
</div>
