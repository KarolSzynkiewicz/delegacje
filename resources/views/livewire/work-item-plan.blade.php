@php
    $dayNames = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'];
    $hourPx = 52;
    $gridHours = count($hours);
    $gridHeight = $gridHours * $hourPx;
    $typeOptions = [
        'task' => ['label' => 'Zadanie', 'icon' => 'bi-check2-square'],
        'meeting' => ['label' => 'Spotkanie', 'icon' => 'bi-calendar-event'],
        'approval' => ['label' => 'Zatwierdzenie', 'icon' => 'bi-check2-circle'],
        'procedure' => ['label' => 'Procedura', 'icon' => 'bi-diagram-3'],
    ];
@endphp

<div class="wi-plan" id="wiPlan"
     x-data="{
        payload: null,
        hourPx: {{ $hourPx }},
        startHour: {{ $gridStartHour }},
        viewStartHour: {{ $viewStartHour }},
        snap: {{ $snap }},
        gridHours: {{ $gridHours }},
        resizing: null,
        drawing: null,
        detail: null,
        setPayload(event, kind, id, movable) {
            if (movable === false || this.resizing) {
                event?.preventDefault();
                return;
            }
            this.payload = { kind, id, copy: !!(event && (event.ctrlKey || event.metaKey)) };
            if (event && event.dataTransfer) {
                event.dataTransfer.setData('text/plain', kind + ':' + id);
                event.dataTransfer.effectAllowed = 'copyMove';
            }
        },
        minutesFromY(clientY, col) {
            const r = col.getBoundingClientRect();
            const y = Math.min(Math.max(0, clientY - r.top), r.height - 1);
            const raw = this.startHour * 60 + (y / r.height) * (this.gridHours * 60);
            return Math.round(raw / this.snap) * this.snap;
        },
        slotFromY(clientY, col) {
            const r = col.getBoundingClientRect();
            const y = Math.min(Math.max(0, clientY - r.top), r.height - 1);
            const raw = this.startHour * 60 + (y / r.height) * (this.gridHours * 60);
            const slot = Math.floor(raw / this.snap) * this.snap;
            return Math.max(0, Math.min(24 * 60 - this.snap, slot));
        },
        applyDrawPointer(clientY) {
            if (!this.drawing || this.drawing.allDay) return;
            const m = this.slotFromY(clientY, this.drawing.col);
            const a = this.drawing.anchor;
            this.drawing.start = Math.min(a, m);
            this.drawing.end = Math.max(a, m);
            if (this.drawing.end <= this.drawing.start) {
                this.drawing.end = this.drawing.start + this.snap;
            }
        },
        formatMinutes(total) {
            const clamped = Math.max(0, Math.min(24 * 60, total));
            const h = Math.floor(clamped / 60) % 24;
            const m = clamped % 60;
            const pad = (n) => String(n).padStart(2, '0');
            return pad(h) + ':' + pad(m);
        },
        dropOn(date, col, clientY, allDay, event) {
            if (!this.payload) return;
            const minutes = allDay ? 0 : this.minutesFromY(clientY, col);
            const copy = !!(this.payload.copy || event?.ctrlKey || event?.metaKey);
            $wire.dropOnCell(this.payload.kind, this.payload.id, date, minutes, !!allDay, copy);
            this.payload = null;
        },
        dropEffect(event) {
            if (!event?.dataTransfer) return;
            event.dataTransfer.dropEffect = (event.ctrlKey || event.metaKey) ? 'copy' : 'move';
        },
        paintRubber() {
            const d = this.drawing;
            document.querySelectorAll('#wiPlan .wi-plan__rubber').forEach((el) => {
                if (!d || d.allDay || el.dataset.date !== d.date) {
                    if (!el.classList.contains('is-held')) {
                        el.style.display = 'none';
                    }
                    return;
                }
                const start = Math.min(d.start, d.end);
                const end = Math.max(d.start, d.end);
                const total = this.gridHours * 60;
                const top = ((start - this.startHour * 60) / total) * 100;
                const height = Math.max(0.4, ((end - start) / total) * 100);
                el.style.display = 'block';
                el.style.top = top + '%';
                el.style.height = height + '%';
                const label = el.querySelector('.wi-plan__rubber-time');
                if (label) label.textContent = this.formatMinutes(start) + ' – ' + this.formatMinutes(end);
            });
        },
        beginResize(event, kind, id, date) {
            event.stopPropagation();
            event.preventDefault();
            const col = event.currentTarget.closest('[data-plan-col]');
            this.resizing = { kind, id, date, col };
            const move = (e) => {
                if (!this.resizing) return;
                this.resizing.end = this.minutesFromY(e.clientY, this.resizing.col);
            };
            const up = (e) => {
                if (this.resizing) {
                    const minutes = this.minutesFromY(e.clientY, this.resizing.col);
                    $wire.resizeOnCell(this.resizing.kind, this.resizing.id, this.resizing.date, minutes);
                }
                this.resizing = null;
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
            };
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        },
        beginDraw(event, date, allDay) {
            if (this.payload || this.resizing) return;
            if (event.target.closest('.wi-plan__event, .wi-plan__chip, .wi-plan__flag, .wi-plan__chip-off')) return;
            if (event.button !== 0) return;
            event.preventDefault();
            const col = event.currentTarget;
            const anchor = allDay ? 0 : this.slotFromY(event.clientY, col);
            this.drawing = {
                date,
                anchor,
                start: anchor,
                end: anchor + (allDay ? 0 : this.snap),
                allDay,
                col,
            };
            document.body.classList.add('wi-plan-drawing');
            this.paintRubber();
            const move = (e) => {
                this.applyDrawPointer(e.clientY);
                this.paintRubber();
            };
            const up = (e) => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
                document.body.classList.remove('wi-plan-drawing');
                if (!this.drawing) {
                    this.paintRubber();
                    return;
                }
                if (!this.drawing.allDay && e.clientY) {
                    this.applyDrawPointer(e.clientY);
                }
                const d = this.drawing;
                this.paintRubber();
                $wire.openComposer(d.date, d.start, d.end || d.start + this.snap, !!d.allDay);
            };
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        },
        openDetail(event, data) {
            event.stopPropagation();
            this.detail = data;
        },
        scrollToWorkHours() {
            const el = this.$refs.board;
            if (!el || el.dataset.scrolled === '1') return;
            el.scrollTop = Math.max(0, (this.viewStartHour - this.startHour) * this.hourPx);
            el.dataset.scrolled = '1';
        }
     }"
     x-init="
        scrollToWorkHours();
        $watch(() => $wire.composerOpen, (open) => {
            if (open) {
                queueMicrotask(() => this.paintRubber());
                return;
            }
            this.drawing = null;
            this.paintRubber();
        });
     "
     @dragend="payload = null"
     @click.outside="detail = null">

    <div class="wi-plan__toolbar">
        <label class="wi-plan__user">
            <i class="bi bi-person"></i>
            <select wire:model.live="userId" class="form-select form-select-sm">
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="wi-plan__week">
            <button type="button" class="wi-plan__nav" wire:click="previousWeek" title="Poprzedni tydzień">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="wi-plan__week-label font-mono">{{ $weekLabel }}</span>
            <button type="button" class="wi-plan__nav" wire:click="nextWeek" title="Następny tydzień">
                <i class="bi bi-chevron-right"></i>
            </button>
            <button type="button" class="wi-plan__today" wire:click="goToToday">Dziś</button>
        </div>
    </div>

    <div class="wi-plan__body">
        <aside class="wi-plan__queue"
               @dragover.prevent
               @drop.prevent="if (payload && payload.kind === 'block') { $wire.unschedule(payload.id); payload = null; }">
            <div class="wi-plan__queue-head">
                Do przypięcia
                <span class="wi-plan__count">{{ $queue->count() }}</span>
            </div>
            @forelse($queue as $item)
                <article class="wi-plan__card"
                         draggable="true"
                         wire:key="q-{{ $item->id }}"
                         @dragstart="setPayload($event, 'queue', {{ $item->id }}, true)">
                    <i class="bi {{ $item->type->icon() }} wi-plan__card-icon"></i>
                    <div class="wi-plan__card-body">
                        <span class="wi-plan__card-title">{{ $item->title }}</span>
                        <div class="wi-plan__card-meta">
                            <span>{{ $item->type->label() }}</span>
                            @if($item->due_at)
                                <span class="font-mono {{ $item->due_at->isPast() ? 'is-late' : '' }}">
                                    {{ $item->due_at->format('d.m') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <p class="wi-plan__empty">Nic do przypięcia — wszystko ma slot od dziś.</p>
            @endforelse
        </aside>

        <div class="wi-plan__board" x-ref="board">
            <div class="wi-plan__sticky">
                <div class="wi-plan__head">
                    <div class="wi-plan__gutter"></div>
                    @foreach($days as $day)
                        @php $date = $day->toDateString(); @endphp
                        <div class="wi-plan__day-head {{ $date === $today ? 'is-today' : '' }}">
                            <span class="wi-plan__day-name">{{ $dayNames[$day->dayOfWeekIso - 1] }}</span>
                            <span class="wi-plan__day-num font-mono">{{ $day->format('d.m') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="wi-plan__allday">
                    <div class="wi-plan__gutter wi-plan__allday-label">dzień</div>
                    @foreach($days as $day)
                        @php
                            $date = $day->toDateString();
                            $flags = $dueFlags[$date] ?? [];
                            $allDayEvents = $allDayByDay[$date] ?? [];
                        @endphp
                        <div class="wi-plan__allday-cell {{ $date === $today ? 'is-today' : '' }}"
                             wire:key="ad-{{ $date }}"
                             @dragover.prevent="dropEffect($event)"
                             @drop.prevent="dropOn('{{ $date }}', $event.currentTarget, $event.clientY, true, $event)"
                             @mousedown="beginDraw($event, '{{ $date }}', true)">
                            @foreach($flags as $flag)
                                <a href="{{ $flag['url'] }}" class="wi-plan__flag" title="Termin: {{ $flag['title'] }}" @mousedown.stop>
                                    <i class="bi bi-flag-fill"></i>{{ \Illuminate\Support\Str::limit($flag['title'], 22) }}
                                </a>
                            @endforeach
                            @foreach($allDayEvents as $slot)
                                @php $dragId = $slot->blockId; @endphp
                                <div class="wi-plan__chip {{ $slot->ghost ? 'is-ghost' : '' }}"
                                     draggable="true"
                                     wire:key="{{ $slot->key }}"
                                     @mousedown.stop
                                     @dragstart="setPayload($event, 'block', {{ $dragId }}, true)"
                                     @click="openDetail($event, {{ \Illuminate\Support\Js::from($slot->payload()) }})">
                                    <span class="wi-plan__chip-title">{{ $slot->title }}</span>
                                    @if($dragId)
                                        <button type="button"
                                                class="wi-plan__chip-off"
                                                title="Odplanuj"
                                                @mousedown.stop
                                                @click.stop="$wire.unschedule({{ $dragId }})">×</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="wi-plan__grid" style="--hour-px: {{ $hourPx }}px; --grid-h: {{ $gridHeight }}px" @selectstart.prevent>
                <div class="wi-plan__hours">
                    @foreach($hours as $hour)
                        <div class="wi-plan__hour font-mono">{{ sprintf('%02d:00', $hour) }}</div>
                    @endforeach
                </div>

                @foreach($days as $day)
                    @php $date = $day->toDateString(); @endphp
                    <div class="wi-plan__col {{ $date === $today ? 'is-today' : '' }}"
                         data-plan-col
                         data-date="{{ $date }}"
                         style="height: {{ $gridHeight }}px"
                         wire:key="col-{{ $date }}"
                         @dragover.prevent="dropEffect($event)"
                         @drop.prevent="dropOn('{{ $date }}', $event.currentTarget, $event.clientY, false, $event)"
                         @mousedown="beginDraw($event, '{{ $date }}', false)">
                        @foreach($hours as $hour)
                            <div class="wi-plan__slot"></div>
                            <div class="wi-plan__slot wi-plan__slot--q"></div>
                            <div class="wi-plan__slot wi-plan__slot--half"></div>
                            <div class="wi-plan__slot wi-plan__slot--q"></div>
                        @endforeach

                        @php
                            $held = $composerOpen && ! $composerAllDay && $composerDate === $date;
                            $heldStart = min($composerStart, $composerEnd);
                            $heldEnd = max($composerStart, $composerEnd);
                            $heldTotal = max(1, $gridHours * 60);
                            $heldTop = $held ? (($heldStart - $gridStartHour * 60) / $heldTotal) * 100 : 0;
                            $heldHeight = $held ? max(2.2, (($heldEnd - $heldStart) / $heldTotal) * 100) : 0;
                            $heldPad = fn (int $minutes) => sprintf('%02d:%02d', intdiv(max(0, min(24 * 60, $minutes)), 60) % 24, max(0, min(24 * 60, $minutes)) % 60);
                        @endphp
                        <div class="wi-plan__rubber {{ $held ? 'is-held' : '' }}"
                             data-date="{{ $date }}"
                             @if($held) style="top: {{ $heldTop }}%; height: {{ $heldHeight }}%;" @endif>
                            <span class="wi-plan__rubber-line wi-plan__rubber-line--top"></span>
                            <span class="wi-plan__rubber-handle wi-plan__rubber-handle--top"></span>
                            <span class="wi-plan__rubber-time font-mono">@if($held){{ $heldPad($heldStart) }} – {{ $heldPad($heldEnd) }}@endif</span>
                            <span class="wi-plan__rubber-mid"></span>
                            <span class="wi-plan__rubber-handle wi-plan__rubber-handle--bot"></span>
                            <span class="wi-plan__rubber-line wi-plan__rubber-line--bot"></span>
                        </div>

                        @foreach($eventsByDay[$date] ?? [] as $slot)
                            @php
                                $width = 100 / max(1, $slot->laneCount);
                                $left = $slot->lane * $width;
                                $dragId = $slot->kind === 'block' ? $slot->blockId : $slot->workItemId;
                            @endphp
                            <div class="wi-plan__event is-{{ $slot->kind }} {{ $slot->ghost ? 'is-ghost' : '' }} {{ $slot->isCompact() ? 'is-compact' : '' }}"
                                 wire:key="{{ $slot->key }}"
                                 draggable="true"
                                 style="top: {{ $slot->topPercent }}%; height: {{ $slot->heightPercent }}%; left: calc({{ $left }}% + 2px); width: calc({{ $width }}% - 4px);"
                                 @mousedown.stop
                                 @dragstart="setPayload($event, '{{ $slot->kind }}', {{ $dragId }}, true)"
                                 @click="openDetail($event, {{ \Illuminate\Support\Js::from($slot->payload()) }})">
                                <span class="wi-plan__event-title">{{ $slot->title }}</span>
                                @unless($slot->isCompact())
                                    <span class="wi-plan__event-time font-mono">{{ $slot->timeLabel() }}</span>
                                @endunless
                                @if($slot->kind === 'block')
                                    <span class="wi-plan__resize"
                                          @mousedown.stop="beginResize($event, 'block', {{ $dragId }}, '{{ $date }}')"></span>
                                @else
                                    <span class="wi-plan__resize"
                                          @mousedown.stop="beginResize($event, 'meeting', {{ $dragId }}, '{{ $date }}')"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="wi-plan__pop" x-show="detail" x-cloak @click.outside="detail = null">
        <div class="wi-plan__pop-top">
            <i class="bi" :class="detail?.typeIcon"></i>
            <strong x-text="detail?.title"></strong>
        </div>
        <div class="wi-plan__pop-meta font-mono" x-text="detail?.timeLabel"></div>
        <div class="wi-plan__pop-meta" x-text="detail?.typeLabel"></div>
        <div class="wi-plan__pop-actions">
            <a :href="detail?.url" class="btn btn-sm btn-outline-secondary">Otwórz kartę</a>
            <template x-if="detail?.kind === 'block' && detail?.blockId">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        @click="$wire.unschedule(detail.blockId); detail = null">Odplanuj</button>
            </template>
        </div>
    </div>

    @if($composerOpen)
        <div class="wi-plan__composer-backdrop" wire:click="closeComposer"></div>
        <div class="wi-plan__composer" wire:click.stop>
            <div class="wi-plan__composer-head">
                <span>Nowy wpis</span>
                <button type="button" class="wi-plan__nav" wire:click="closeComposer">×</button>
            </div>
            <p class="wi-plan__composer-range font-mono">{{ $composerRangeLabel }}</p>
            <div class="wi-plan__types">
                @foreach($typeOptions as $value => $meta)
                    <label class="{{ $composerType === $value ? 'is-on' : '' }}">
                        <input type="radio" wire:model.live="composerType" value="{{ $value }}">
                        <i class="bi {{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                    </label>
                @endforeach
            </div>

            @if($composerType === 'procedure')
                <select wire:model.live="composerProcedureTemplateId"
                        class="form-select form-select-sm mb-2">
                    <option value="">Szablon procedury</option>
                    @foreach($procedureTemplates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
                @error('composerProcedureTemplateId') <div class="text-danger small">{{ $message }}</div> @enderror
                @if($procedureSubjectType)
                    <select wire:model.live="composerProcedureSubjectId" class="form-select form-select-sm">
                        <option value="">{{ $procedureSubjectType->label() }}</option>
                        @foreach($procedureSubjectOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('composerProcedureSubjectId') <div class="text-danger small">{{ $message }}</div> @enderror
                @elseif($composerProcedureTemplateId !== '')
                    <input type="text"
                           class="form-control form-control-sm"
                           wire:model="composerProcedureNameSuffix"
                           maxlength="80"
                           placeholder="Dopisek (opcjonalnie)">
                    @error('composerProcedureNameSuffix') <div class="text-danger small">{{ $message }}</div> @enderror
                @endif
            @else
                <input type="text"
                       class="form-control form-control-sm"
                       wire:model="composerTitle"
                       placeholder="{{ $composerType === 'meeting' ? 'Temat spotkania' : 'Nazwa' }}"
                       x-init="$el.focus()">
                @error('composerTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @endif

            @if($composerType === 'meeting')
                <input type="text"
                       class="form-control form-control-sm mt-2"
                       wire:model="composerLocation"
                       placeholder="Gdzie">
                @error('composerLocation') <div class="text-danger small">{{ $message }}</div> @enderror
                <div class="wi-plan__people">
                    <span class="wi-plan__people-label">Uczestnicy</span>
                    <div class="wi-plan__people-list">
                        @foreach($users as $user)
                            <label class="wi-plan__people-row">
                                <input type="checkbox" wire:model="composerParticipantIds" value="{{ $user->id }}">
                                <span>{{ $user->name }}{{ (int) $user->id === (int) $calendarUser->id ? ' (kalendarz)' : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="wi-plan__composer-foot">
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closeComposer">Anuluj</button>
                <button type="button" class="btn btn-sm btn-primary" wire:click="submitComposer">Zapisz</button>
            </div>
        </div>
    @endif

<style>
    .wi-plan { display: flex; flex-direction: column; gap: .75rem; min-height: calc(100vh - 8.5rem); position: relative; }
    .wi-plan__toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.25rem; }
    .wi-plan__user { display: inline-flex; align-items: center; gap: .45rem; color: var(--text-muted); font-size: .8rem; }
    .wi-plan__user .form-select { min-width: 160px; background: rgba(255,255,255,.04); border-color: var(--glass-border); color: var(--text-main); }
    .wi-plan__week { display: inline-flex; align-items: center; gap: .35rem; margin-left: auto; }
    .wi-plan__week-label { min-width: 9.5rem; text-align: center; font-size: .8rem; color: var(--text-main); }
    .wi-plan__nav, .wi-plan__today {
        background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
        color: var(--text-muted); border-radius: 8px; padding: .2rem .55rem; font-size: .78rem;
    }
    .wi-plan__nav:hover, .wi-plan__today:hover { color: var(--text-main); border-color: rgba(255,255,255,.16); }
    .wi-plan__body { display: grid; grid-template-columns: minmax(200px, 26%) minmax(0, 1fr); gap: .9rem; min-height: 0; flex: 1; }
    .wi-plan__queue {
        background: var(--bg-card); border: 1px solid var(--glass-border);
        border-radius: 14px; padding: .85rem .75rem 1rem; min-height: 0; overflow: auto;
    }
    .wi-plan__queue-head {
        display: flex; align-items: center; justify-content: space-between;
        font-size: .78rem; font-weight: 600; margin-bottom: .7rem; color: var(--text-main);
    }
    .wi-plan__count {
        font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .68rem; color: var(--text-muted);
        background: rgba(255,255,255,.05); border-radius: 999px; padding: .1rem .5rem;
    }
    .wi-plan__card {
        display: flex; gap: .5rem; align-items: flex-start; padding: .45rem .55rem; margin-bottom: .4rem;
        border: 1px solid rgba(255,255,255,.08); border-left: 3px solid var(--primary);
        border-radius: 10px; background: rgba(255,255,255,.03); cursor: grab;
    }
    .wi-plan__card-icon { color: var(--accent); margin-top: .12rem; font-size: .85rem; }
    .wi-plan__card-title { color: var(--text-main); font-size: .78rem; font-weight: 600; display: block; }
    .wi-plan__card-meta { display: flex; gap: .5rem; font-size: .66rem; color: var(--text-muted); margin-top: .12rem; }
    .wi-plan__card-meta .is-late { color: #f87171; }
    .wi-plan__empty { color: var(--text-muted); font-size: .76rem; margin: 1.5rem .25rem 0; }
    .wi-plan__board {
        background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 14px;
        overflow: auto; min-width: 0; max-height: calc(100vh - 11rem);
    }
    .wi-plan__sticky {
        position: sticky; top: 0; z-index: 8;
        background: rgba(13, 18, 30, .94); backdrop-filter: blur(8px);
    }
    .wi-plan__head, .wi-plan__grid, .wi-plan__allday {
        display: grid; grid-template-columns: 3.2rem repeat(7, minmax(5.5rem, 1fr)); min-width: 52rem;
    }
    .wi-plan__gutter { border-bottom: 1px solid rgba(255,255,255,.06); }
    .wi-plan__day-head { padding: .4rem .35rem .3rem; border-bottom: 1px solid rgba(255,255,255,.06); border-left: 1px solid rgba(255,255,255,.05); }
    .wi-plan__day-head.is-today, .wi-plan__allday-cell.is-today, .wi-plan__col.is-today { background: rgba(59, 130, 246, .07); }
    .wi-plan__day-name { display: block; font-size: .62rem; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
    .wi-plan__day-num { font-size: .76rem; color: var(--text-main); }
    .wi-plan__allday { border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(245, 158, 11, .06); }
    .wi-plan__allday-label { font-size: .58rem; color: var(--text-muted); padding: .3rem .2rem; text-transform: uppercase; letter-spacing: .04em; }
    .wi-plan__allday-cell {
        min-height: 2.2rem; padding: .2rem; border-left: 1px solid rgba(255,255,255,.05);
        display: flex; flex-direction: column; gap: .18rem; cursor: cell;
    }
    .wi-plan__flag, .wi-plan__chip {
        display: flex; align-items: center; gap: .25rem; width: 100%; text-align: left; border: 0; border-radius: 4px;
        padding: .1rem .3rem; font-size: .62rem; line-height: 1.2; cursor: pointer;
        text-decoration: none; overflow: hidden;
    }
    .wi-plan__flag { background: rgba(251, 191, 36, .18); color: #fbbf24; }
    .wi-plan__chip { background: linear-gradient(135deg, rgba(59,130,246,.75), rgba(168,85,247,.7)); color: #fff; cursor: grab; }
    .wi-plan__chip.is-ghost { opacity: .5; }
    .wi-plan__chip-title { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wi-plan__chip-off {
        flex-shrink: 0; border: 0; background: transparent; color: inherit; opacity: .75;
        line-height: 1; padding: 0 .1rem; font-size: .8rem; cursor: pointer;
    }
    .wi-plan__chip-off:hover { opacity: 1; }
    .wi-plan__hours { position: relative; }
    .wi-plan__hour {
        height: var(--hour-px); font-size: .62rem; color: var(--text-muted);
        padding: .08rem .2rem 0 0; text-align: right; border-top: 1px solid rgba(255,255,255,.05);
    }
    .wi-plan__col { position: relative; border-left: 1px solid rgba(255,255,255,.06); cursor: cell; user-select: none; }
    .wi-plan__slot { height: calc(var(--hour-px) / 4); border-top: 1px solid rgba(255,255,255,.035); pointer-events: none; }
    .wi-plan__slot--half { border-top-color: rgba(255,255,255,.07); }
    .wi-plan__slot--q { border-top-style: dotted; border-top-color: rgba(255,255,255,.03); }
    .wi-plan__rubber {
        display: none; position: absolute; left: 4px; right: 4px; z-index: 12; pointer-events: none;
        border-radius: 10px; overflow: visible;
        background: linear-gradient(180deg, rgba(37, 99, 235, .55), rgba(59, 130, 246, .38));
        border: 1px solid rgba(147, 197, 253, .85);
        box-shadow: 0 0 0 1px rgba(59, 130, 246, .25), 0 10px 28px rgba(37, 99, 235, .28);
    }
    .wi-plan__rubber.is-held { display: block; }
    .wi-plan__rubber-time {
        display: block; padding: 8px 10px 0; font-size: .68rem; font-weight: 600; color: #fff; letter-spacing: .01em;
    }
    .wi-plan__rubber-mid {
        position: absolute; left: 10px; right: 10px; top: 50%; border-top: 1px dashed rgba(191, 219, 254, .7);
    }
    .wi-plan__rubber-handle {
        position: absolute; left: 50%; width: 11px; height: 11px; margin-left: -5.5px;
        border-radius: 50%; background: #60a5fa; border: 2px solid #dbeafe;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .25);
    }
    .wi-plan__rubber-handle--top { top: -6px; }
    .wi-plan__rubber-handle--bot { bottom: -6px; }
    .wi-plan__rubber-line {
        position: absolute; left: 50%; width: 0; height: 14px; margin-left: -0.5px;
        border-left: 1px dashed rgba(96, 165, 250, .7);
    }
    .wi-plan__rubber-line--top { bottom: 100%; }
    .wi-plan__rubber-line--bot { top: 100%; }
    .wi-plan__event {
        position: absolute; z-index: 2; border-radius: 6px; padding: 2px 6px 10px;
        overflow: hidden; color: #fff; cursor: pointer; box-sizing: border-box;
        background: #60a5fa; border: 1px solid rgba(255,255,255,.16);
        box-shadow: 0 4px 10px rgba(0,0,0,.18);
    }
    .wi-plan__event.is-compact { padding: 0 5px; border-radius: 4px; }
    .wi-plan__event.is-compact .wi-plan__event-title { line-height: 1.15; }
    .wi-plan__event.is-compact .wi-plan__resize { height: 6px; }
    .wi-plan__event.is-meeting { background: linear-gradient(135deg, #3b82f6, #a855f7); }
    .wi-plan__event.is-block { background: #818cf8; }
    .wi-plan__event.is-ghost { opacity: .55; }
    .wi-plan__event-title {
        display: block; font-size: .64rem; font-weight: 600; line-height: 1.2;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .wi-plan__event-time { display: block; font-size: .58rem; opacity: .88; line-height: 1.2; margin-top: 1px; }
    .wi-plan__resize {
        position: absolute; left: 0; right: 0; bottom: 0; height: 14px; cursor: ns-resize; z-index: 4;
    }
    .wi-plan__pop {
        position: absolute; z-index: 20; top: 4.5rem; left: 32%; width: min(320px, 90%);
        background: rgba(13, 18, 30, .94); border: 1px solid var(--glass-border); border-radius: 12px;
        padding: .85rem .95rem; box-shadow: 0 16px 40px rgba(0,0,0,.4);
    }
    .wi-plan__pop-top { display: flex; align-items: center; gap: .45rem; color: var(--text-main); font-size: .85rem; }
    .wi-plan__pop-meta { font-size: .72rem; color: var(--text-muted); margin-top: .2rem; }
    .wi-plan__pop-actions { display: flex; gap: .5rem; margin-top: .75rem; }
    .wi-plan__composer-backdrop { position: fixed; inset: 0; z-index: 30; background: rgba(0,0,0,.35); }
    .wi-plan__composer {
        position: fixed; z-index: 31; top: 16%; left: 50%; transform: translateX(-50%);
        width: min(360px, 92vw); background: rgba(13, 18, 30, .96);
        border: 1px solid var(--glass-border); border-radius: 14px; padding: .85rem .9rem;
        box-shadow: 0 20px 50px rgba(0,0,0,.45);
    }
    .wi-plan__composer-head { display: flex; justify-content: space-between; align-items: center; font-size: .82rem; font-weight: 600; margin-bottom: .2rem; }
    .wi-plan__composer-range { font-size: .68rem; color: var(--text-muted); margin-bottom: 0; }
    .wi-plan__types { display: flex; gap: .25rem; flex-wrap: wrap; margin: .55rem 0 .55rem; }
    .wi-plan__types label {
        display: inline-flex; align-items: center; gap: .22rem;
        font-size: .62rem; padding: .12rem .4rem; border-radius: 999px; cursor: pointer;
        border: 1px solid rgba(255,255,255,.1); color: var(--text-muted);
    }
    .wi-plan__types label.is-on, .wi-plan__types label:has(input:checked) {
        border-color: transparent; color: #fff;
        background: linear-gradient(135deg, var(--primary), var(--accent));
    }
    .wi-plan__types input { display: none; }
    .wi-plan__people { margin-top: .55rem; }
    .wi-plan__people-label { display: block; font-size: .62rem; color: var(--text-muted); margin-bottom: .25rem; }
    .wi-plan__people-list {
        max-height: 7.5rem; overflow: auto; border: 1px solid rgba(255,255,255,.08);
        border-radius: 8px; padding: .25rem .4rem;
    }
    .wi-plan__people-row {
        display: flex; align-items: center; gap: .4rem; font-size: .7rem; color: var(--text-main);
        padding: .12rem 0;
    }
    .wi-plan__composer-foot { display: flex; justify-content: flex-end; gap: .45rem; margin-top: .75rem; }
    .wi-plan-drawing { user-select: none !important; cursor: ns-resize; }
    [x-cloak] { display: none !important; }
    @media (max-width: 991.98px) {
        .wi-plan__body { grid-template-columns: 1fr; }
        .wi-plan__queue { max-height: 12rem; }
        .wi-plan__board { max-height: calc(100vh - 18rem); }
    }
</style>
</div>
