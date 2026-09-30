export function registerAssignmentTimeline(Alpine) {
    Alpine.data('assignmentTimeline', () => ({
        dragging: false,
        laneKey: null,
        barId: null,
        mode: null,
        edge: null,
        anchor: null,
        moving: null,
        bound: null,
        keepOpen: false,
        employeeId: null,
        originStart: null,
        originEnd: null,
        tip: '',
        tipX: 0,
        tipY: 0,
        moved: false,
        selecting: false,
        selectId: null,
        selectEmployee: null,
        selectLane: null,
        originX: 0,
        originY: 0,
        track: null,
        dayWidth: 22,
        _move: null,
        _up: null,
        _down: null,

        init() {
            this.root = this.$el;
            this.axis = this.$el.dataset.start;
            this.days = Number(this.$el.dataset.days || 1);
            this._down = (event) => this.onPointerDown(event);
            this.root.addEventListener('pointerdown', this._down);
            this.$nextTick(() => this.bindAxisScroll());
        },

        bindAxisScroll() {
            const body = this.$refs.scroller;
            if (!body || this._axisScroll) {
                return;
            }
            this._axisScroll = true;
            const today = this.root.querySelector('.rax-line--today');
            const side = this.$refs.side;
            if (today) {
                const chart = Math.max(160, body.clientWidth - (side?.offsetWidth || 0));
                body.scrollLeft = Math.max(0, today.offsetLeft - chart * 0.22);
            }
        },

        get rubberStyle() {
            if (!this.dragging || !this.anchor || !this.moving) {
                return {};
            }
            const start = this.anchor < this.moving ? this.anchor : this.moving;
            const end = this.anchor < this.moving ? this.moving : this.anchor;
            const left = this.indexOf(start) * this.dayWidth;
            const width = (this.indexOf(end) - this.indexOf(start) + 1) * this.dayWidth;

            return { left: `${left}px`, width: `${width}px` };
        },

        get tipStyle() {
            return `left:${this.tipX}px;top:${this.tipY}px`;
        },

        onPointerDown(event) {
            if (event.button !== 0 || !this.canGesture()) {
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
                this.armSelect(event, bar);
                return;
            }
            if (event.target.closest?.('.atl-track')) {
                this.onTrackDown(event);
            }
        },

        edgeHandle(bar, clientX) {
            if (bar.classList.contains('is-pending') || !bar.querySelector('.atl-handle')) {
                return null;
            }
            const rect = bar.getBoundingClientRect();
            const zone = rect.width < 48 ? 6 : 16;
            if (clientX - rect.left <= zone) {
                return bar.querySelector('.atl-handle--start');
            }
            if (rect.right - clientX <= zone) {
                return bar.querySelector('.atl-handle--end');
            }

            return null;
        },

        armSelect(event, bar) {
            const lane = bar.closest('.atl-lane');
            if (!lane || lane.dataset.canDelete !== '1' || !bar.dataset.id || bar.classList.contains('is-pending') || bar.dataset.locked === '1') {
                return;
            }
            event.preventDefault();
            this.detach();
            this.selecting = true;
            this.selectId = Number(bar.dataset.id);
            this.selectEmployee = lane.dataset.employee ? Number(lane.dataset.employee) : null;
            this.selectLane = lane.dataset.lane || null;
            this.originX = event.clientX;
            this.originY = event.clientY;
            this._up = (pointer) => this.finishSelect(pointer);
            window.addEventListener('pointerup', this._up);
            window.addEventListener('pointercancel', this._up);
        },

        finishSelect(event) {
            if (!this.selecting) {
                return;
            }
            const moved = Math.hypot(event.clientX - this.originX, event.clientY - this.originY) > 4;
            const employeeId = this.selectEmployee;
            const lane = this.selectLane;
            const id = this.selectId;
            this.selecting = false;
            this.detach();
            if (moved || !id) {
                return;
            }
            const menu = this.menuAnchor(event);
            if (employeeId) {
                this.$wire.selectBar(employeeId, id, menu.left, menu.top, menu.above);
            } else if (lane) {
                this.$wire.selectBar(lane, id, menu.left, menu.top, menu.above);
            }
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
            const day = this.dayAt(track, event.clientX);
            const gaps = JSON.parse(lane.dataset.gaps || '[]');
            const gap = gaps.find((item) => day >= item.start && day <= item.end);
            if (!gap) {
                return;
            }
            this.begin(event, track, 'draw', lane.dataset.lane, null, day, day, gap, false);
        },

        onHandleDown(event, handle = event.target.closest('.atl-handle')) {
            if (!handle) {
                return;
            }
            const bar = handle.closest('.atl-bar');
            const lane = handle.closest('.atl-lane');
            const track = handle.closest('.atl-track');
            if (!bar || !lane || !track) {
                return;
            }
            const edge = handle.dataset.edge;
            const open = bar.dataset.open === '1';
            const start = bar.dataset.start;
            const visualEnd = bar.dataset.end || this.lastDay();
            const anchor = edge === 'start' ? visualEnd : start;
            const moving = edge === 'start' ? start : visualEnd;
            this.originStart = start;
            this.originEnd = bar.dataset.end || null;
            this.edge = edge;
            this.begin(event, track, 'resize', lane.dataset.lane, Number(bar.dataset.id), anchor, moving, {
                start: bar.dataset.min,
                end: bar.dataset.max,
            }, edge === 'start' && open);
        },

        begin(event, track, mode, lane, barId, anchor, moving, bound, keepOpen) {
            event.preventDefault();
            if (event.pointerId != null) {
                try {
                    this.root.setPointerCapture(event.pointerId);
                } catch (error) {
                    // Syntetyczny albo już puszczony wskaźnik nie daje się przechwycić.
                }
            }
            this.detach();
            this.dragging = true;
            this.mode = mode;
            this.track = track;
            this.laneKey = lane;
            this.barId = barId;
            const laneEl = track.closest('.atl-lane');
            this.employeeId = laneEl?.dataset.employee ? Number(laneEl.dataset.employee) : null;
            this.anchor = anchor;
            this.moving = moving;
            this.bound = bound;
            this.keepOpen = keepOpen;
            this.moved = false;
            this.originX = event.clientX;
            this.dayWidth = parseFloat(getComputedStyle(this.root).getPropertyValue('--atl-day')) || 22;
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
            if (Math.abs(event.clientX - this.originX) > 3) {
                this.moved = true;
            }
            let day = this.dayAt(this.track, event.clientX);
            if (day < this.bound.start) {
                day = this.bound.start;
            }
            if (day > this.bound.end) {
                day = this.bound.end;
            }
            if (this.mode === 'resize' && this.edge === 'start' && day > this.anchor) {
                day = this.anchor;
            }
            if (this.mode === 'resize' && this.edge === 'end' && day < this.anchor) {
                day = this.anchor;
            }
            if (this.mode === 'draw' && day !== this.anchor) {
                this.moved = true;
            }
            this.moving = day;
            this.paintTip(event);
            this.nudgeScroll(event);
        },

        endPointer(event) {
            if (!this.dragging) {
                return;
            }
            const lane = this.laneKey;
            const id = this.barId;
            const employeeId = this.employeeId;
            const keepOpen = this.keepOpen;
            const mode = this.mode;
            const edge = this.edge;
            const moved = this.moved;
            const originStart = this.originStart;
            const originEnd = this.originEnd;
            let start = this.anchor < this.moving ? this.anchor : this.moving;
            let end = this.anchor < this.moving ? this.moving : this.anchor;
            if (mode === 'resize' && edge === 'start') {
                start = this.moving;
                end = originEnd;
            }
            if (mode === 'resize' && edge === 'end') {
                start = originStart;
                end = this.moving;
            }
            this.detach();
            this.dragging = false;

            if (mode === 'draw' && !moved) {
                return;
            }
            if (mode === 'resize' && edge === 'start' && start === originStart) {
                return;
            }
            if (mode === 'resize' && edge === 'end' && end === (originEnd || this.lastDay())) {
                return;
            }

            if (employeeId) {
                const menu = this.menuAnchor(event);
                this.$wire.propose(employeeId, id, start, end, menu.left, menu.top, menu.above);
            } else {
                const menu = this.menuAnchor(event);
                this.$wire.propose(lane, id, start, keepOpen ? null : end, keepOpen, menu.left, menu.top, menu.above);
            }
        },

        menuAnchor(event) {
            const rect = this.root.getBoundingClientRect();
            const menuWidth = 250;
            let left = (event?.clientX ?? rect.left + 24) - rect.left;
            const min = menuWidth / 2 + 8;
            const max = Math.max(min, rect.width - menuWidth / 2 - 8);
            if (left < min) {
                left = min;
            }
            if (left > max) {
                left = max;
            }
            const clientY = event?.clientY ?? rect.top + 24;
            const above = clientY > 160;

            return {
                left,
                top: clientY - rect.top + (above ? -8 : 14),
                above,
            };
        },

        paintTip(event) {
            const start = this.anchor < this.moving ? this.anchor : this.moving;
            const end = this.anchor < this.moving ? this.moving : this.anchor;
            this.tip = `${this.formatPl(start)} – ${this.keepOpen ? 'otwarte' : this.formatPl(end)}`;
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
            if (!scroller) {
                return;
            }
            const rect = scroller.getBoundingClientRect();
            if (event.clientX > rect.right - 36) {
                scroller.scrollLeft += 18;
            } else if (event.clientX < rect.left + 36) {
                scroller.scrollLeft -= 18;
            }
        },

        detach() {
            if (this._move) {
                window.removeEventListener('pointermove', this._move);
            }
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

        axisStart() {
            return this.axis;
        },

        dayCount() {
            return this.days;
        },

        lastDay() {
            return this.addDays(this.axisStart(), this.dayCount() - 1);
        },

        dayAt(track, clientX) {
            const rect = track.getBoundingClientRect();
            const index = Math.floor((clientX - rect.left) / this.dayWidth);
            const clamped = Math.max(0, Math.min(this.dayCount() - 1, index));

            return this.addDays(this.axisStart(), clamped);
        },

        indexOf(iso) {
            const [y, m, d] = iso.split('-').map(Number);
            const [Y, M, D] = this.axisStart().split('-').map(Number);

            return Math.round((Date.UTC(y, m - 1, d) - Date.UTC(Y, M - 1, D)) / 86400000);
        },

        addDays(iso, days) {
            const [y, m, d] = iso.split('-').map(Number);
            const date = new Date(Date.UTC(y, m - 1, d));
            date.setUTCDate(date.getUTCDate() + days);
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const day = String(date.getUTCDate()).padStart(2, '0');

            return `${date.getUTCFullYear()}-${month}-${day}`;
        },

        formatPl(iso) {
            const [, month, day] = iso.split('-');

            return `${Number(day)}.${month}`;
        },
    }));
}
