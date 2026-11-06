export function registerProjectDemandTimeline(Alpine) {
    Alpine.data('demandTimeline', () => ({
        dragging: false,
        laneKey: null,
        barId: null,
        mode: null,
        edge: null,
        anchor: null,
        moving: null,
        bound: null,
        originStart: null,
        originEnd: null,
        tip: '',
        tipX: 0,
        tipY: 0,
        moved: false,
        selecting: false,
        selectId: null,
        originX: 0,
        originY: 0,
        track: null,
        weekWidth: 56,
        moveDelta: 0,
        boundEdge: null,
        boundLeftPx: null,
        projectStartIso: null,
        projectEndIso: null,
        _move: null,
        _up: null,
        _down: null,

        init() {
            this.root = this.$el;
            this.syncAxis();
            this._down = (event) => this.onPointerDown(event);
            this.root.addEventListener('pointerdown', this._down);
            this.$nextTick(() => this.centerOnToday());
        },

        syncAxis() {
            this.axis = this.root?.dataset?.start || this.axis;
            this.weeks = Number(this.root?.dataset?.weeks || this.weeks || 1);
            this.weekWidth = parseFloat(getComputedStyle(this.root).getPropertyValue('--atl-day')) || 56;
            this.projectStartIso = this.root?.dataset?.projectStart || this.projectStartIso;
            this.projectEndIso = this.root?.dataset?.projectEnd || this.projectEndIso;
        },

        boundMarkerStyle(edge) {
            if (this.dragging && this.boundEdge === edge && this.boundLeftPx != null) {
                return `left:${this.boundLeftPx}px`;
            }
            return null;
        },

        centerOnToday() {
            const body = this.$refs.scroller;
            const today = this.root.querySelector('.pdt-marker--today');
            if (!body || !today) {
                return;
            }
            const chart = Math.max(160, body.clientWidth - 140);
            body.scrollLeft = Math.max(0, today.offsetLeft - chart * 0.25);
        },

        get rubberStyle() {
            if (!this.dragging || this.mode === 'bound') {
                return {};
            }
            if (!this.anchor || !this.moving) {
                return {};
            }
            if (this.mode === 'move') {
                const startIdx = this.indexOf(this.originStart) + this.moveDelta;
                const endIdx = this.indexOf(this.originEnd) + this.moveDelta;
                const left = Math.min(startIdx, endIdx) * this.weekWidth;
                const width = (Math.abs(endIdx - startIdx) + 1) * this.weekWidth;

                return { left: `${left}px`, width: `${width}px` };
            }
            const start = this.anchor < this.moving ? this.anchor : this.moving;
            const end = this.anchor < this.moving ? this.moving : this.anchor;
            const left = this.indexOf(start) * this.weekWidth;
            const width = (this.indexOf(end) - this.indexOf(start) + 1) * this.weekWidth;

            return { left: `${left}px`, width: `${width}px` };
        },

        get projectBandStyle() {
            const startIso = (this.dragging && this.boundEdge === 'start' && this.moving)
                ? this.moving
                : this.projectStartIso;
            const endIso = (this.dragging && this.boundEdge === 'end' && this.moving)
                ? this.moving
                : this.projectEndIso;
            if (!startIso || !endIso) {
                return {};
            }
            const left = this.indexOf(startIso) * this.weekWidth;
            const right = (this.indexOf(endIso) + 1) * this.weekWidth;

            return { left: `${left}px`, width: `${Math.max(this.weekWidth, right - left)}px` };
        },

        get tipStyle() {
            return `left:${this.tipX}px;top:${this.tipY}px`;
        },

        onPointerDown(event) {
            if (event.button !== 0 || !this.canGesture()) {
                return;
            }
            const bound = event.target.closest?.('.pdt-bound');
            if (bound && bound.dataset.draggable === '1') {
                this.onBoundDown(event, bound);
                return;
            }
            const handle = event.target.closest?.('.atl-handle');
            if (handle) {
                this.onHandleDown(event, handle);
                return;
            }
            const bar = event.target.closest?.('.atl-bar');
            if (bar) {
                const grip = this.edgeHandle(bar, event.clientX);
                if (grip) {
                    this.onHandleDown(event, grip);
                    return;
                }
                this.onMoveDown(event, bar);
                return;
            }
            if (event.target.closest?.('.atl-track') && !event.target.closest?.('.pdt-agg')) {
                this.onTrackDown(event);
            }
        },

        onBoundDown(event, bound) {
            const track = bound.closest('.atl-track') || this.root.querySelector('.pdt-agg__track');
            if (!track) {
                return;
            }
            const edge = bound.dataset.edge;
            if (edge !== 'start' && edge !== 'end') {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            this.syncAxis();
            this.detach();
            this.dragging = true;
            this.mode = 'bound';
            this.boundEdge = edge;
            this.track = track;
            this.moved = false;
            this.originX = event.clientX;
            this.originY = event.clientY;
            this.weekWidth = parseFloat(getComputedStyle(this.root).getPropertyValue('--atl-day')) || 56;
            this.anchor = edge === 'start' ? this.projectStartIso : this.projectEndIso;
            this.moving = this.weekAt(track, event.clientX);
            this.boundLeftPx = this.indexOf(this.moving) * this.weekWidth + (edge === 'end' ? this.weekWidth - 2 : 0);
            this.paintTip(event);
            this._move = (pointer) => this.trackPointer(pointer);
            this._up = (pointer) => this.endPointer(pointer);
            window.addEventListener('pointermove', this._move);
            window.addEventListener('pointerup', this._up);
            window.addEventListener('pointercancel', this._up);
        },

        edgeHandle(bar, clientX) {
            if (bar.classList.contains('is-pending') || !bar.querySelector('.atl-handle')) {
                return null;
            }
            const rect = bar.getBoundingClientRect();
            const zone = rect.width < 48 ? 8 : 18;
            if (clientX - rect.left <= zone) {
                return bar.querySelector('.atl-handle--start');
            }
            if (rect.right - clientX <= zone) {
                return bar.querySelector('.atl-handle--end');
            }

            return null;
        },

        onTrackDown(event) {
            if (event.target.closest('.atl-bar')) {
                return;
            }
            const lane = event.target.closest('.atl-lane');
            const track = event.target.closest('.atl-track');
            if (!lane || !track || lane.dataset.canCreate !== '1') {
                return;
            }
            const week = this.weekAt(track, event.clientX);
            let gaps = [];
            try {
                gaps = JSON.parse(lane.dataset.gaps || '[]');
            } catch (error) {
                gaps = [];
            }
            const gap = gaps.find((item) => week >= item.start && week <= item.end);
            if (!gap) {
                return;
            }
            this.begin(event, track, 'draw', lane.dataset.lane, null, week, week, {
                start: gap.start,
                end: gap.end,
            });
        },

        onMoveDown(event, bar) {
            const lane = bar.closest('.atl-lane');
            const track = bar.closest('.atl-track');
            if (!lane || !track || !bar.dataset.id || bar.classList.contains('is-pending') || bar.dataset.locked === '1') {
                return;
            }
            if (!lane.querySelector('.atl-handle')) {
                return;
            }
            event.preventDefault();
            this.syncAxis();
            this.detach();
            this.dragging = true;
            this.mode = 'move';
            this.track = track;
            this.laneKey = lane.dataset.lane;
            this.barId = Number(bar.dataset.id);
            this.originStart = bar.dataset.start;
            this.originEnd = bar.dataset.end || bar.dataset.start;
            this.bound = { start: bar.dataset.min, end: bar.dataset.max };
            this.moveDelta = 0;
            this.moved = false;
            this.originX = event.clientX;
            this.originY = event.clientY;
            this.selecting = true;
            this.selectId = this.barId;
            this.weekWidth = parseFloat(getComputedStyle(this.root).getPropertyValue('--atl-day')) || 56;
            this.anchor = this.originStart;
            this.moving = this.originEnd;
            this.paintTip(event);
            this._move = (pointer) => this.trackPointer(pointer);
            this._up = (pointer) => this.endPointer(pointer);
            window.addEventListener('pointermove', this._move);
            window.addEventListener('pointerup', this._up);
            window.addEventListener('pointercancel', this._up);
        },

        onHandleDown(event, handle) {
            const bar = handle.closest('.atl-bar');
            const lane = handle.closest('.atl-lane');
            const track = handle.closest('.atl-track');
            if (!bar || !lane || !track) {
                return;
            }
            const edge = handle.dataset.edge;
            const start = bar.dataset.start;
            const end = bar.dataset.end || start;
            this.originStart = start;
            this.originEnd = end;
            this.edge = edge;
            this.begin(event, track, 'resize', lane.dataset.lane, Number(bar.dataset.id), edge === 'start' ? end : start, edge === 'start' ? start : end, {
                start: bar.dataset.min,
                end: bar.dataset.max,
            });
        },

        begin(event, track, mode, lane, barId, anchor, moving, bound) {
            event.preventDefault();
            this.syncAxis();
            this.detach();
            this.dragging = true;
            this.mode = mode;
            this.track = track;
            this.laneKey = lane;
            this.barId = barId;
            this.anchor = anchor;
            this.moving = moving;
            this.bound = bound;
            this.moved = false;
            this.selecting = false;
            this.originX = event.clientX;
            this.originY = event.clientY;
            this.weekWidth = parseFloat(getComputedStyle(this.root).getPropertyValue('--atl-day')) || 56;
            this.paintTip(event);
            this._move = (pointer) => this.trackPointer(pointer);
            this._up = (pointer) => this.endPointer(pointer);
            window.addEventListener('pointermove', this._move);
            window.addEventListener('pointerup', this._up);
            window.addEventListener('pointercancel', this._up);
        },

        trackPointer(event) {
            if (!this.dragging) {
                return;
            }
            if (Math.hypot(event.clientX - this.originX, event.clientY - (this.originY || event.clientY)) > 4) {
                this.moved = true;
            }

            if (this.mode === 'bound') {
                let week = this.weekAt(this.track, event.clientX);
                const startMon = this.mondayOf(this.projectStartIso);
                const endMon = this.mondayOf(this.projectEndIso);
                if (this.boundEdge === 'start') {
                    // Start musi zostać przed końcem (min. 1 tydzień).
                    const maxStart = this.addWeeks(endMon, -1);
                    if (week > maxStart) week = maxStart;
                } else {
                    const minEnd = this.addWeeks(startMon, 1);
                    if (week < minEnd) week = minEnd;
                }
                this.moving = week;
                this.boundLeftPx = this.indexOf(week) * this.weekWidth
                    + (this.boundEdge === 'end' ? this.weekWidth - 2 : 0);
                this.paintTip(event);
                this.nudgeScroll(event);

                return;
            }

            if (this.mode === 'move') {
                const originIdx = this.indexOf(this.originStart);
                const hoverIdx = this.indexOf(this.weekAt(this.track, event.clientX));
                let delta = hoverIdx - originIdx;
                const span = this.indexOf(this.originEnd) - this.indexOf(this.originStart);
                const minIdx = this.indexOf(this.bound.start);
                const maxIdx = this.indexOf(this.mondayOf(this.bound.end));
                if (originIdx + delta < minIdx) {
                    delta = minIdx - originIdx;
                }
                if (originIdx + span + delta > maxIdx) {
                    delta = maxIdx - (originIdx + span);
                }
                this.moveDelta = delta;
                this.moving = this.addWeeks(this.originEnd, delta);
                this.anchor = this.addWeeks(this.originStart, delta);
                this.paintTip(event);
                this.nudgeScroll(event);

                return;
            }

            let week = this.weekAt(this.track, event.clientX);
            const boundStart = this.mondayOf(this.bound.start);
            const boundEndMonday = this.mondayOf(this.bound.end);
            if (week < boundStart) {
                week = boundStart;
            }
            if (week > boundEndMonday) {
                week = boundEndMonday;
            }
            if (this.edge === 'start' && week > this.mondayOf(this.anchor)) {
                week = this.mondayOf(this.anchor);
            }
            if (this.edge === 'end' && week < this.mondayOf(this.anchor)) {
                week = this.mondayOf(this.anchor);
            }
            if (this.mode === 'draw' && week !== this.anchor) {
                this.moved = true;
            }
            this.moving = week;
            this.paintTip(event);
            this.nudgeScroll(event);
        },

        endPointer(event) {
            if (!this.dragging) {
                return;
            }
            const id = this.barId;
            const lane = this.laneKey;
            const mode = this.mode;
            const edge = this.edge;
            const boundEdge = this.boundEdge;
            const moved = this.moved;
            const originStart = this.originStart;
            const originEnd = this.originEnd;
            const moveDelta = this.moveDelta;
            const moving = this.moving;
            let start = this.anchor < this.moving ? this.anchor : this.moving;
            let end = this.anchor < this.moving ? this.moving : this.anchor;

            if (mode === 'resize' && edge === 'start') {
                start = this.mondayOf(this.moving);
                end = this.sundayOf(originEnd);
            }
            if (mode === 'resize' && edge === 'end') {
                start = this.mondayOf(originStart);
                end = this.sundayOf(this.moving);
            }
            if (mode === 'move') {
                start = this.addWeeks(originStart, moveDelta);
                end = this.sundayOf(this.addWeeks(originEnd, moveDelta));
            }
            if (mode === 'draw') {
                start = this.mondayOf(start);
                end = this.sundayOf(end);
            }

            this.detach();
            this.dragging = false;
            this.boundEdge = null;
            this.boundLeftPx = null;

            if (mode === 'bound') {
                if (!moved) {
                    return;
                }
                const menu = this.menuAnchor(event);
                const date = boundEdge === 'start'
                    ? this.mondayOf(moving)
                    : this.sundayOf(moving);
                this.$wire.proposeBound(boundEdge, date, menu.left, menu.top, menu.above);

                return;
            }

            if (mode === 'draw' && !moved) {
                return;
            }

            if (mode === 'move' && !moved) {
                const menu = this.menuAnchor(event);
                this.$wire.selectBar(id, menu.left, menu.top, menu.above);

                return;
            }

            if (mode === 'resize' && edge === 'start' && start === originStart) {
                return;
            }
            if (mode === 'resize' && edge === 'end' && this.mondayOf(end) === this.mondayOf(originEnd)) {
                return;
            }
            if (mode === 'move' && moveDelta === 0) {
                return;
            }

            const menu = this.menuAnchor(event);
            if (mode === 'draw') {
                this.$wire.proposeDraw(lane, this.mondayOf(start), this.sundayOf(end), menu.left, menu.top, menu.above);
            } else {
                this.$wire.propose(id, this.mondayOf(start), this.sundayOf(end), menu.left, menu.top, menu.above);
            }
        },

        menuAnchor(event) {
            const rect = this.root.getBoundingClientRect();
            const menuWidth = 250;
            let left = (event?.clientX ?? rect.left + 24) - rect.left;
            const min = menuWidth / 2 + 8;
            const max = Math.max(min, rect.width - menuWidth / 2 - 8);
            if (left < min) left = min;
            if (left > max) left = max;
            const clientY = event?.clientY ?? rect.top + 24;
            const above = clientY > 160;

            return {
                left,
                top: clientY - rect.top + (above ? -8 : 14),
                above,
            };
        },

        paintTip(event) {
            if (this.mode === 'bound') {
                const label = this.boundEdge === 'start' ? 'Start' : 'Koniec';
                const iso = this.boundEdge === 'start'
                    ? this.mondayOf(this.moving)
                    : this.sundayOf(this.moving);
                this.tip = `${label} ${this.formatPl(iso)}`;
            } else {
                let start;
                let end;
                if (this.mode === 'move') {
                    start = this.addWeeks(this.originStart, this.moveDelta);
                    end = this.addWeeks(this.originEnd, this.moveDelta);
                } else {
                    start = this.anchor < this.moving ? this.anchor : this.moving;
                    end = this.anchor < this.moving ? this.moving : this.anchor;
                }
                this.tip = `${this.formatPl(this.mondayOf(start))} – ${this.formatPl(this.sundayOf(end))}`;
            }
            const rect = this.root.getBoundingClientRect();
            let x = event.clientX - rect.left + 12;
            if (x > rect.width - 130) {
                x = event.clientX - rect.left - 118;
            }
            this.tipX = x;
            this.tipY = event.clientY - rect.top + 16;
        },

        nudgeScroll(event) {
            const scroller = this.$refs.scroller;
            if (!scroller) return;
            const rect = scroller.getBoundingClientRect();
            if (event.clientX > rect.right - 36) {
                scroller.scrollLeft += 24;
            } else if (event.clientX < rect.left + 36) {
                scroller.scrollLeft -= 24;
            }
        },

        detach() {
            if (this._move) window.removeEventListener('pointermove', this._move);
            if (this._up) {
                window.removeEventListener('pointerup', this._up);
                window.removeEventListener('pointercancel', this._up);
            }
            this._move = null;
            this._up = null;
        },

        destroy() {
            this.root?.removeEventListener('pointerdown', this._down);
            this.detach();
        },

        canGesture() {
            return window.matchMedia('(pointer: fine)').matches || window.innerWidth >= 992;
        },

        weekAt(track, clientX) {
            const rect = track.getBoundingClientRect();
            const index = Math.floor((clientX - rect.left) / this.weekWidth);
            const clamped = Math.max(0, Math.min(this.weeks - 1, index));

            return this.addWeeks(this.axis, clamped);
        },

        indexOf(iso) {
            const monday = this.mondayOf(iso);
            const [y, m, d] = monday.split('-').map(Number);
            const [Y, M, D] = this.axis.split('-').map(Number);
            const days = Math.round((Date.UTC(y, m - 1, d) - Date.UTC(Y, M - 1, D)) / 86400000);

            return Math.floor(days / 7);
        },

        addWeeks(iso, weeks) {
            const monday = this.mondayOf(iso);
            const [y, m, d] = monday.split('-').map(Number);
            const date = new Date(Date.UTC(y, m - 1, d));
            date.setUTCDate(date.getUTCDate() + (weeks * 7));
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const day = String(date.getUTCDate()).padStart(2, '0');

            return `${date.getUTCFullYear()}-${month}-${day}`;
        },

        mondayOf(iso) {
            const [y, m, d] = iso.split('-').map(Number);
            const date = new Date(Date.UTC(y, m - 1, d));
            const day = date.getUTCDay(); // 0 Sun .. 6 Sat
            const diff = day === 0 ? -6 : 1 - day;
            date.setUTCDate(date.getUTCDate() + diff);
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const dayNum = String(date.getUTCDate()).padStart(2, '0');

            return `${date.getUTCFullYear()}-${month}-${dayNum}`;
        },

        sundayOf(iso) {
            const monday = this.mondayOf(iso);
            const [y, m, d] = monday.split('-').map(Number);
            const date = new Date(Date.UTC(y, m - 1, d));
            date.setUTCDate(date.getUTCDate() + 6);
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const day = String(date.getUTCDate()).padStart(2, '0');

            return `${date.getUTCFullYear()}-${month}-${day}`;
        },

        formatPl(iso) {
            const monday = this.mondayOf(iso);
            const [, month, day] = monday.split('-');
            const week = this.isoWeek(monday);

            return `T${week} · ${Number(day)}.${month}`;
        },

        isoWeek(iso) {
            const [y, m, d] = this.mondayOf(iso).split('-').map(Number);
            const date = new Date(Date.UTC(y, m - 1, d));
            // ISO: Thursday-based week number.
            const dayNum = date.getUTCDay() || 7;
            date.setUTCDate(date.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
            return Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
        },
    }));
}
