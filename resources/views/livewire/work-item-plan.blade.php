@php
    $dayNames = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'];
    $hourPx = 52;
    $gridHours = count($hours);
    $gridHeight = $gridHours * $hourPx;
    $typeOptions = [
        'task' => ['label' => 'Zadanie', 'icon' => 'bi-check2-square'],
        'meeting' => ['label' => 'Spotkanie', 'icon' => 'bi-calendar-event'],
        'approval' => ['label' => 'Zatwierdzenie', 'icon' => 'bi-check2-circle'],
        'procedure' => ['label' => 'Procedura', 'icon' => 'bi-share'],
        'session' => ['label' => 'Sesja', 'icon' => 'bi-bag'],
    ];
@endphp

<div class="wi-plan" id="wiPlan"
     x-data="{
        payload: null,
        armed: false,
        ghost: null,
        ghostChip: { visible: false, x: 0, y: 0 },
        lastQueueSelectId: 0,
        sessionNamePrompt: null,
        sessionNameDraft: '',
        dueOpen: '',
        hourPx: {{ $hourPx }},
        startHour: {{ $gridStartHour }},
        viewStartHour: {{ $viewStartHour }},
        snap: {{ $snap }},
        gridHours: {{ $gridHours }},
        defaultMinutes: {{ $defaultMinutes }},
        resizing: null,
        drawing: null,
        holdTimer: null,
        boardScrollX: 0,
        boardScrollY: 0,
        boardScrollRaf: null,
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
        timedCol(date) {
            if (!date) return null;
            return document.querySelector('#wiPlan [data-plan-col][data-date=\'' + date + '\']');
        },
        isTimedOnly(payload) {
            return !!(payload && (payload.kind === 'meeting' || payload.itemType === 'meeting'));
        },
        canScrollBoardUp() {
            const board = this.$refs.board;
            return !!(board && board.scrollTop > 1);
        },
        timedViewport() {
            const board = this.$refs.board;
            if (!board) return null;
            const r = board.getBoundingClientRect();
            const sticky = board.querySelector('.wi-plan__sticky');
            const top = sticky ? sticky.getBoundingClientRect().bottom : r.top;
            return { board, top, bottom: r.bottom };
        },
        preferTimedOverAllDay(clientY, payload) {
            if (this.isTimedOnly(payload)) return true;
            const view = this.timedViewport();
            if (!view) return false;
            return clientY < view.top + 56 && this.canScrollBoardUp();
        },
        asTimedHit(hit) {
            if (!hit) return null;
            if (hit.type === 'col') return hit;
            if (hit.type === 'session' && hit.col && !hit.col.hasAttribute('data-plan-allday')) return hit;
            const col = this.timedCol(hit.date);
            if (!col) return hit;
            return { type: 'col', date: hit.date, col };
        },
        edgeScrollBoard(clientX, clientY) {
            this.boardScrollX = clientX;
            this.boardScrollY = clientY;
            if (this.boardScrollRaf != null) return;
            const tick = () => {
                this.boardScrollRaf = null;
                if (!this.payload && !this.resizing && !this.drawing) return;
                const view = this.timedViewport();
                if (!view) return;
                const board = view.board;
                const y = this.boardScrollY;
                const edge = 120;
                let dy = 0;
                if (y < view.top + edge) {
                    const t = Math.min(1, Math.max(0, (edge - (y - view.top)) / edge));
                    dy = -Math.ceil(10 + t * 42);
                } else if (y > view.bottom - edge) {
                    const t = Math.min(1, Math.max(0, (edge - (view.bottom - y)) / edge));
                    dy = Math.ceil(10 + t * 42);
                }
                if (dy !== 0) {
                    const prev = board.scrollTop;
                    const max = Math.max(0, board.scrollHeight - board.clientHeight);
                    board.scrollTop = Math.max(0, Math.min(max, prev + dy));
                    if (board.scrollTop !== prev) {
                        if (this.payload && this.armed) {
                            this.applyGhostFromPoint(this.boardScrollX, this.boardScrollY);
                        }
                        if (this.resizing) {
                            const col = this.timedCol(this.resizing.date);
                            if (col) {
                                this.resizing.end = this.minutesFromY(this.boardScrollY, col);
                                this.paintRubber();
                            }
                        }
                        if (this.drawing && !this.drawing.allDay) {
                            this.applyDrawPointer(this.boardScrollY);
                            this.paintRubber();
                        }
                    }
                }
                this.boardScrollRaf = requestAnimationFrame(tick);
            };
            this.boardScrollRaf = requestAnimationFrame(tick);
        },
        stopEdgeScroll() {
            if (this.boardScrollRaf != null) {
                cancelAnimationFrame(this.boardScrollRaf);
                this.boardScrollRaf = null;
            }
        },
        hideQueueSource(payload) {
            if (!payload || payload.kind !== 'queue') return;
            document.querySelectorAll('#wiPlan [data-plan-drag=\'queue:' + payload.id + '\']').forEach((el) => {
                el.style.display = 'none';
            });
        },
        hideQueueSources(ids) {
            (ids || []).forEach((id) => this.hideQueueSource({ kind: 'queue', id: id }));
        },
        queueMemberIds(draggedId) {
            const dragged = Number(draggedId);
            const members = [];
            let draggedChecked = false;
            document.querySelectorAll('#wiPlan [data-plan-queue] .tg-select input:checked').forEach((el) => {
                const id = Number(el.value);
                const host = el.closest('[data-plan-drag]');
                const type = (host && host.dataset && host.dataset.planType) ? host.dataset.planType : '';
                if (id === dragged) draggedChecked = true;
                if (id > 0 && type !== 'meeting') members.push(id);
            });
            if (! draggedChecked) return [dragged];
            if (members.indexOf(dragged) === -1) return [dragged];
            return members;
        },
        dropQueueOnCalendar(payload, date, minutes, allDay, copy) {
            if (payload.kind === 'queue' && payload.itemType !== 'meeting') {
                const ids = this.queueMemberIds(payload.id);
                if (ids.length > 1) {
                    this.sessionNamePrompt = { ids: ids, date: date, minutes: minutes, allDay: !!allDay };
                    this.sessionNameDraft = '';
                    this.$nextTick(() => { if (this.$refs.sessionNameInput) this.$refs.sessionNameInput.focus(); });
                    return;
                }
            }
            this.hideQueueSource(payload);
            $wire.dropOnCell(payload.kind, payload.id, date, minutes, !!allDay, !!copy);
        },
        confirmSessionName() {
            if (!this.sessionNamePrompt) return;
            const name = String(this.sessionNameDraft || '').trim();
            if (!name) return;
            const prompt = this.sessionNamePrompt;
            this.hideQueueSources(prompt.ids);
            $wire.dropQueueBundle(prompt.ids, prompt.date, prompt.minutes, !!prompt.allDay, name);
            this.sessionNamePrompt = null;
            this.sessionNameDraft = '';
            this.lastQueueSelectId = 0;
        },
        cancelSessionName() {
            this.sessionNamePrompt = null;
            this.sessionNameDraft = '';
        },
        queueSelectBoxes() {
            return Array.from(document.querySelectorAll('#wiPlan [data-plan-queue] .tg-select input[type=\'checkbox\']'));
        },
        syncQueueSelectedClass(input) {
            const card = input.closest('.tg-dt-card, .tg-task-row');
            if (card) card.classList.toggle('is-selected', !!input.checked);
        },
        toggleQueueSelect(event, id, input) {
            const boxes = this.queueSelectBoxes();
            const ids = boxes.map((el) => Number(el.value));
            if (event.shiftKey && this.lastQueueSelectId) {
                const a = ids.indexOf(this.lastQueueSelectId);
                const b = ids.indexOf(id);
                if (a >= 0 && b >= 0) {
                    const from = Math.min(a, b);
                    const to = Math.max(a, b);
                    for (let i = from; i <= to; i++) {
                        boxes[i].checked = true;
                        this.syncQueueSelectedClass(boxes[i]);
                    }
                    this.lastQueueSelectId = id;
                    return;
                }
            }
            input.checked = !input.checked;
            this.syncQueueSelectedClass(input);
            this.lastQueueSelectId = id;
        },
        onQueueSelectClick(event) {
            const wrap = event.target.closest('#wiPlan [data-plan-queue] .tg-select');
            if (!wrap || !this.$el.contains(wrap)) return;
            const input = wrap.querySelector('input[type=\'checkbox\']');
            if (!input) return;
            event.preventDefault();
            event.stopPropagation();
            this.toggleQueueSelect(event, Number(input.value), input);
        },
        applyDrawPointer(clientY) {
            if (!this.drawing || this.drawing.allDay) return;
            const col = this.timedCol(this.drawing.date);
            if (!col) return;
            const m = this.slotFromY(clientY, col);
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
        trackPointer(event, move, up) {
            const pointerId = event.pointerId;
            const onMove = (e) => { if (e.pointerId === pointerId) move(e); };
            const onUp = (e) => {
                if (e.pointerId !== pointerId) return;
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onUp);
                window.removeEventListener('pointercancel', onUp);
                up(e);
            };
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp);
            window.addEventListener('pointercancel', onUp);
        },
        swallowNextClick() {
            const eat = (ev) => {
                ev.stopPropagation();
                ev.preventDefault();
                window.removeEventListener('click', eat, true);
            };
            window.addEventListener('click', eat, true);
        },
        hitTarget(clientX, clientY) {
            const el = document.elementFromPoint(clientX, clientY);
            if (!el || typeof el.closest !== 'function') return null;
            const queue = el.closest('[data-plan-queue]');
            if (queue) return { type: 'queue', col: queue };
            const session = el.closest('[data-plan-session]');
            if (session) {
                const host = session.closest('[data-plan-col], [data-plan-allday]');
                return {
                    type: 'session',
                    id: Number(session.dataset.planSession),
                    date: host?.dataset.date || '',
                    col: host,
                };
            }
            const allDay = el.closest('[data-plan-allday]');
            if (allDay) return { type: 'allday', date: allDay.dataset.date, col: allDay };
            const head = el.closest('[data-plan-day-head]');
            if (head) return { type: 'head', date: head.dataset.date, col: head };
            const col = el.closest('[data-plan-col]');
            if (col) return { type: 'col', date: col.dataset.date, col };
            return null;
        },
        previewSlot() {
            if (this.drawing && !this.drawing.allDay) return this.drawing;
            if (this.ghost && !this.ghost.allDay && !this.ghost.unschedule) return this.ghost;
            if (this.resizing) {
                const start = this.resizing.start;
                let end = this.resizing.end ?? (start + this.snap);
                if (end <= start) end = start + this.snap;
                return { date: this.resizing.date, start, end };
            }
            return null;
        },
        paintRubber() {
            if (this.ghost && this.ghost.sessionId) {
                document.querySelectorAll('#wiPlan .wi-plan__rubber').forEach((el) => {
                    if (!el.classList.contains('is-held')) el.style.display = 'none';
                    el.classList.remove('is-move');
                });
                return;
            }
            const d = this.previewSlot();
            document.querySelectorAll('#wiPlan .wi-plan__rubber').forEach((el) => {
                if (!d || el.dataset.date !== d.date) {
                    if (!el.classList.contains('is-held')) {
                        el.style.display = 'none';
                    }
                    el.classList.remove('is-move');
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
                el.classList.toggle('is-move', !!(this.ghost || this.resizing));
                const label = el.querySelector('.wi-plan__rubber-time');
                if (label) label.textContent = this.formatMinutes(start) + ' – ' + this.formatMinutes(end);
            });
        },
        paintDropTargets() {
            document.querySelectorAll('#wiPlan [data-plan-allday]').forEach((el) => {
                el.classList.toggle('is-drop', !!(this.ghost && this.ghost.allDay && el.dataset.date === this.ghost.date));
            });
            document.querySelectorAll('#wiPlan [data-plan-queue]').forEach((el) => {
                el.classList.toggle('is-drop', !!(this.ghost && this.ghost.unschedule));
            });
            document.querySelectorAll('#wiPlan [data-plan-session]').forEach((el) => {
                el.classList.toggle('is-session-drop', !!(this.ghost && this.ghost.sessionId && Number(el.dataset.planSession) === this.ghost.sessionId));
            });
        },
        markSource(on) {
            document.querySelectorAll('#wiPlan .is-source').forEach((el) => el.classList.remove('is-source'));
            if (!on || !this.payload) return;
            const kind = this.payload.kind;
            const ids = kind === 'queue' ? this.queueMemberIds(this.payload.id) : [this.payload.id];
            ids.forEach((id) => {
                const key = kind + ':' + id;
                document.querySelectorAll('#wiPlan [data-plan-drag]').forEach((el) => {
                    if (el.getAttribute('data-plan-drag') === key) el.classList.add('is-source');
                });
            });
        },
        applyGhostFromPoint(clientX, clientY) {
            if (!this.payload) return;
            this.ghostChip = { visible: true, x: clientX, y: clientY };
            let hit = this.hitTarget(clientX, clientY);
            if (!hit) {
                this.ghost = null;
                this.paintRubber();
                this.paintDropTargets();
                return;
            }
            if (hit.type === 'session' && this.payload.kind === 'queue' && this.payload.itemType !== 'meeting') {
                this.ghost = { sessionId: hit.id };
                this.paintRubber();
                this.paintDropTargets();
                return;
            }
            if (hit.type === 'queue') {
                this.ghost = this.payload.kind === 'block' ? { unschedule: true } : null;
                this.paintRubber();
                this.paintDropTargets();
                return;
            }
            if (hit.type === 'head' || (hit.type === 'allday' && this.preferTimedOverAllDay(clientY, this.payload))) {
                const timed = this.asTimedHit(hit);
                if (timed && timed.col && timed.type === 'col') {
                    hit = timed;
                } else if (this.isTimedOnly(this.payload)) {
                    this.ghost = null;
                    this.paintRubber();
                    this.paintDropTargets();
                    return;
                }
            }
            if (hit.type === 'allday') {
                this.ghost = { date: hit.date, allDay: true, start: 0, end: 0 };
                this.paintRubber();
                this.paintDropTargets();
                return;
            }
            if (!hit.col) {
                this.ghost = null;
                this.paintRubber();
                this.paintDropTargets();
                return;
            }
            const start = this.slotFromY(clientY, hit.col);
            const duration = this.payload.duration || this.defaultMinutes;
            this.ghost = {
                date: hit.date,
                allDay: false,
                start,
                end: Math.min(24 * 60, start + duration),
            };
            this.paintRubber();
            this.paintDropTargets();
        },
        clearDrag() {
            this.stopEdgeScroll();
            if (this.holdTimer) {
                clearTimeout(this.holdTimer);
                this.holdTimer = null;
            }
            this.payload = null;
            this.armed = false;
            this.ghost = null;
            this.ghostChip = { visible: false, x: 0, y: 0 };
            this.markSource(false);
            this.$el.classList.remove('is-dragging', 'is-queue-drag');
            document.body.classList.remove('wi-plan-dragging');
            this.paintRubber();
            this.paintDropTargets();
        },
        armDrag() {
            if (this.armed || !this.payload) return;
            this.armed = true;
            if (this.payload.kind === 'queue' && this.payload.itemType !== 'meeting') {
                const ids = this.queueMemberIds(this.payload.id);
                if (ids.length > 1) {
                    this.payload.title = ids.length + ' WI → sesja';
                    this.payload.bundle = true;
                }
            }
            this.$el.classList.add('is-dragging');
            this.$el.classList.toggle('is-queue-drag', this.payload.kind === 'queue');
            document.body.classList.add('wi-plan-dragging');
            this.markSource(true);
        },
        commitDrop(event, payload) {
            const copy = !!(payload.copy || event.ctrlKey || event.metaKey);
            let hit = this.hitTarget(event.clientX, event.clientY);
            if (!hit) return;
            if (hit.type === 'queue') {
                if (payload.kind === 'block') $wire.unschedule(payload.id);
                return;
            }
            if (hit.type === 'session') {
                if (payload.kind === 'queue' && payload.itemType !== 'meeting') {
                    const ids = this.queueMemberIds(payload.id);
                    this.hideQueueSources(ids);
                    if (ids.length > 1) {
                        $wire.addQueueItemsToSession(ids, hit.id);
                    } else {
                        $wire.addToSession(hit.id, payload.id);
                    }
                    return;
                }
                if (!hit.col || !hit.date) return;
                if (hit.col.hasAttribute('data-plan-allday')) {
                    if (this.preferTimedOverAllDay(event.clientY, payload)) {
                        hit = this.asTimedHit(hit);
                    } else if (this.isTimedOnly(payload)) {
                        return;
                    } else {
                        this.dropQueueOnCalendar(payload, hit.date, 0, true, copy);
                        return;
                    }
                }
                if (!hit || !hit.col) return;
                const minutes = this.slotFromY(event.clientY, hit.col);
                this.dropQueueOnCalendar(payload, hit.date, minutes, false, copy);
                return;
            }
            if (hit.type === 'head' || (hit.type === 'allday' && this.preferTimedOverAllDay(event.clientY, payload))) {
                hit = this.asTimedHit(hit);
            }
            if (hit && hit.type === 'allday') {
                if (this.isTimedOnly(payload)) return;
                this.dropQueueOnCalendar(payload, hit.date, 0, true, copy);
                return;
            }
            if (!hit || !hit.col) return;
            const minutes = this.slotFromY(event.clientY, hit.col);
            this.dropQueueOnCalendar(payload, hit.date, minutes, false, copy);
        },
        beginOpen(event, kind, id) {
            if (this.payload || this.resizing || this.drawing || !id) return;
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            if (event.target.closest('.wi-plan__grip, .wi-plan__resize, .wi-plan__chip-off, a, button')) return;
            event.stopPropagation();
            const x0 = event.clientX;
            const y0 = event.clientY;
            this.trackPointer(event, () => {}, (e) => {
                const dx = e.clientX - x0;
                const dy = e.clientY - y0;
                if ((dx * dx + dy * dy) < 36) {
                    this.swallowNextClick();
                    $wire.openEvent(kind, id);
                }
            });
        },
        onQueueGrip(event) {
            const grip = event.target.closest('[data-plan-queue-grip]');
            if (!grip) return;
            const host = grip.closest('[data-plan-drag]');
            if (!host) return;
            const raw = host.dataset.planDrag || '';
            const parts = raw.split(':');
            if (parts[0] !== 'queue' || !parts[1]) return;
            this.beginDrag(
                event,
                'queue',
                Number(parts[1]),
                this.defaultMinutes,
                host.dataset.planTitle || '',
                host.dataset.planType || 'task',
                true,
            );
        },
        beginDrag(event, kind, id, duration, title, itemType, immediate) {
            if (this.resizing || this.drawing || !id) return;
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            if (event.target.closest('.wi-plan__resize, .wi-plan__chip-off, a, button')) return;
            event.preventDefault();
            event.stopPropagation();
            this.payload = {
                kind,
                id,
                itemType: itemType || kind,
                copy: !!(event.ctrlKey || event.metaKey),
                duration: duration || this.defaultMinutes,
                title: title || '',
                x0: event.clientX,
                y0: event.clientY,
            };
            this.armed = false;
            if (immediate) {
                this.armDrag();
                this.applyGhostFromPoint(this.payload.x0, this.payload.y0);
            } else {
                this.holdTimer = window.setTimeout(() => {
                    if (!this.payload || this.armed) return;
                    this.armDrag();
                    this.applyGhostFromPoint(this.payload.x0, this.payload.y0);
                }, 180);
            }
            this.trackPointer(event, (e) => {
                if (!this.payload) return;
                const dx = e.clientX - this.payload.x0;
                const dy = e.clientY - this.payload.y0;
                if (!this.armed && (dx * dx + dy * dy) >= 36) {
                    if (this.holdTimer) {
                        clearTimeout(this.holdTimer);
                        this.holdTimer = null;
                    }
                    this.armDrag();
                }
                if (!this.armed) return;
                this.payload.copy = !!(e.ctrlKey || e.metaKey || this.payload.copy);
                this.edgeScrollBoard(e.clientX, e.clientY);
                this.applyGhostFromPoint(e.clientX, e.clientY);
            }, (e) => {
                const payload = this.payload;
                const armed = this.armed;
                if (!armed) {
                    this.clearDrag();
                    return;
                }
                this.swallowNextClick();
                this.commitDrop(e, payload);
                this.clearDrag();
            });
        },
        beginResize(event, kind, id, date, startMinutes) {
            event.stopPropagation();
            event.preventDefault();
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            if (!event.currentTarget.closest('[data-plan-col]')) return;
            const eventEl = event.currentTarget.closest('.wi-plan__event');
            if (eventEl) eventEl.classList.add('is-resizing');
            this.resizing = { kind, id, date, start: startMinutes, end: startMinutes + this.snap };
            document.body.classList.add('wi-plan-drawing');
            this.paintRubber();
            this.trackPointer(event, (e) => {
                if (!this.resizing) return;
                this.edgeScrollBoard(e.clientX, e.clientY);
                const resizeCol = this.timedCol(this.resizing.date);
                if (resizeCol) {
                    this.resizing.end = this.minutesFromY(e.clientY, resizeCol);
                }
                this.paintRubber();
            }, (e) => {
                this.stopEdgeScroll();
                document.body.classList.remove('wi-plan-drawing');
                document.querySelectorAll('#wiPlan .is-resizing').forEach((el) => el.classList.remove('is-resizing'));
                if (this.resizing) {
                    const resizeCol = this.timedCol(this.resizing.date);
                    const minutes = resizeCol ? this.minutesFromY(e.clientY, resizeCol) : this.resizing.end;
                    $wire.resizeOnCell(this.resizing.kind, this.resizing.id, this.resizing.date, minutes);
                }
                this.resizing = null;
                this.paintRubber();
            });
        },
        beginDraw(event, date, allDay) {
            if (this.payload || this.resizing) return;
            if (event.target.closest('.wi-plan__event, .wi-plan__chip, .wi-plan__flag, .wi-plan__chip-off, .wi-plan__float, .wi-plan__pop')) return;
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            event.preventDefault();
            const col = event.currentTarget;
            const anchor = allDay ? 0 : this.slotFromY(event.clientY, col);
            this.drawing = {
                date,
                anchor,
                start: anchor,
                end: anchor + (allDay ? 0 : this.snap),
                allDay,
            };
            document.body.classList.add('wi-plan-drawing');
            this.paintRubber();
            this.trackPointer(event, (e) => {
                this.edgeScrollBoard(e.clientX, e.clientY);
                this.applyDrawPointer(e.clientY);
                this.paintRubber();
            }, (e) => {
                this.stopEdgeScroll();
                document.body.classList.remove('wi-plan-drawing');
                if (!this.drawing) {
                    this.paintRubber();
                    return;
                }
                if (!this.drawing.allDay && e.clientY) {
                    this.applyDrawPointer(e.clientY);
                }
                const d = this.drawing;
                this.drawing = null;
                this.paintRubber();
                $wire.openComposer(d.date, d.start, d.end || d.start + this.snap, !!d.allDay);
            });
        },
        scrollToWorkHours() {
            const el = this.$refs.board;
            if (!el || el.dataset.scrolled === '1') return;
            el.scrollTop = Math.max(0, (this.viewStartHour - this.startHour) * this.hourPx);
            el.dataset.scrolled = '1';
        },
        init() {
            this.scrollToWorkHours();
            this.$el.addEventListener('click', (e) => this.onQueueSelectClick(e), true);
        }
     }">

    @if($undo)
        <div class="wi-plan__undo"
             wire:key="undo-{{ $undo['token'] }}"
             x-data
             x-init="setTimeout(() => $wire.dismissUndo(), 8000)">
            <span>{{ $undo['message'] }}</span>
            <button type="button" class="wi-plan__undo-action" wire:click="undoLastChange">Cofnij</button>
            <button type="button" class="wi-plan__undo-close" title="Zamknij" wire:click="dismissUndo">×</button>
        </div>
    @endif

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
        <aside class="wi-plan__queue" data-plan-queue @pointerdown="onQueueGrip($event)">
            <div class="wi-plan__queue-head">
                Do przypięcia
                <div class="wi-plan__queue-tools">
                    @unless($pinId)
                        <button type="button"
                                class="wi-plan__nav"
                                wire:click="openUnscheduledMeeting"
                                title="Spotkanie bez godziny">
                            <i class="bi bi-calendar-plus"></i>
                        </button>
                    @endunless
                </div>
            </div>
            @if($pinId && $pinnedTitle)
                <p class="wi-plan__pin-hint">Nowe: <strong>{{ $pinnedTitle }}</strong> — przeciągnij na godzinę w siatce.</p>
            @endif
            <livewire:tasks-grid
                :plan-queue="true"
                :plan-user-id="$calendarUser->id"
                :plan-pin-id="$pinId"
                :key="'plan-q-'.$calendarUser->id.'-'.($pinId ?? 0)"
            />
        </aside>

        <div class="wi-plan__board" x-ref="board">
            <div class="wi-plan__sticky">
                <div class="wi-plan__head">
                    <div class="wi-plan__gutter"></div>
                    @foreach($days as $day)
                        @php $date = $day->toDateString(); @endphp
                        <div class="wi-plan__day-head {{ $date === $today ? 'is-today' : '' }}"
                             data-plan-day-head
                             data-date="{{ $date }}">
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
                             data-plan-allday
                             data-date="{{ $date }}"
                             wire:key="ad-{{ $date }}"
                             @pointerdown="beginDraw($event, '{{ $date }}', true)">
                            @php
                                $flagVisible = array_slice($flags, 0, 3);
                                $flagHidden = array_slice($flags, 3);
                            @endphp
                            @foreach($flagVisible as $flag)
                                <a href="{{ $flag['url'] }}" class="wi-plan__flag" title="Termin: {{ $flag['title'] }}" @pointerdown.stop>
                                    <i class="bi bi-flag-fill"></i>{{ \Illuminate\Support\Str::limit($flag['title'], 22) }}
                                </a>
                            @endforeach
                            @if($flagHidden !== [])
                                <button type="button"
                                        class="wi-plan__flag wi-plan__flag-more"
                                        @pointerdown.stop
                                        @click.stop="dueOpen = dueOpen === '{{ $date }}' ? '' : '{{ $date }}'">
                                    +{{ count($flagHidden) }}
                                </button>
                                <div class="wi-plan__flag-overflow"
                                     x-show="dueOpen === '{{ $date }}'"
                                     x-cloak
                                     @click.stop
                                     @pointerdown.stop>
                                    @foreach($flagHidden as $flag)
                                        <a href="{{ $flag['url'] }}" class="wi-plan__flag" title="Termin: {{ $flag['title'] }}">
                                            <i class="bi bi-flag-fill"></i>{{ \Illuminate\Support\Str::limit($flag['title'], 22) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            @foreach($allDayEvents as $slot)
                                @php $dragId = $slot->blockId; @endphp
                                <div class="wi-plan__chip {{ $slot->ghost ? 'is-ghost' : '' }} {{ $slot->isSession ? 'is-session' : '' }} {{ $slot->kind === 'meeting' ? 'is-meeting' : '' }}"
                                     wire:key="{{ $slot->key }}"
                                     data-plan-drag="block:{{ $dragId }}"
                                     @if($slot->isSession) data-plan-session="{{ $dragId }}" @endif
                                     @pointerdown.stop="beginOpen($event, 'block', {{ $dragId ?: 0 }})">
                                    <span class="wi-plan__grip"
                                          title="Przesuń"
                                          @pointerdown.stop="beginDrag($event, 'block', {{ $dragId ?: 0 }}, {{ $defaultMinutes }}, {{ \Illuminate\Support\Js::from($slot->title) }}, 'block', true)"></span>
                                    @if($slot->isSession)
                                        <i class="bi bi-bag wi-plan__chip-icon"></i>
                                    @elseif($slot->kind === 'meeting')
                                        <i class="bi bi-people-fill wi-plan__chip-icon"></i>
                                    @endif
                                    <span class="wi-plan__chip-title">{{ $slot->title }}</span>
                                    @if($slot->isSession)
                                        <span class="wi-plan__session-count">{{ count($slot->members) }} WI</span>
                                    @endif
                                    @if($dragId)
                                        <button type="button"
                                                class="wi-plan__chip-off"
                                                title="Odplanuj"
                                                @pointerdown.stop
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
                         @pointerdown="beginDraw($event, '{{ $date }}', false)">
                        @foreach($hours as $hour)
                            <div class="wi-plan__slot wi-plan__slot--hour"></div>
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
                                $startMin = ((int) $slot->startsAt->format('H') * 60) + (int) $slot->startsAt->format('i');
                                $duration = max($snap, $slot->durationMinutes() ?: $defaultMinutes);
                                $resizeKind = $slot->kind === 'block' ? 'block' : 'meeting';
                            @endphp
                            <div class="wi-plan__event is-{{ $slot->kind }} {{ $slot->isSession ? 'is-session' : '' }} {{ $slot->ghost ? 'is-ghost' : '' }} {{ $slot->isCompact() ? 'is-compact' : '' }} {{ $slot->hidesTime() ? 'is-tight' : '' }}"
                                 wire:key="{{ $slot->key }}"
                                 data-plan-drag="{{ $slot->kind }}:{{ $dragId }}"
                                 @if($slot->isSession) data-plan-session="{{ $dragId }}" @endif
                                 style="top: {{ $slot->topPercent }}%; height: {{ $slot->heightPercent }}%; left: calc({{ $left }}% + 2px); width: calc({{ $width }}% - 4px);"
                                 @pointerdown.stop="beginOpen($event, '{{ $slot->kind }}', {{ $dragId ?: 0 }})">
                                <span class="wi-plan__grip"
                                      title="Przesuń"
                                      @pointerdown.stop="beginDrag($event, '{{ $slot->kind }}', {{ $dragId ?: 0 }}, {{ $duration }}, {{ \Illuminate\Support\Js::from($slot->title) }}, '{{ $slot->kind }}', true)"></span>
                                @if($slot->isSession)
                                    <span class="wi-plan__event-head">
                                        <i class="bi bi-bag"></i>
                                        @unless($slot->isCompact())
                                            <span class="wi-plan__event-title">{{ $slot->title }}</span>
                                        @endunless
                                        <span class="wi-plan__session-count">{{ count($slot->members) }} WI</span>
                                    </span>
                                    @unless($slot->hidesTime())
                                        <span class="wi-plan__event-time font-mono">{{ $slot->timeLabel() }}</span>
                                    @endunless
                                @elseif($slot->kind === 'meeting')
                                    <span class="wi-plan__event-head">
                                        <i class="bi bi-people-fill"></i>
                                        <span class="wi-plan__event-title">{{ $slot->title }}</span>
                                    </span>
                                    @unless($slot->hidesTime())
                                        <span class="wi-plan__event-time font-mono">{{ $slot->timeLabel() }}</span>
                                    @endunless
                                @else
                                    <span class="wi-plan__event-title">{{ $slot->title }}</span>
                                    @unless($slot->hidesTime())
                                        <span class="wi-plan__event-time font-mono">{{ $slot->timeLabel() }}</span>
                                    @endunless
                                @endif
                                <span class="wi-plan__resize"
                                      title="Zmień czas"
                                      @pointerdown.stop="beginResize($event, '{{ $resizeKind }}', {{ $dragId ?: 0 }}, '{{ $date }}', {{ $startMin }})"></span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if($openCard)
        <div class="wi-plan__pop-backdrop" wire:click="closeEvent"></div>
        <div class="wi-plan__pop {{ $openCard['isSession'] && ! $openCard['viewingMember'] ? 'is-session-pop' : 'is-card-pop' }}{{ ! empty($openCard['procedureRun']) ? ' is-procedure-pop' : '' }}{{ ! empty($openCard['approval']) ? ' is-approval-pop' : '' }}"
             wire:key="plan-card-{{ $openCard['key'] }}"
             wire:click.stop
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="if (!document.querySelector('.task-qe-overlay') && !document.activeElement?.closest('input, textarea, select')) $wire.closeEvent()"
             @keydown.left.window="if (!document.querySelector('.task-qe-overlay') && !document.activeElement?.closest('input, textarea, select')) $wire.openPrevSessionMember()"
             @keydown.right.window="if (!document.querySelector('.task-qe-overlay') && !document.activeElement?.closest('input, textarea, select')) $wire.openNextSessionMember()">
            <div class="wi-plan__pop-head">
                <div class="wi-plan__pop-head-copy">
                    <span class="wi-plan__pop-head-kicker"><i class="bi bi-eye"></i> Podgląd z Planera</span>
                    <span class="wi-plan__pop-head-time font-mono">{{ $openCard['timeLabel'] }}</span>
                </div>
                <span class="wi-plan__pop-head-type">
                    <i class="bi {{ $openCard['typeIcon'] }}"></i>{{ $openCard['typeLabel'] }}
                </span>
                @if($openCard['isSession'] && ! $openCard['viewingMember'])
                    @if($editingSessionTitle)
                        <div class="wi-plan__pop-rename">
                            <input type="text"
                                   class="form-control form-control-sm"
                                   wire:model="sessionTitleDraft"
                                   maxlength="255"
                                   placeholder="Nazwa sesji"
                                   wire:keydown.enter="saveSessionTitle"
                                   wire:keydown.escape="cancelSessionRename"
                                   x-data x-init="$el.focus(); $el.select()">
                            <button type="button" class="btn btn-sm btn-primary" wire:click="saveSessionTitle">Zapisz</button>
                        </div>
                    @else
                        <strong class="wi-plan__pop-head-title">{{ $openCard['title'] !== '' ? $openCard['title'] : 'Sesja' }}</strong>
                        <button type="button"
                                class="wi-plan__nav wi-plan__pop-rename-btn"
                                title="Zmień nazwę"
                                wire:click="startSessionRename">
                            <i class="bi bi-pencil"></i>
                        </button>
                    @endif
                @endif
                @if($openCard['viewingMember'] && ($openCard['memberTotal'] ?? 0) > 1)
                    <div class="wi-plan__pop-member-nav">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                wire:click="openPrevSessionMember"
                                @disabled(! $openCard['prevMemberId'])>
                            <i class="bi bi-chevron-left"></i> Poprzednie
                        </button>
                        <span class="font-mono">{{ $openCard['memberIndex'] }} / {{ $openCard['memberTotal'] }}</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                wire:click="openNextSessionMember"
                                @disabled(! $openCard['nextMemberId'])>
                            Następne <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                @endif
                <div class="wi-plan__pop-head-actions">
                    @if($openCard['viewingMember'])
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closeSessionMember">← Sesja</button>
                    @endif
                    @if($openCard['canUnschedule'] && $openCard['blockId'])
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="unschedule({{ $openCard['blockId'] }})">Odplanuj</button>
                    @endif
                    <button type="button" class="wi-plan__nav wi-plan__pop-close" title="Zamknij" wire:click="closeEvent">×</button>
                </div>
            </div>

            <div class="wi-plan__pop-body">
                <div class="wi-plan__pop-sheet">
                    @if($openCard['isSession'] && ! $openCard['viewingMember'])
                        <div class="wi-plan__pop-members">
                            @forelse($openCard['members'] as $member)
                                <article class="wi-plan__card wi-plan__pop-card">
                                    <i class="bi {{ $member['typeIcon'] }} wi-plan__card-icon"></i>
                                    <div class="wi-plan__card-body">
                                        <button type="button"
                                                class="wi-plan__card-title"
                                                wire:click="openSessionMember({{ $member['id'] }})">{{ $member['title'] }}</button>
                                        <div class="wi-plan__card-meta">
                                            <span>{{ $member['typeLabel'] }}</span>
                                            @if($member['dueLabel'])
                                                <span class="font-mono {{ $member['dueLate'] ? 'is-late' : '' }}">{{ $member['dueLabel'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button"
                                            class="wi-plan__chip-off"
                                            title="Wyrzuć z sesji"
                                            wire:click="removeFromSession({{ $openCard['blockId'] }}, {{ $member['id'] }})">×</button>
                                </article>
                            @empty
                                <p class="wi-plan__pop-empty">Przeciągnij zadania z kolejki na tę kartę.</p>
                            @endforelse
                        </div>
                    @elseif($openCard['task'])
                        @if($openCard['procedureRun'])
                            <livewire:procedure-run-stepper
                                :run="$openCard['procedureRun']"
                                :compact="true"
                                wire:key="plan-stepper-{{ $openCard['procedureRun']->id }}"
                            />
                        @endif
                        @include('tasks.partials.task-body', [
                            'task' => $openCard['task'],
                            'showSubtasks' => $openCard['showSubtasks'],
                            'showComments' => true,
                            'showActivity' => false,
                            'wireKey' => 'plan-'.$openCard['task']->id,
                        ])
                    @elseif($openCard['approval'])
                        @include('approval-requests.partials.body', [
                            'approval' => $openCard['approval'],
                            'embedded' => true,
                            'showComments' => true,
                        ])
                    @else
                        <p class="wi-plan__pop-fallback-title">{{ $openCard['title'] }}</p>
                        @if($openCard['description'] !== '')
                            <p class="wi-plan__pop-fallback-body">{{ $openCard['description'] }}</p>
                        @endif
                        @if($openCard['url'] !== '')
                            <a href="{{ $openCard['url'] }}" class="btn btn-sm btn-outline-secondary">Otwórz kartę</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div x-show="sessionNamePrompt" x-cloak>
        <div class="wi-plan__composer-backdrop" @click="cancelSessionName()"></div>
        <div class="wi-plan__composer" @click.stop>
            <div class="wi-plan__composer-head">
                <span>Nazwa sesji</span>
                <button type="button" class="wi-plan__nav" @click="cancelSessionName()">×</button>
            </div>
            <p class="wi-plan__composer-range font-mono" x-text="sessionNamePrompt ? (sessionNamePrompt.ids.length + ' WI') : ''"></p>
            <input type="text"
                   class="form-control form-control-sm"
                   x-ref="sessionNameInput"
                   x-model="sessionNameDraft"
                   maxlength="255"
                   placeholder="Jak nazwiesz ten worek?"
                   @keydown.enter.prevent="confirmSessionName()"
                   @keydown.escape.prevent="cancelSessionName()">
            <div class="wi-plan__composer-foot">
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="cancelSessionName()">Anuluj</button>
                <button type="button"
                        class="btn btn-sm btn-primary"
                        @click="confirmSessionName()"
                        :disabled="!(sessionNameDraft || '').trim()">Zapisz</button>
            </div>
        </div>
    </div>

    @if($composerOpen)
        <div class="wi-plan__composer-backdrop" wire:click="closeComposer"></div>
        <div class="wi-plan__composer" wire:click.stop>
            <div class="wi-plan__composer-head">
                <span>{{ $composerUnscheduled ? 'Nowe spotkanie' : 'Nowy wpis' }}</span>
                <button type="button" class="wi-plan__nav" wire:click="closeComposer">×</button>
            </div>
            <p class="wi-plan__composer-range font-mono">{{ $composerRangeLabel }}</p>
            @unless($composerUnscheduled)
            <div class="wi-plan__types">
                @foreach($typeOptions as $value => $meta)
                    @if($value === 'meeting' && $composerAllDay)
                        @continue
                    @endif
                    <label class="{{ $composerType === $value ? 'is-on' : '' }}">
                        <input type="radio" wire:model.live="composerType" value="{{ $value }}">
                        <i class="bi {{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                    </label>
                @endforeach
            </div>
            @endunless

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
                       placeholder="{{ $composerType === 'meeting' ? 'Temat spotkania' : ($composerType === 'session' ? 'Nazwa (opcjonalnie)' : 'Nazwa') }}"
                       x-init="$el.focus()">
                @error('composerTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @if($composerType === 'session')
                    <p class="wi-plan__composer-hint">Potem wrzucisz zadania z kolejki na tę kartę. Spotkań tu nie mieszaj.</p>
                @endif
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

    <div class="wi-plan__float"
         x-show="ghostChip.visible"
         x-cloak
         :class="payload?.copy && 'is-copy'"
         :style="'left:' + ghostChip.x + 'px; top:' + ghostChip.y + 'px'"
         x-text="payload?.title"></div>

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
    .wi-plan__body { display: grid; grid-template-columns: minmax(240px, 30%) minmax(0, 1fr); gap: .9rem; min-height: 0; flex: 1; }
    .wi-plan__queue {
        background: var(--bg-card); border: 1px solid var(--glass-border);
        border-radius: 14px; padding: .85rem .75rem 1rem; min-height: 0; overflow: auto;
    }
    .wi-plan__queue-head {
        display: flex; align-items: center; justify-content: space-between;
        font-size: .78rem; font-weight: 600; margin-bottom: .7rem; color: var(--text-main);
    }
    .wi-plan__queue-tools { display: inline-flex; align-items: center; gap: .35rem; }
    .wi-plan__count {
        font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .68rem; color: var(--text-muted);
        background: rgba(255,255,255,.05); border-radius: 999px; padding: .1rem .5rem;
    }
    .wi-plan__card {
        display: flex; gap: .5rem; align-items: flex-start; padding: .45rem .55rem; margin-bottom: .4rem;
        border: 1px solid rgba(255,255,255,.08); border-left: 3px solid var(--primary);
        border-radius: 10px; background: rgba(255,255,255,.03); cursor: grab;
        -webkit-user-drag: none; user-select: none; touch-action: none;
    }
    .wi-plan__card.is-pin {
        border-color: rgba(168, 85, 247, .55);
        box-shadow: 0 0 0 1px rgba(59, 130, 246, .45), 0 8px 22px rgba(59, 130, 246, .18);
    }
    .wi-plan__pin-hint {
        font-size: .72rem; color: var(--text-muted); margin: 0 0 .7rem; line-height: 1.35;
    }
    .wi-plan__pin-hint strong { color: var(--text-main); font-weight: 600; }
    .wi-plan__card-icon { color: var(--accent); margin-top: .12rem; font-size: .85rem; }
    .wi-plan__card-body { min-width: 0; flex: 1; }
    .wi-plan__card-title { color: var(--text-main); font-size: .78rem; font-weight: 600; display: block; }
    .wi-plan__card-meta { display: flex; gap: .5rem; font-size: .66rem; color: var(--text-muted); margin-top: .12rem; }
    .wi-plan__card-meta .is-late { color: #f87171; }
    .wi-plan__empty { color: var(--text-muted); font-size: .76rem; margin: 1.5rem .25rem 0; }
    .wi-plan__queue .xuiv2-tasks { isolation: auto; }
    .wi-plan__queue .xuiv2-tasks .card.mb-2 { margin-bottom: .35rem !important; }
    .wi-plan__queue .tg-add-actions { display: none !important; }
    .wi-plan__queue .tg-cards { display: flex; flex-direction: column; gap: .35rem; }
    .wi-plan__queue .dt-card.card {
        padding: .4rem .55rem .4rem 2.2rem !important;
        border-radius: 10px !important;
    }
    .wi-plan__queue .dt-card__title {
        font-size: .82rem;
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .wi-plan__queue .dt-card__title:has(+ .dt-card__row) {
        margin-bottom: .35rem;
        padding-bottom: .3rem;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .wi-plan__queue .dt-card__row { font-size: .7rem; }
    .wi-plan__queue .tg-dt-card .wi-plan__grip {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 1.15rem;
        z-index: 3;
        border-radius: 10px 0 0 10px;
        cursor: grab;
        touch-action: none;
    }
    .wi-plan__queue .tg-dt-card.is-pin {
        border-color: rgba(168, 85, 247, .55);
        box-shadow: 0 0 0 1px rgba(59, 130, 246, .45), 0 8px 22px rgba(59, 130, 246, .18);
    }
    .wi-plan__queue .rp-active-filters { margin-bottom: .45rem !important; }
    .wi-plan__queue .rp-active-filters__chip.is-locked { padding-right: .55rem; }
    .wi-plan__board {
        background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 14px;
        overflow: auto; min-width: 0; max-height: calc(100vh - 11rem);
        overscroll-behavior: contain;
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
    .wi-plan__day-head.is-today, .wi-plan__allday-cell.is-today, .wi-plan__col.is-today { background-color: rgba(59, 130, 246, .07); }
    .wi-plan__day-name { display: block; font-size: .62rem; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
    .wi-plan__day-num { font-size: .76rem; color: var(--text-main); }
    .wi-plan__allday { border-bottom: 1px solid rgba(255,255,255,.08); background: rgba(245, 158, 11, .06); }
    .wi-plan__allday-label { font-size: .58rem; color: var(--text-muted); padding: .3rem .2rem; text-transform: uppercase; letter-spacing: .04em; }
    .wi-plan__allday-cell {
        position: relative;
        min-height: 2.2rem; padding: .2rem; border-left: 1px solid rgba(255,255,255,.05);
        display: flex; flex-direction: column; gap: .18rem; cursor: cell;
        overflow: visible;
    }
    .wi-plan__flag, .wi-plan__chip {
        display: flex; align-items: center; gap: .25rem; width: 100%; text-align: left; border: 0; border-radius: 4px;
        padding: .1rem .3rem; font-size: .62rem; line-height: 1.2; cursor: pointer;
        text-decoration: none; overflow: hidden;
    }
    .wi-plan__flag { background: rgba(251, 191, 36, .18); color: #fbbf24; }
    .wi-plan__flag-more { font-weight: 600; background: rgba(251, 191, 36, .28); }
    .wi-plan__flag-overflow {
        display: flex; flex-direction: column; gap: .18rem;
    }
    .wi-plan__chip {
        position: relative;
        background: #3d4f7c; color: #fff; cursor: pointer;
        -webkit-user-drag: none; user-select: none; touch-action: none;
    }
    .wi-plan__chip.is-session, .wi-plan__chip.is-meeting { gap: .28rem; }
    .wi-plan__chip-icon { font-size: .72rem; flex-shrink: 0; }
    .wi-plan__chip.is-ghost { opacity: .5; }
    .wi-plan__chip-title { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wi-plan__chip-off {
        flex-shrink: 0; border: 0; background: transparent; color: inherit; opacity: .75;
        line-height: 1; padding: 0 .1rem; font-size: .8rem; cursor: pointer;
    }
    .wi-plan__chip-off:hover { opacity: 1; }
    .wi-plan__hours { position: relative; }
    .wi-plan__hour {
        height: var(--hour-px); font-size: .62rem; font-weight: 500;
        color: rgba(226, 232, 240, .68);
        padding: .08rem .2rem 0 0; text-align: right;
        border-top: 1px solid rgba(255,255,255,.14);
        letter-spacing: .02em;
    }
    .wi-plan__col {
        position: relative; border-left: 1px solid rgba(255,255,255,.06); cursor: cell; user-select: none;
        background-image: repeating-linear-gradient(
            to bottom,
            rgba(255,255,255,.022) 0,
            rgba(255,255,255,.022) var(--hour-px),
            transparent var(--hour-px),
            transparent calc(var(--hour-px) * 2)
        );
    }
    .wi-plan__slot { height: calc(var(--hour-px) / 4); border-top: 1px solid rgba(255,255,255,.035); pointer-events: none; }
    .wi-plan__slot--hour { border-top-color: rgba(255,255,255,.14); }
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
        position: absolute; z-index: 2; border-radius: 8px; padding: 4px 7px 10px;
        overflow: hidden; color: #fff; cursor: pointer; box-sizing: border-box;
        background: #3d4f7c; border: 1px solid rgba(255,255,255,.12);
        box-shadow: 0 4px 10px rgba(0,0,0,.18);
        -webkit-user-drag: none; user-select: none; touch-action: none;
    }
    .wi-plan__event.is-source, .wi-plan__chip.is-source, .wi-plan__card.is-source,
    .wi-plan__event.is-resizing { opacity: .35; }
    .wi-plan__event.is-tight { padding: 3px 7px 6px; }
    .wi-plan__event.is-compact { padding: 0 6px; border-radius: 6px; }
    .wi-plan__event.is-compact.is-session .wi-plan__event-head { height: 100%; }
    .wi-plan__event.is-compact .wi-plan__event-title { line-height: 1.15; }
    .wi-plan__event.is-block { background: #3d4f7c; }
    .wi-plan__event.is-meeting {
        background: linear-gradient(135deg, #5b4aa8, #7c3aed);
        border-color: rgba(196, 181, 253, .25);
    }
    .wi-plan__event.is-session, .wi-plan__chip.is-session {
        background: rgba(18, 16, 10, .92);
        color: #fbbf24;
        border: 1.5px dashed rgba(245, 158, 11, .85);
        box-shadow: none;
    }
    .wi-plan__event.is-session-drop, .wi-plan__chip.is-session-drop {
        border-style: solid;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, .45), 0 8px 18px rgba(245, 158, 11, .2);
    }
    .wi-plan__chip.is-meeting { background: linear-gradient(135deg, #5b4aa8, #7c3aed); }
    .wi-plan__event.is-ghost { opacity: .55; }
    .wi-plan__event-head {
        display: flex; align-items: center; gap: .28rem; min-width: 0;
    }
    .wi-plan__event-head .wi-plan__event-title { flex: 1; min-width: 0; }
    .wi-plan__event-head i { flex-shrink: 0; font-size: .72rem; opacity: .95; }
    .wi-plan__session-count {
        flex-shrink: 0; font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: .58rem; font-weight: 600; letter-spacing: .02em;
        border: 1px solid rgba(251, 191, 36, .7); color: #fbbf24;
        border-radius: 999px; padding: .04rem .38rem; line-height: 1.25;
        background: rgba(245, 158, 11, .12);
    }
    .wi-plan__event-title {
        display: block; font-size: .64rem; font-weight: 600; line-height: 1.2;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .wi-plan__event-time { display: block; font-size: .58rem; opacity: .88; line-height: 1.2; margin-top: 1px; }
    .wi-plan__event.is-session .wi-plan__event-time { color: #fcd34d; opacity: 1; }
    .wi-plan__grip, .wi-plan__resize {
        opacity: 0;
        transition: opacity .12s ease;
    }
    .wi-plan__event:hover .wi-plan__grip,
    .wi-plan__event:hover .wi-plan__resize,
    .wi-plan__event:focus-within .wi-plan__grip,
    .wi-plan__event:focus-within .wi-plan__resize,
    .wi-plan__chip:hover .wi-plan__grip,
    .wi-plan__chip:focus-within .wi-plan__grip,
    .wi-plan__queue .tg-dt-card:hover .wi-plan__grip,
    .wi-plan__queue .tg-dt-card:focus-within .wi-plan__grip {
        opacity: 1;
    }
    @media (hover: none) {
        .wi-plan__grip, .wi-plan__resize { opacity: .9; }
    }
    .wi-plan__grip {
        position: absolute; left: 0; top: 0; bottom: 0; width: 14px;
        z-index: 5; cursor: grab;
    }
    .wi-plan__grip::before {
        content: '';
        position: absolute; left: 4px; top: 50%;
        width: 2px; height: 2px; margin-top: -5px; border-radius: 50%;
        background: rgba(255,255,255,.9);
        box-shadow:
            4px 0 0 rgba(255,255,255,.9),
            0 4px 0 rgba(255,255,255,.9),
            4px 4px 0 rgba(255,255,255,.9),
            0 8px 0 rgba(255,255,255,.9),
            4px 8px 0 rgba(255,255,255,.9);
    }
    .wi-plan__resize {
        position: absolute; right: 0; bottom: 0; width: 14px; height: 14px;
        cursor: nwse-resize; z-index: 4;
    }
    .wi-plan__resize::after {
        content: '';
        position: absolute; right: 2px; bottom: 2px;
        border: 5px solid transparent;
        border-right-color: rgba(255,255,255,.8);
        border-bottom-color: rgba(255,255,255,.8);
    }
    .wi-plan__chip .wi-plan__grip { border-radius: 4px 0 0 4px; }
    .wi-plan__pop {
        position: fixed; z-index: 41; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: min(42rem, calc(100vw - 2rem)); max-height: min(90vh, 820px);
        background: rgba(13, 18, 30, .96); border: 1px solid var(--glass-border); border-radius: 14px;
        box-shadow: 0 16px 40px rgba(0,0,0,.4);
        display: flex; flex-direction: column; min-width: 0; min-height: 0;
        overflow: hidden;
        box-sizing: border-box;
    }
    .wi-plan__pop.is-card-pop {
        width: min(44rem, calc(100vw - 2rem));
    }
    .wi-plan__pop.is-procedure-pop,
    .wi-plan__pop.is-approval-pop {
        width: min(56rem, calc(100vw - 2rem));
    }
    .wi-plan__pop.is-session-pop {
        width: min(26rem, calc(100vw - 2rem));
        border-color: rgba(245, 158, 11, .35);
    }
    .wi-plan__pop-backdrop {
        position: fixed; inset: 0; z-index: 40; background: rgba(0,0,0,.45);
    }
    .wi-plan__pop-head {
        display: flex; align-items: center; gap: .55rem; flex-shrink: 0; flex-wrap: wrap; min-width: 0;
        padding: .8rem 1rem .7rem;
        border-bottom: 1px solid var(--glass-border);
        background: rgba(13, 18, 30, .98);
        z-index: 2;
    }
    .wi-plan__pop-head-copy {
        display: flex; flex-direction: column; gap: .08rem; min-width: 0;
    }
    .wi-plan__pop-head-kicker {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .62rem; letter-spacing: .04em; text-transform: uppercase;
        color: var(--text-muted);
    }
    .wi-plan__pop-head-time { color: var(--text-main); font-size: .85rem; font-weight: 600; }
    .wi-plan__pop-head-type {
        display: inline-flex; align-items: center; gap: .28rem; flex-shrink: 0;
        font-size: .68rem; color: var(--text-muted);
        border: 1px solid var(--glass-border); border-radius: 999px; padding: .12rem .5rem;
    }
    .wi-plan__pop-head-title {
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        font-size: .82rem; color: var(--text-main);
    }
    .wi-plan__pop-rename {
        display: flex; align-items: center; gap: .35rem; min-width: 0; flex: 1;
    }
    .wi-plan__pop-rename .form-control { min-width: 8rem; }
    .wi-plan__pop-rename-btn { padding: .15rem .4rem; line-height: 1; }
    .wi-plan__pop-member-nav {
        display: inline-flex; align-items: center; gap: .4rem; flex-wrap: wrap;
        font-size: .72rem; color: var(--text-muted);
    }
    .wi-plan__pop-head-actions { margin-left: auto; display: flex; align-items: center; gap: .35rem; flex-shrink: 0; }
    .wi-plan__pop-close { line-height: 1; padding: .1rem .45rem; }
    .wi-plan__pop-body {
        overflow-x: hidden; overflow-y: auto; min-width: 0; min-height: 0; flex: 1;
        padding: .85rem 1rem 1rem;
        overscroll-behavior: contain;
    }
    .wi-plan__pop-sheet {
        min-width: 0; max-width: 100%;
        container-type: inline-size;
    }
    .wi-plan__pop-sheet > .card,
    .wi-plan__pop-sheet .row,
    .wi-plan__pop-sheet [class*="col-"] {
        min-width: 0; max-width: 100%;
    }
    .wi-plan__pop-sheet .row {
        --bs-gutter-x: 1rem;
        margin-left: 0;
        margin-right: 0;
    }
    @container (max-width: 36rem) {
        .wi-plan__pop-sheet .row > [class*="col-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }
    .wi-plan__pop-members { overflow: visible; }
    .wi-plan__pop-empty { font-size: .72rem; color: var(--text-muted); margin: 0; }
    .wi-plan__pop-card { cursor: default; margin-bottom: .4rem; }
    .wi-plan__pop-card:last-child { margin-bottom: 0; }
    .wi-plan__pop-card .wi-plan__card-title {
        appearance: none; background: none; border: 0; padding: 0; margin: 0;
        color: var(--text-main); text-decoration: none; text-align: left;
        font: inherit; font-weight: 600; cursor: pointer;
    }
    .wi-plan__pop-card .wi-plan__card-title:hover { color: var(--primary); }
    .wi-plan__pop-card .wi-plan__chip-off {
        color: var(--text-muted); font-size: 1rem; margin-top: .05rem;
    }
    .wi-plan__pop-card .wi-plan__chip-off:hover { color: var(--text-main); }
    .wi-plan__pop-fallback-title { font-size: .95rem; font-weight: 600; color: var(--text-main); margin: 0 0 .45rem; }
    .wi-plan__pop-fallback-body { font-size: .8rem; color: var(--text-muted); white-space: pre-wrap; margin: 0 0 .75rem; }
    .wi-plan__composer-hint { font-size: .68rem; color: var(--text-muted); margin: .4rem 0 0; line-height: 1.35; }
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
    .wi-plan-dragging, .wi-plan-dragging * { cursor: grabbing !important; user-select: none !important; }
    .wi-plan.is-dragging, .wi-plan-dragging { touch-action: none; }
    .wi-plan.is-dragging .wi-plan__event,
    .wi-plan.is-dragging .wi-plan__chip,
    .wi-plan.is-dragging .wi-plan__flag,
    .wi-plan.is-dragging .wi-plan__card { pointer-events: none; }
    .wi-plan.is-dragging.is-queue-drag .wi-plan__event[data-plan-session],
    .wi-plan.is-dragging.is-queue-drag .wi-plan__chip[data-plan-session] { pointer-events: auto; cursor: copy; }
    .wi-plan__allday-cell.is-drop, .wi-plan__queue.is-drop {
        outline: 2px dashed rgba(96, 165, 250, .85); outline-offset: -2px;
    }
    .wi-plan__float {
        position: fixed; z-index: 80; pointer-events: none; max-width: 16rem;
        transform: translate(14px, 10px); padding: .28rem .55rem; border-radius: 8px;
        background: linear-gradient(135deg, var(--primary), var(--accent)); color: #fff;
        font-size: .72rem; font-weight: 600; box-shadow: 0 10px 24px rgba(0,0,0,.35);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .wi-plan__float.is-copy { box-shadow: 0 0 0 2px #fff, 0 10px 24px rgba(0,0,0,.35); }
    .wi-plan__undo {
        position: fixed; z-index: 70; left: 50%; bottom: 1.4rem; transform: translateX(-50%);
        display: flex; align-items: center; gap: .65rem;
        max-width: min(32rem, calc(100vw - 24px));
        padding: .55rem .7rem .55rem .95rem;
        background: rgba(20, 24, 34, .96); color: var(--text-main);
        border: 1px solid var(--glass-border); border-radius: 10px;
        box-shadow: 0 16px 40px rgba(0,0,0,.45);
        font-size: .8rem;
    }
    .wi-plan__undo span { min-width: 0; }
    .wi-plan__undo-action {
        flex-shrink: 0; border: 0; background: transparent; padding: 0;
        color: #93c5fd; font-weight: 600; font-size: .8rem;
    }
    .wi-plan__undo-action:hover { color: #fff; }
    .wi-plan__undo-close {
        flex-shrink: 0; border: 0; background: transparent; color: var(--text-muted);
        line-height: 1; font-size: 1.1rem; padding: 0 .15rem;
    }
    .wi-plan__undo-close:hover { color: var(--text-main); }
    [x-cloak] { display: none !important; }
    @media (max-width: 991.98px) {
        .wi-plan__body { grid-template-columns: 1fr; }
        .wi-plan__queue { max-height: 12rem; }
        .wi-plan__board { max-height: calc(100vh - 18rem); }
    }
</style>
</div>
