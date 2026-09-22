<div class="xuiv2-tasks{{ $this->isEdiReviewing() ? ' is-edi-review' : '' }}{{ $this->isPlanQueue() ? ' is-plan-queue' : '' }}" id="xuiv2Tasks"
     x-data="{
         filterOpen: false,
         filterMode: 'all',
         filterTop: 0,
         filterLeft: 0,
         filterWidth: 600,
         openStatus: false,
         openVisibility: false,
         openType: false,
         openSearch: false,
         openMore: false,
         openGroup: false,
         filterLabels: @js(collect($availableColumns)->mapWithKeys(fn ($col, $key) => [$key => $col['label']])->all()),
         closeFilters() {
             this.filterOpen = false;
             this.filterMode = 'all';
         },
         toggleAllFilters(el) {
             this.$dispatch('tg-close-col-menu');
             if (this.filterOpen && this.filterMode === 'all') {
                 this.closeFilters();
                 return;
             }
             const r = el.getBoundingClientRect();
             const pw = Math.min(600, window.innerWidth - 24);
             this.filterTop = r.bottom + 4;
             this.filterLeft = Math.max(4, Math.min(r.left, window.innerWidth - pw - 4));
             this.filterWidth = pw;
             this.filterMode = 'all';
             this.filterOpen = true;
         },
         openColumnFilter(detail) {
             const pw = Math.min(360, window.innerWidth - 24);
             this.filterTop = detail.top;
             this.filterLeft = Math.max(4, Math.min(detail.left, window.innerWidth - pw - 4));
             this.filterWidth = pw;
             this.filterMode = detail.key;
             this.filterOpen = true;
         },
         flashFilterChip(key) {
             this.$nextTick(() => {
                 if (!key) return;
                 const scope = document.getElementById('wi-plan-filters') || this.$root;
                 if (!scope) return;
                 const el = scope.querySelector('[data-tg-filter-key=' + key + ']');
                 if (!el) return;
                 el.classList.remove('is-fresh');
                 void el.offsetWidth;
                 el.classList.add('is-fresh');
                 setTimeout(() => el.classList.remove('is-fresh'), 1100);
             });
         }
     }"
     @tg-open-col-filter.window="openColumnFilter($event.detail)"
     @tg-close-filters.window="closeFilters()"
     @tg-filter-flash.window="flashFilterChip($event.detail.key)"
     @keydown.escape.window="closeFilters()">
<style>
    /* ══════════════════════════════════════════════════════════
       xuiv2 — probka z /2, oryginalnie testowana na /tasks2. Fonty
       (Space Grotesk/JetBrains Mono), tło ambient (siatka/ziarno/poświata)
       i fiolet primary→accent są teraz GLOBALNE (app.css + app.js) — patrz
       body::before/::after, .cl-cursor-glow, ".card::before" i ".font-mono"
       w app.css. Ten blok zawiera już tylko rzeczy specyficzne dla /tasks2
       (nagłówek "Backlog", panel filtrów, status pills, magnetyczne CTA).
       ══════════════════════════════════════════════════════════ */
    .xuiv2-tasks {
        position: relative;
        isolation: isolate;
        /* Chrome (tło/obramowanie) jest już na .app-page-shell w layoucie —
           tu zostaje tylko scoping CSS, bez drugiej „karty w karcie”. */
    }
    /* Toolbar: zero gradientu — tytuł Backlog ma być jedynym mocnym akcentem.
       Aktywny stan to cichy tint, nie CTA. */
    .xuiv2-tasks .tg-quiet-btn,
    .xuiv2-tasks .rp-topbar-btn {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: var(--text-muted, #94a3b8) !important;
        box-shadow: none !important;
        filter: none !important;
        transform: none !important;
    }
    .xuiv2-tasks .tg-quiet-btn:hover,
    .xuiv2-tasks .rp-topbar-btn:hover {
        background: rgba(255, 255, 255, 0.07) !important;
        border-color: rgba(255, 255, 255, 0.14) !important;
        color: var(--text-main, #f1f5f9) !important;
        filter: none !important;
        transform: none !important;
        box-shadow: none !important;
    }
    .xuiv2-tasks .tg-quiet-btn.is-on,
    .xuiv2-tasks .rp-topbar-btn.is-on {
        background: rgba(168, 85, 247, 0.10) !important;
        border-color: rgba(168, 85, 247, 0.28) !important;
        color: #e2e8f0 !important;
    }
    .xuiv2-tasks .tg-quiet-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.2em;
        padding: 0 .3rem;
        margin-left: .3rem;
        border-radius: 999px;
        font-size: .62rem;
        font-weight: 600;
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        background: rgba(255, 255, 255, 0.08);
        color: inherit;
        opacity: .85;
    }
    /* Nagłówek strony ("Backlog" + Sprinty/Widok kart) żyje poza tym komponentem
       (x-app-layout). Mono-font na przyciskach w headerze jest globalny. */

    /* Focus ring: fiolet (--accent) zamiast niebieskiego */
    .xuiv2-tasks .form-control:focus,
    .xuiv2-tasks .form-select:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 4px rgba(168,85,247,0.2) !important;
    }

    /* Trochę więcej oddechu w chipach aktywnych filtrów (globalna klasa .rp-active-filters__chip
       ma za ciasny padding — nadpisujemy tylko w obrębie tego widoku) */
    .xuiv2-tasks .rp-active-filters { gap: .4rem .55rem; padding: .3rem 0 .2rem; }
    .xuiv2-tasks .rp-active-filters__chip { padding: .3rem .65rem; font-size: .78rem; }
    .xuiv2-tasks .rp-active-filters__clear { padding: .3rem .5rem; font-size: .78rem; }
    .tg-active-filters__chips { display: contents; }
    @keyframes tg-filter-fresh {
        0% {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(168, 85, 247, 0.7);
            box-shadow: 0 0 0 0 rgba(168, 85, 247, 0.5);
            color: #e9d5ff;
        }
        100% {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 8px rgba(168, 85, 247, 0);
            color: #cbd5e1;
        }
    }
    .xuiv2-tasks .rp-active-filters__chip.is-fresh {
        animation: tg-filter-fresh .95s ease-out;
    }
    @media (prefers-reduced-motion: reduce) {
        .xuiv2-tasks .rp-active-filters__chip.is-fresh {
            animation: none;
            border-color: rgba(168, 85, 247, 0.55);
        }
    }

    .tg-facet {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        min-width: 0;
        max-width: 100%;
    }
    .tg-facet__value {
        appearance: none;
        background: none;
        border: 0;
        padding: 0;
        margin: 0;
        color: inherit;
        font: inherit;
        text-align: left;
        cursor: pointer;
        min-width: 0;
        max-width: 100%;
        border-radius: 4px;
    }
    .tg-facet__value:hover {
        outline: 1px dashed rgba(168, 85, 247, 0.45);
    }
    .tg-facet__edit {
        appearance: none;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        padding: 0;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: var(--text-muted, #94a3b8);
        font-size: 0.72rem;
        opacity: 0.45;
        cursor: pointer;
    }
    .tg-facet:hover .tg-facet__edit,
    .tg-facet__edit:focus-visible {
        opacity: 1;
        color: var(--text-main, #f1f5f9);
        background: rgba(168, 85, 247, 0.16);
    }
    .tg-dt-card .tg-facet {
        width: 100%;
        justify-content: space-between;
        position: relative;
        z-index: 3;
    }

    /* Panel „Filtry” (teleportowany do body — scoped przez klasę, nie DOM ancestry) */
    .tg-filter-panel-teal button.rp-filter-chip.is-active {
        background: rgba(168,85,247,0.2) !important;
        border-color: rgba(168,85,247,0.45) !important;
    }
    .tg-filter-panel-teal button.rp-filter-option.is-active {
        background: rgba(168,85,247,0.12) !important;
    }
    .tg-filter-panel-teal .rp-filter-check.is-checked {
        background: #a855f7 !important; border-color: #a855f7 !important; color: #fff !important;
    }
    .tg-filter-panel-teal .rp-filter-input:focus {
        border-color: rgba(168,85,247,0.55) !important;
        box-shadow: 0 0 0 2px rgba(168,85,247,0.2) !important;
    }
    .tg-col-menu {
        display: none !important;
        min-width: 200px;
        z-index: 1000002 !important;
        pointer-events: auto;
    }
    .tg-col-menu.is-open {
        display: block !important;
    }
    .tg-filter-panel--column {
        width: min(360px, calc(100vw - 24px)) !important;
        min-width: 260px !important;
    }
    .tg-col-filter-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.15rem;
        height: 1.15rem;
        margin-left: .15rem;
        padding: 0;
        border: 0;
        border-radius: 4px;
        background: rgba(168, 85, 247, 0.2);
        color: #ddd6fe;
        font-size: .62rem;
        line-height: 1;
        vertical-align: middle;
        cursor: pointer;
        pointer-events: auto;
    }
    .tg-col-filter-mark:hover {
        background: rgba(168, 85, 247, 0.38);
        color: #fff;
    }
    .tg-col-legend {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.15rem;
        height: 1.15rem;
        margin-left: .2rem;
        padding: 0;
        border: 0;
        border-radius: 4px;
        background: transparent;
        color: var(--text-muted, #94a3b8);
        font-size: .72rem;
        line-height: 1;
        vertical-align: middle;
        cursor: help;
        pointer-events: auto;
        opacity: .7;
    }
    .tg-col-legend:hover,
    .tg-col-legend:focus-visible {
        opacity: 1;
        color: #ddd6fe;
        background: rgba(168, 85, 247, 0.2);
    }

    /* All text inside any dropdown rendered by this component must be light */
    .dropdown-menu { color: var(--text-main, #f1f5f9) !important; }
    .dropdown-menu hr { border-color: rgba(255,255,255,0.1) !important; }

    /* Compact btn-sm — app.css hardcodes 10px 24px on .btn, killing Bootstrap's btn-sm vars */
    .tg-toolbar .btn {
        padding: 5px 11px !important;
        font-size: 0.78rem !important;
        border-radius: 8px !important;
        gap: 5px !important;
        box-shadow: none !important;
        transform: none !important;
        filter: none !important;
    }
    .tg-toolbar .btn-group .btn { border-radius: 0 !important; }
    .tg-toolbar .btn-group .btn:first-child { border-radius: 8px 0 0 8px !important; }
    .tg-toolbar .btn-group .btn:last-child  { border-radius: 0 8px 8px 0 !important; }

    .rp-active-filters__chip-remove {
        appearance: none;
        -webkit-appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1rem;
        height: 1rem;
        padding: 0;
        margin: 0 0 0 .05rem;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: #64748b;
        line-height: 1;
        cursor: pointer;
    }
    .rp-active-filters__chip-remove:hover {
        color: #e2e8f0;
        background: rgba(255, 255, 255, 0.08);
    }

    /* Input-group in dark theme */
    .tg-toolbar .input-group-text {
        background: var(--bg-input, rgba(15,23,42,.8)) !important;
        border-color: var(--glass-border, rgba(255,255,255,.1)) !important;
        color: var(--text-muted, #94a3b8) !important;
    }
    .tg-toolbar .form-control {
        border-radius: 8px !important;
        font-size: 0.8rem !important;
        padding: 5px 10px !important;
    }
    .tg-toolbar .input-group .form-control { border-radius: 0 8px 8px 0 !important; }
    .tg-toolbar .input-group .input-group-text:first-child { border-radius: 8px 0 0 8px !important; }
    .tg-toolbar__controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    .tg-toolbar__chrono .ac-trigger__hint {
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-variant-numeric: tabular-nums;
    }

    /* ── Compact grid: reset global table spacing ── */
    .tg-table {
        border-spacing: 0 !important;
        border-collapse: separate !important;
    }
    .tg-table td, .tg-table th {
        vertical-align: middle !important;
    }

    /* Karta tabeli: cieńsze, bardziej „editorial” obramowanie zamiast domyślnego .card 20px */
    .tg-table-wrap {
        border-radius: 12px !important;
        border-color: rgba(255,255,255,0.08) !important;
        overflow: hidden;
        position: relative;
    }
    .tg-table-wrap::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, var(--primary,#3b82f6), transparent 70%);
        opacity: .6; z-index: 6; pointer-events: none;
    }

    /* ── Sticky header ──
       WAŻNE: !important jest tu konieczne — komórki kolumn danych mają
       inline style="position:relative" (do pozycjonowania uchwytu do
       zmiany szerokości .tg-resize-handle), który bez !important
       nadpisywał by position:sticky. Bez tego tylko pierwsza, "pusta"
       komórka (checkbox/expand, bez inline position) łapała sticky —
       stąd błąd "przykleja się jeden mały kwadracik, a nie cały wiersz". ── */
    .tg-table > thead > tr > th {
        position: sticky !important;
        top: 0;
        z-index: 5;
        background: rgba(10, 15, 29, 0.97) !important;
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255,255,255,0.12) !important;
        border-top: none !important;
        color: var(--text-muted, #94a3b8) !important;
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.66rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        white-space: nowrap;
        padding: 11px 10px;
        transition: color .15s ease;
    }
    .tg-table > thead > tr > th.sortable { cursor: pointer; }
    .tg-table > thead > tr > th.sortable:hover { color: var(--primary, #3b82f6) !important; }

    /* ── Task rows ── */
    .tg-table > tbody > tr.tg-task-row > td {
        padding: 7px 10px !important;
        border-bottom: 1px solid rgba(255,255,255,0.05) !important;
        background: transparent !important;
        font-size: 0.84rem;
        transition: background .12s ease;
    }
    .tg-table > tbody > tr.tg-task-row:hover > td {
        background: rgba(255,255,255,0.035) !important;
    }
    .xuiv2-tasks .tg-table > tbody > tr.tg-task-row > td.tg-edi--add {
        background: rgba(59, 130, 246, 0.22) !important;
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.55);
    }
    .xuiv2-tasks .tg-table > tbody > tr.tg-task-row > td.tg-edi--change {
        background: rgba(234, 179, 8, 0.22) !important;
        box-shadow: inset 0 0 0 1px rgba(234, 179, 8, 0.6);
    }
    .xuiv2-tasks .tg-table > tbody > tr.tg-task-row > td.tg-edi--remove {
        background: rgba(239, 68, 68, 0.22) !important;
        box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.55);
    }
    .xuiv2-tasks .tg-table > tbody > tr.tg-task-row:hover > td.tg-edi--add {
        background: rgba(59, 130, 246, 0.3) !important;
    }
    .xuiv2-tasks .tg-table > tbody > tr.tg-task-row:hover > td.tg-edi--change {
        background: rgba(234, 179, 8, 0.3) !important;
    }
    .xuiv2-tasks .tg-table > tbody > tr.tg-task-row:hover > td.tg-edi--remove {
        background: rgba(239, 68, 68, 0.3) !important;
    }
    .xuiv2-tasks.is-edi-review .tg-expanded > td {
        background: rgba(15, 23, 42, 0.35) !important;
    }
    .tg-edi__cell { display: inline-flex; align-items: center; gap: 0.25rem; max-width: 100%; }
    .tg-edi__pair {
        display: inline-flex; align-items: center; gap: 0.35rem; min-width: 0; max-width: 100%;
        padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.78rem; line-height: 1.3;
        border: 0; cursor: pointer; text-align: left; color: inherit;
    }
    .tg-edi__pair--add { background: rgba(59, 130, 246, 0.32); box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.7); }
    .tg-edi__pair--change { background: rgba(234, 179, 8, 0.32); box-shadow: inset 0 0 0 1px rgba(234, 179, 8, 0.75); }
    .tg-edi__pair--remove { background: rgba(239, 68, 68, 0.32); box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.7); }
    .tg-edi__from { color: #94a3b8; text-decoration: line-through; max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tg-edi__to { font-weight: 700; max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: text; }
    .tg-edi__pair--add .tg-edi__to { color: #bfdbfe; }
    .tg-edi__pair--change .tg-edi__to { color: #fde047; }
    .tg-edi__pair--remove .tg-edi__to { color: #fca5a5; }
    .tg-edi__skip {
        flex-shrink: 0; width: 1.15rem; height: 1.15rem; padding: 0; border: 0; border-radius: 999px;
        background: rgba(0,0,0,.35); color: #94a3b8; line-height: 1; font-size: 0.7rem;
    }
    .tg-edi__skip:hover { background: rgba(239,68,68,.45); color: #fff; }
    .tg-edi__input {
        min-width: 7rem; max-width: 14rem; width: 100%;
        padding: 0.1rem 0.35rem; border-radius: 4px;
        border: 1px solid rgba(168,85,247,.55);
        background: rgba(7,10,19,.75); color: #f1f5f9;
        font-size: 0.78rem; font-family: inherit;
    }
    .tg-edi__input--wide { min-width: 12rem; max-width: 100%; }
    .tg-expanded > td {
        background: rgba(168,85,247,0.06) !important;
    }

    /* ── Hover-edit cells ── */
    .tg-hover-edit:hover {
        background: rgba(168,85,247,0.12) !important;
        outline: 1px dashed rgba(168,85,247,0.5);
        border-radius: 4px;
    }
    button.tg-hover-edit {
        appearance: none;
        background: none;
        border: 0;
        padding: 2px 4px;
        color: inherit;
        font: inherit;
        cursor: pointer;
        text-align: left;
    }
    .tg-dt-card .tg-hover-edit {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.3rem;
        width: 100%;
        min-height: 1.7rem;
        cursor: pointer;
        padding: 0.1rem 0.25rem;
        border-radius: 4px;
    }
    @media (hover: none) {
        .tg-dt-card .tg-hover-edit {
            outline: 1px dashed rgba(168, 85, 247, 0.28);
        }
    }
    .tg-dt-card .dt-card__value .form-select,
    .tg-dt-card .dt-card__value .form-control {
        width: 100%;
        max-width: 100%;
    }

    /* ── Status badge pill ── */
    .xuiv2-tasks .tg-status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 20px;
        font-size: 0.76rem; font-weight: 600; line-height: 1.3;
        white-space: nowrap; border: none;
        transition: filter .15s;
        width: 100%;
        box-sizing: border-box;
        justify-content: flex-start;
    }
    .xuiv2-tasks .tg-status-badge.tg-col-chip--split {
        gap: 0;
        padding: 2px 2px 2px 0;
    }
    .xuiv2-tasks .tg-status-badge.tg-col-chip--has-exclude,
    .xuiv2-tasks .tg-time-chip.tg-col-chip--has-exclude {
        padding-left: 2px;
    }
    .xuiv2-tasks button.tg-status-badge:hover { filter: brightness(1.18); cursor: pointer; }
    .xuiv2-tasks .tg-status-badge.s-pending    { background: rgba(245,158,11,.18); color: #f59e0b; border: 1px solid rgba(245,158,11,.35); }
    .xuiv2-tasks .tg-status-badge.s-in_progress{ background: rgba(168,85,247,.18); color: #c084fc; border: 1px solid rgba(168,85,247,.35); }
    .xuiv2-tasks .tg-status-badge.s-completed  { background: rgba(16,185,129,.18); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
    .xuiv2-tasks .tg-status-badge.s-cancelled  { background: rgba(239,68,68,.18);  color: #f87171; border: 1px solid rgba(239,68,68,.35); }

    .xuiv2-tasks td:has(.tg-col-chip--sprint),
    .xuiv2-tasks td:has(.tg-col-chip--category),
    .xuiv2-tasks td:has(.tg-col-chip--priority),
    .xuiv2-tasks td:has(.tg-col-chip--assignee),
    .xuiv2-tasks td:has(.tg-time-chip) {
        overflow: hidden;
        min-width: 0;
        position: relative;
        z-index: 1;
    }
    .xuiv2-tasks td:has(.tg-col-chip:hover),
    .xuiv2-tasks td:has(.tg-col-chip:focus-within),
    .xuiv2-tasks td:has(.tg-status-badge:hover),
    .xuiv2-tasks td:has(.tg-status-badge:focus-within),
    .xuiv2-tasks td:has(.tg-time-chip:hover),
    .xuiv2-tasks td:has(.tg-time-chip:focus-within) {
        overflow: visible;
        z-index: 12;
    }
    .xuiv2-tasks td:has(.tg-col-chip--sprint) {
        max-width: var(--tg-w-sprint, 16rem);
    }
    .xuiv2-tasks td:has(.tg-col-chip--category) {
        max-width: var(--tg-w-category, 16rem);
    }
    .xuiv2-tasks td > [x-data]:has(> .tg-status-badge) {
        width: 100%;
    }

    .xuiv2-tasks .tg-col-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.76rem;
        font-weight: 600;
        line-height: 1.3;
        text-align: left;
        text-decoration: none !important;
        white-space: nowrap;
        appearance: none;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        overflow: visible;
    }
    .xuiv2-tasks .tg-col-chip--split {
        gap: 0;
        padding: 2px 2px 2px 0;
    }
    .xuiv2-tasks .tg-col-chip--has-exclude {
        padding-left: 2px;
    }
    .xuiv2-tasks .tg-col-chip__main {
        appearance: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        flex: 1 1 auto;
        min-width: 0;
        padding: 2px 8px 2px 10px;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: inherit;
        line-height: inherit;
        text-align: left;
        text-decoration: none !important;
        cursor: pointer;
    }
    .xuiv2-tasks span.tg-col-chip__main {
        cursor: default;
    }
    .xuiv2-tasks button.tg-col-chip--split > .tg-col-chip__main,
    .xuiv2-tasks button.tg-status-badge > .tg-col-chip__main {
        cursor: inherit;
        pointer-events: none;
    }
    .xuiv2-tasks button.tg-col-chip--split > .tg-col-chip__side,
    .xuiv2-tasks button.tg-status-badge > .tg-col-chip__side {
        pointer-events: none;
    }
    .xuiv2-tasks .tg-col-chip > i:first-child,
    .xuiv2-tasks .tg-col-chip__main > i:first-child {
        flex-shrink: 0;
        font-size: 0.82rem;
        opacity: 0.9;
    }
    .xuiv2-tasks .tg-col-chip__label {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .xuiv2-tasks .tg-col-chip__go {
        flex-shrink: 0;
        margin-left: auto;
        font-size: 0.62rem;
        opacity: 0.65;
    }
    .xuiv2-tasks .tg-col-chip__exclude {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        appearance: none;
        width: 1.45rem;
        min-height: 1.45rem;
        padding: 0;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: inherit;
        font-size: 0.72rem;
        line-height: 1;
        cursor: pointer;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.12s ease, background-color 0.12s ease;
    }
    .xuiv2-tasks .tg-col-chip__exclude i {
        font-size: 0.78rem;
        opacity: 0.85;
    }
    @media (hover: hover) {
        .xuiv2-tasks .tg-col-chip:hover > .tg-col-chip__exclude,
        .xuiv2-tasks .tg-col-chip:focus-within > .tg-col-chip__exclude,
        .xuiv2-tasks .tg-status-badge:hover > .tg-col-chip__exclude,
        .xuiv2-tasks .tg-status-badge:focus-within > .tg-col-chip__exclude,
        .xuiv2-tasks .tg-time-chip:hover > .tg-col-chip__exclude,
        .xuiv2-tasks .tg-time-chip:focus-within > .tg-col-chip__exclude {
            opacity: 1;
            pointer-events: auto;
        }
        .xuiv2-tasks .tg-col-chip__exclude:hover {
            background: rgba(255, 255, 255, 0.12);
        }
    }
    @media (hover: none) {
        .xuiv2-tasks .tg-col-chip__exclude {
            opacity: 0.75;
            pointer-events: auto;
        }
    }
    .xuiv2-tasks .tg-col-chip__side {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        appearance: none;
        width: 1.55rem;
        min-height: 1.45rem;
        margin-left: auto;
        padding: 0;
        border: 0;
        border-left: 1px solid color-mix(in srgb, currentColor 38%, transparent);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.05);
        color: inherit;
        font-size: 0.68rem;
        line-height: 1;
        cursor: pointer;
        text-decoration: none !important;
    }
    .xuiv2-tasks .tg-col-chip__side i {
        font-size: 0.68rem;
        opacity: 0.85;
    }
    .xuiv2-tasks span.tg-col-chip__side {
        cursor: inherit;
    }
    .xuiv2-tasks .tg-col-chip--split > .tg-col-chip__main,
    .xuiv2-tasks .tg-col-chip--split > .tg-col-chip__side,
    .xuiv2-tasks .tg-col-chip--split > .tg-col-chip__exclude {
        transform: none;
        box-shadow: none;
        filter: none;
        position: relative;
        z-index: 1;
    }
    .xuiv2-tasks .tg-col-chip--split > .tg-col-chip__side,
    .xuiv2-tasks .tg-col-chip--split > .tg-col-chip__exclude {
        z-index: 2;
    }
    @media (hover: hover) {
        .xuiv2-tasks .tg-col-chip__side:hover {
            background: rgba(255, 255, 255, 0.12);
        }
        .xuiv2-tasks a.tg-col-chip--sprint:hover,
        .xuiv2-tasks button.tg-col-chip--sprint:hover,
        .xuiv2-tasks .tg-col-chip--sprint.tg-col-chip--split:hover {
            background: rgba(168, 85, 247, 0.28);
            border-color: rgba(168, 85, 247, 0.55);
            color: #d8b4fe;
            filter: brightness(1.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
        }
        .xuiv2-tasks a.tg-col-chip--category:hover,
        .xuiv2-tasks button.tg-col-chip--category:hover,
        .xuiv2-tasks .tg-col-chip--category.tg-col-chip--split:hover {
            background: rgba(59, 130, 246, 0.28);
            border-color: rgba(59, 130, 246, 0.55);
            color: #93c5fd;
            filter: brightness(1.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        .xuiv2-tasks a.tg-col-chip--assignee:hover,
        .xuiv2-tasks button.tg-col-chip--assignee:hover,
        .xuiv2-tasks .tg-col-chip--assignee.tg-col-chip--split:hover {
            background: rgba(59, 130, 246, 0.22);
            border-color: rgba(59, 130, 246, 0.48);
            color: #bfdbfe;
            filter: brightness(1.12);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.32);
        }
        .xuiv2-tasks a.tg-col-chip--p1:hover,
        .xuiv2-tasks button.tg-col-chip--p1:hover,
        .xuiv2-tasks .tg-col-chip--p1.tg-col-chip--split:hover,
        .xuiv2-tasks a.tg-col-chip--p2:hover,
        .xuiv2-tasks button.tg-col-chip--p2:hover,
        .xuiv2-tasks .tg-col-chip--p2.tg-col-chip--split:hover {
            background: rgba(148, 163, 184, 0.22);
            border-color: rgba(148, 163, 184, 0.5);
            filter: brightness(1.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(148, 163, 184, 0.28);
        }
        .xuiv2-tasks a.tg-col-chip--p3:hover,
        .xuiv2-tasks button.tg-col-chip--p3:hover,
        .xuiv2-tasks .tg-col-chip--p3.tg-col-chip--split:hover {
            background: rgba(245, 158, 11, 0.26);
            border-color: rgba(245, 158, 11, 0.55);
            color: #fdba74;
            filter: brightness(1.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }
        .xuiv2-tasks a.tg-col-chip--p4:hover,
        .xuiv2-tasks button.tg-col-chip--p4:hover,
        .xuiv2-tasks .tg-col-chip--p4.tg-col-chip--split:hover,
        .xuiv2-tasks a.tg-col-chip--p5:hover,
        .xuiv2-tasks button.tg-col-chip--p5:hover,
        .xuiv2-tasks .tg-col-chip--p5.tg-col-chip--split:hover {
            background: rgba(239, 68, 68, 0.28);
            border-color: rgba(239, 68, 68, 0.55);
            color: #fca5a5;
            filter: brightness(1.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        .xuiv2-tasks button.tg-status-badge:hover,
        .xuiv2-tasks .tg-status-badge.tg-col-chip--split:hover {
            filter: brightness(1.18);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.28);
        }
        .xuiv2-tasks button.tg-status-badge.s-in_progress:hover,
        .xuiv2-tasks .tg-status-badge.s-in_progress.tg-col-chip--split:hover {
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.35);
        }
        .xuiv2-tasks button.tg-status-badge.s-completed:hover,
        .xuiv2-tasks .tg-status-badge.s-completed.tg-col-chip--split:hover {
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.35);
        }
        .xuiv2-tasks button.tg-status-badge.s-cancelled:hover,
        .xuiv2-tasks .tg-status-badge.s-cancelled.tg-col-chip--split:hover {
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.35);
        }
        .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--scheduled:hover,
        .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--scheduled.tg-col-chip--split:hover,
        .xuiv2-tasks button.tg-time-chip--due.tg-time-chip--ok:hover,
        .xuiv2-tasks .tg-time-chip--due.tg-time-chip--ok.tg-col-chip--split:hover {
            background: rgba(59, 130, 246, 0.28);
            border-color: rgba(168, 85, 247, 0.55);
            color: #ddd6fe;
            filter: brightness(1.12);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.38);
        }
        .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--stale:hover,
        .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--stale.tg-col-chip--split:hover,
        .xuiv2-tasks button.tg-time-chip--due.tg-time-chip--soon:hover,
        .xuiv2-tasks .tg-time-chip--due.tg-time-chip--soon.tg-col-chip--split:hover {
            background: rgba(245, 158, 11, 0.26);
            border-color: rgba(245, 158, 11, 0.55);
            color: #fdba74;
            filter: brightness(1.12);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }
        .xuiv2-tasks button.tg-time-chip--due.tg-time-chip--late:hover,
        .xuiv2-tasks .tg-time-chip--due.tg-time-chip--late.tg-col-chip--split:hover {
            background: rgba(239, 68, 68, 0.28);
            border-color: rgba(239, 68, 68, 0.55);
            color: #fca5a5;
            filter: brightness(1.12);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        .xuiv2-tasks a.tg-time-chip--none:hover,
        .xuiv2-tasks button.tg-time-chip--none:hover,
        .xuiv2-tasks .tg-time-chip--none.tg-col-chip--split:hover {
            background: rgba(148, 163, 184, 0.18);
            border-color: rgba(148, 163, 184, 0.42);
            color: #cbd5e1;
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(148, 163, 184, 0.22);
        }
    }
    .xuiv2-tasks .tg-col-chip--sprint {
        background: rgba(168, 85, 247, 0.16);
        color: #c084fc;
        border: 1px solid rgba(168, 85, 247, 0.4);
    }
    .xuiv2-tasks .tg-col-chip--category {
        background: rgba(59, 130, 246, 0.16);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.4);
    }
    .xuiv2-tasks .tg-col-chip--assignee {
        background: rgba(59, 130, 246, 0.1);
        color: #93c5fd;
        border: 1px solid rgba(59, 130, 246, 0.32);
    }
    .xuiv2-tasks .tg-col-chip--p1 {
        background: rgba(148, 163, 184, 0.1);
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.28);
    }
    .xuiv2-tasks .tg-col-chip--p2 {
        background: rgba(148, 163, 184, 0.12);
        color: #cbd5e1;
        border: 1px solid rgba(148, 163, 184, 0.38);
    }
    .xuiv2-tasks .tg-col-chip--p3 {
        background: rgba(245, 158, 11, 0.14);
        color: #fb923c;
        border: 1px solid rgba(245, 158, 11, 0.4);
    }
    .xuiv2-tasks .tg-col-chip--p4 {
        background: rgba(239, 68, 68, 0.16);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.42);
    }
    .xuiv2-tasks .tg-col-chip--p5 {
        background: rgba(239, 68, 68, 0.22);
        color: #fb7185;
        border: 1px solid rgba(244, 63, 94, 0.5);
    }
    .xuiv2-tasks td:has(.tg-col-chip--priority) {
        overflow: hidden;
        min-width: 0;
        max-width: var(--tg-w-priority, 10rem);
    }
    .xuiv2-tasks td:has(.tg-col-chip--assignee) {
        max-width: var(--tg-w-assigned_to, 12rem);
    }
    .xuiv2-tasks .tg-facet:has(.tg-col-chip) {
        position: relative;
        display: flex;
        width: 100%;
        max-width: none;
    }
    .xuiv2-tasks .tg-quick-menu {
        width: 100%;
    }
    .xuiv2-tasks .tg-quick-menu > button,
    .xuiv2-tasks .tg-quick-menu > .tg-col-chip {
        width: 100%;
    }
    .xuiv2-tasks .tg-facet:has(.tg-facet__edit) .tg-col-chip {
        padding-right: 1.7rem;
    }
    .xuiv2-tasks .tg-facet:has(.tg-col-chip) .tg-facet__edit {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
        background: rgba(13, 18, 30, 0.88);
    }

    /* ── Avatar initials ── */
    .tg-avatar {
        width: 26px; height: 26px; font-size: 0.62rem; font-weight: 700;
        border-radius: 50%; display: inline-flex; align-items: center;
        justify-content: center; color: #fff;
        background: var(--primary, #3b82f6); flex-shrink: 0;
    }

    /* ── Group header (editorial kicker) ── */
    .tg-group-header > td {
        background: rgba(255,255,255,0.04) !important;
        border-top: 1px solid rgba(255,255,255,0.1) !important;
        padding: 7px 12px !important;
        font-size: 0.72rem; font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted, #94a3b8) !important;
        letter-spacing: 0.6px;
    }
    .tg-group-header .tg-group-bullet { color: var(--primary,#3b82f6); font-size: 0.6rem; margin-right: 7px; }
    .tg-group-collapsed > td { opacity: .85; }
    .tg-group-count {
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.66rem !important; font-weight: 500 !important;
        background: rgba(255,255,255,0.08) !important; color: var(--text-muted,#94a3b8) !important;
    }

    /* ── Expanded detail panel ── */
    .tg-expand-row > td {
        background: rgba(10,15,29,0.6) !important;
        border-bottom: 2px solid rgba(168,85,247,0.3) !important;
        padding: 14px 16px 18px !important;
    }
    .tg-expand-btn {
        position: relative;
    }
    .tg-expand-btn i {
        display: inline-block;
        transform-origin: 50% 50%;
        transition: transform .15s cubic-bezier(.22,.7,.2,1);
    }
    .tg-expand-btn.is-open i,
    .tg-expand-btn.is-opening i { transform: rotate(90deg); }
    [data-tg-expand-for][hidden] { display: none !important; }
    .tg-expand-body {
        overflow: visible;
    }
    .tg-expand-skel {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-height: 72px;
        padding: 4px 0 8px;
        color: var(--text-muted, #94a3b8);
        font-size: 0.78rem;
    }
    .tg-expand-skel__head {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .tg-expand-spinner {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid rgba(168,85,247,.25);
        border-top-color: #c084fc;
        animation: tg-expand-spin .7s linear infinite;
        flex-shrink: 0;
    }
    .tg-expand-skel__line {
        display: block;
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(255,255,255,.04), rgba(168,85,247,.18), rgba(59,130,246,.12), rgba(255,255,255,.04));
        background-size: 180% 100%;
        animation: tg-expand-shimmer 1.4s ease-in-out infinite;
    }
    .tg-expand-skel__line:nth-child(2) { width: 72%; }
    .tg-expand-skel__line:nth-child(3) { width: 54%; }
    .tg-expand-skel__line:nth-child(4) { width: 63%; }
    @keyframes tg-expand-spin { to { transform: rotate(360deg); } }
    @keyframes tg-expand-shimmer {
        0% { background-position: 100% 0; }
        100% { background-position: -80% 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .tg-expand-btn i { transition: none; }
        .tg-expand-spinner, .tg-expand-skel__line { animation: none; }
    }

    /* ── Add composer (top of list) ── */
    .tg-add-composer {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .4rem .5rem;
        margin-bottom: .45rem;
        padding: .4rem .55rem;
        border-radius: 14px;
        background: var(--bg-input, rgba(15, 23, 42, .45));
        border: 1px solid var(--glass-border, rgba(255,255,255,.1));
    }
    .tg-add-composer:focus-within {
        border-color: rgba(59, 130, 246, .45);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }
    .tg-add-composer--wide {
        flex-direction: column;
        align-items: stretch;
    }
    .tg-add-composer__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }
    .tg-add-composer__kind {
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-muted, #94a3b8);
    }
    .tg-add-composer__name {
        flex: 1 1 10rem;
        min-width: 0;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        color: var(--text-main, #f1f5f9);
        font-size: .9rem;
        padding: .15rem .2rem;
    }
    .tg-add-composer__name::placeholder {
        color: var(--text-muted, #94a3b8);
        opacity: .8;
    }
    .tg-add-composer__name:focus {
        outline: none;
    }
    .tg-add-composer__select {
        width: auto;
        min-width: 8.5rem;
        max-width: 12rem;
        background: rgba(255,255,255,.04) !important;
        border-color: var(--glass-border, rgba(255,255,255,.1)) !important;
        color: var(--text-main, #f1f5f9) !important;
        font-size: .78rem !important;
    }
    .tg-add-kinds {
        display: flex;
        align-items: center;
        gap: .3rem;
        margin: -.15rem 0 .65rem;
    }
    .xuiv2-tasks.is-edi-review .tg-add-composer,
    .xuiv2-tasks.is-edi-review .tg-add-kinds { display: none !important; }

    /* ── Subtask drag-and-drop (pointer events, not HTML5) ── */
    .tg-subtask-item { transition: background .1s; -webkit-user-drag: none; user-select: none; }
    .tg-subtask-item:hover { background: rgba(255,255,255,0.04); }
    .tg-subtask-item .tg-subtask-grip { cursor: grab; touch-action: none; }
    .tg-subtask-item .form-check { margin-bottom: 0 !important; }
    .tg-subtask-item.tg-row-sub-drop {
        background: rgba(16,185,129,.12) !important;
        box-shadow: inset 0 0 0 2px rgba(16,185,129,.45);
    }

    /* Drop target on a collapsed task row or expanded detail */
    .tg-task-row.tg-row-sub-drop > td,
    .tg-expand-row.tg-row-sub-drop > td {
        background: rgba(16,185,129,.08) !important;
        box-shadow: inset 0 0 0 2px rgba(16,185,129,.4);
    }

    /* ── Task drag between groups (Kanban) ── */
    .tg-task-grip { cursor: grab; color: rgba(255,255,255,0.22); font-size: 0.95rem; padding: 2px 4px; user-select: none; touch-action: none; }
    .tg-task-grip:hover { color: rgba(255,255,255,0.6); }
    .tg-group-header.tg-group-drop > td,
    .tg-task-row.tg-group-drop > td {
        background: rgba(16,185,129,.10) !important;
        box-shadow: inset 0 0 0 2px rgba(16,185,129,.45);
    }

    /* ── Column drag-to-reorder ── */
    .tg-table th[data-col] { cursor: grab; }
    html.tg-pointer-hold,
    html.tg-pointer-hold *,
    html.tg-pointer-drag,
    html.tg-pointer-drag * { cursor: grabbing !important; }
    html.tg-pointer-drag { user-select: none !important; }
    .tg-drag-ghost {
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000005;
        pointer-events: none;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-family: 'Space Grotesk', sans-serif;
        color: #f1f5f9;
        background: rgba(13, 18, 30, 0.94);
        border: 1px solid rgba(168, 85, 247, 0.5);
        box-shadow: 0 8px 24px rgba(0,0,0,.45);
        white-space: nowrap;
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tg-col-drag-over {
        background: rgba(168,85,247,.18) !important;
        outline: 2px dashed rgba(168,85,247,.6) !important;
        outline-offset: -2px;
    }

    /* ── Column resize handle ── */
    .tg-resize-handle {
        position: absolute;
        right: 0;
        top: 0;
        width: 6px;
        height: 100%;
        cursor: col-resize;
        z-index: 6;
        border-right: 2px solid transparent;
        transition: border-color .15s;
    }
    .tg-resize-handle:hover,
    .tg-resizing .tg-resize-handle { border-right-color: rgba(168,85,247,.7); }
    .tg-resizing * { cursor: col-resize !important; user-select: none !important; }

    /* ══════════════════════════════════════════════════════════
       MOBILE (< 768px): karty zamiast tabeli (HTML wybiera layout)
       ══════════════════════════════════════════════════════════ */
    .tg-cards {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    @media (max-width: 767.98px) {
        .tg-toolbar__row {
            flex-wrap: wrap;
            align-items: stretch;
            gap: 0.45rem;
        }
        .tg-toolbar__search,
        .tg-toolbar__views,
        .tg-toolbar__home {
            display: none !important;
        }
        .tg-toolbar__controls {
            flex: 1 1 100%;
            width: 100%;
            display: flex;
            gap: 0.4rem;
        }
        .tg-toolbar__filters,
        .tg-toolbar__columns {
            flex: 1 1 0;
            min-width: 0;
        }
        .tg-toolbar__filters .btn,
        .tg-toolbar__columns .btn {
            width: 100%;
            justify-content: center;
        }
        .tg-toolbar__chrono {
            padding: 4px 10px 4px 5px !important;
            gap: 0.4rem !important;
        }
        .tg-toolbar__chrono .ac-trigger__text {
            display: flex !important;
        }
        .tg-toolbar__chrono .ac-trigger__name {
            display: none !important;
        }
        .tg-toolbar__meta {
            flex: 1 1 100%;
            width: 100%;
            margin-left: 0 !important;
            align-items: stretch;
            justify-content: flex-start;
            gap: 0.4rem;
        }
        .tg-toolbar__view-menu,
        .tg-toolbar__chrono {
            flex: 1 1 0;
            min-width: 0;
        }
        .tg-toolbar__view-menu > .btn,
        .tg-toolbar__chrono.ac-trigger {
            width: 100%;
            height: 100%;
            justify-content: center;
            box-sizing: border-box;
            overflow: hidden;
        }
        .tg-toolbar__view-label {
            display: inline-block !important;
            flex: 1 1 auto;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }
        .tg-toolbar__view-icon {
            display: inline !important;
        }
        .tg-toolbar .btn { padding: 4px 8px !important; font-size: 0.72rem !important; }
        .tg-group-badge { display: none !important; }

        .tg-active-filters {
            display: flex !important;
            flex-direction: column;
            align-items: stretch;
            gap: 0.4rem;
        }
        .tg-active-filters__chips {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            width: 100%;
        }
        .tg-active-filters .rp-active-filters__chip {
            display: flex;
            width: 100%;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            border-radius: 10px;
            padding: 0.45rem 0.7rem;
        }
        .tg-active-filters .rp-active-filters__chip-text {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .tg-active-filters .rp-active-filters__chip-remove {
            width: 1.35rem;
            height: 1.35rem;
            margin: 0;
            flex-shrink: 0;
            font-size: 1rem;
        }
        .tg-active-filters .rp-active-filters__clear {
            align-self: flex-end;
        }
        @media (hover: none) {
            .tg-facet__edit { opacity: 0.8; }
        }
    }

    /* Plan queue: compact Filtry + Kolumny. No views, Chrono, or search. */
    .xuiv2-tasks.is-plan-queue .tg-toolbar__row {
        flex-wrap: nowrap;
        align-items: stretch;
        gap: 0.4rem;
    }
    .xuiv2-tasks.is-plan-queue .tg-toolbar__search,
    .xuiv2-tasks.is-plan-queue .tg-toolbar__views,
    .xuiv2-tasks.is-plan-queue .tg-toolbar__home,
    .xuiv2-tasks.is-plan-queue .tg-toolbar__meta,
    .xuiv2-tasks.is-plan-queue .tg-toolbar__chrono {
        display: none !important;
    }
    .xuiv2-tasks.is-plan-queue .tg-toolbar__controls {
        flex: 1 1 auto;
        width: 100%;
        display: flex;
        gap: 0.4rem;
    }
    .xuiv2-tasks.is-plan-queue .tg-toolbar__filters,
    .xuiv2-tasks.is-plan-queue .tg-toolbar__columns {
        flex: 1 1 0;
        min-width: 0;
        display: block;
    }
    .xuiv2-tasks.is-plan-queue .tg-toolbar__filters .btn,
    .xuiv2-tasks.is-plan-queue .tg-toolbar__columns .btn {
        width: 100%;
        justify-content: center;
    }
    .xuiv2-tasks.is-plan-queue .tg-toolbar__filters .bi-chevron-down {
        display: none !important;
    }
    .xuiv2-tasks.is-plan-queue .tg-toolbar .btn { padding: 4px 8px !important; font-size: 0.72rem !important; }
    .xuiv2-tasks.is-plan-queue .card.mb-2 { margin-bottom: 0.35rem !important; }
    .xuiv2-tasks.is-plan-queue .tg-toolbar.card-body,
    .xuiv2-tasks.is-plan-queue .card-body.tg-toolbar { padding: 0.35rem 0.45rem !important; }

    .tg-select {
        margin: 0;
        flex-shrink: 0;
        cursor: pointer;
        position: relative;
        z-index: 3;
    }
    .tg-select input {
        cursor: pointer;
    }
    .tg-task-row.is-selected > td {
        background: rgba(168, 85, 247, .08) !important;
    }
    .tg-dt-card.is-selected {
        box-shadow: 0 0 0 1px rgba(168, 85, 247, .55);
    }
    .tg-bulk-bar {
        display: none;
        align-items: center;
        flex-wrap: wrap;
        gap: .45rem .6rem;
        padding: .55rem .75rem;
        margin-bottom: .65rem;
        border: 1px solid var(--glass-border, rgba(255,255,255,.1));
        border-radius: 12px;
        background: rgba(13, 18, 30, .72);
    }
    .tg-bulk-bar.is-on {
        display: flex;
    }
    .tg-bulk-bar__count {
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-main, #f1f5f9);
    }
    .tg-bulk-bar__mutate {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        min-width: 0;
        flex-wrap: wrap;
    }
    .tg-bulk-bar__mutate .form-control,
    .tg-bulk-bar__mutate .form-select {
        width: 10.5rem;
        background: rgba(255,255,255,.04);
        border-color: var(--glass-border, rgba(255,255,255,.1));
        color: var(--text-main, #f1f5f9);
    }
    .xuiv2-tasks td:has(.tg-time-chip),
    .xuiv2-tasks .dt-card__value:has(.tg-time-chip) {
        min-width: 0;
    }
    .xuiv2-tasks .dt-card__value:has(.tg-time-chip:hover),
    .xuiv2-tasks .dt-card__value:has(.tg-time-chip:focus-within) {
        overflow: visible;
        z-index: 12;
        position: relative;
    }
    .xuiv2-tasks .tg-time-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
        padding: 3px 8px 3px 3px;
        border-radius: 20px;
        border: 1px solid transparent;
        font-size: 0.76rem;
        font-weight: 600;
        line-height: 1.3;
        text-align: left;
        text-decoration: none !important;
        white-space: nowrap;
        appearance: none;
        cursor: pointer;
        color: inherit;
        background: rgba(255, 255, 255, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        overflow: visible;
    }
    .xuiv2-tasks .tg-time-chip.tg-col-chip--split {
        gap: 0;
        padding: 2px 2px 2px 2px;
    }
    .xuiv2-tasks .tg-time-chip--static {
        cursor: default;
    }
    .xuiv2-tasks .tg-time-chip__icon {
        flex-shrink: 0;
        width: 1.45rem;
        height: 1.45rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        background: rgba(255, 255, 255, 0.08);
    }
    .xuiv2-tasks .tg-time-chip__label {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .xuiv2-tasks .tg-time-chip__go {
        flex-shrink: 0;
        margin-left: auto;
        font-size: 0.62rem;
        opacity: 0.7;
    }
    .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--scheduled,
    .xuiv2-tasks .tg-time-chip--due.tg-time-chip--ok {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(168, 85, 247, 0.38);
        color: #c4b5fd;
    }
    .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--scheduled .tg-time-chip__icon,
    .xuiv2-tasks .tg-time-chip--due.tg-time-chip--ok .tg-time-chip__icon {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.4), rgba(168, 85, 247, 0.4));
        color: #ede9fe;
    }
    .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--stale,
    .xuiv2-tasks .tg-time-chip--due.tg-time-chip--soon {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.4);
        color: #fdba74;
    }
    .xuiv2-tasks .tg-time-chip--cal.tg-time-chip--stale .tg-time-chip__icon,
    .xuiv2-tasks .tg-time-chip--due.tg-time-chip--soon .tg-time-chip__icon {
        background: rgba(245, 158, 11, 0.22);
        color: #fdba74;
    }
    .xuiv2-tasks .tg-time-chip--due.tg-time-chip--late {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.4);
        color: #fca5a5;
    }
    .xuiv2-tasks .tg-time-chip--due.tg-time-chip--late .tg-time-chip__icon {
        background: rgba(239, 68, 68, 0.22);
        color: #fca5a5;
    }
    .xuiv2-tasks .tg-time-chip--none {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.08);
        color: rgba(241, 245, 249, 0.45);
    }
    .xuiv2-tasks .tg-time-chip--none .tg-time-chip__icon {
        background: rgba(255, 255, 255, 0.04);
        color: rgba(241, 245, 249, 0.4);
    }
    .xuiv2-tasks .tg-schedule-stack {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.3rem;
        width: 100%;
        min-width: 0;
    }
    .xuiv2-tasks .tg-schedule-slots {
        list-style: none;
        margin: 0;
        padding: 0 0.15rem 0 1.85rem;
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 0.7rem;
        font-variant-numeric: tabular-nums;
        color: var(--text-muted, #94a3b8);
        line-height: 1.35;
    }
    .xuiv2-tasks .tg-schedule-slots li + li {
        margin-top: 0.1rem;
    }
        background: rgba(255, 255, 255, 0.05);
        color: rgba(241, 245, 249, 0.45);
    }
    .xuiv2-tasks .tg-date-plain {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
        font-size: 0.72rem;
        color: rgba(255, 255, 255, 0.42);
    }
    .xuiv2-tasks .tg-date-plain .bi {
        font-size: 0.78rem;
        opacity: 0.75;
    }

    /* ── Karty zadań (mobile) — ten sam szkielet label/wartość co /rotations ── */
    .tg-dt-card.card {
        border-left-width: 3px !important;
        border-left-style: solid !important;
    }
    .tg-dt-card .dt-card__title {
        font-size: 1.05rem;
        margin-bottom: 0.45rem;
        padding-bottom: 0.55rem;
    }
    .tg-dt-card.is-expanded .dt-card__title {
        margin-bottom: 0;
    }
    .tg-dt-card .dt-card__row {
        grid-template-columns: 6.8rem 1fr;
    }
    .tg-dt-card__heading {
        display: flex;
        align-items: flex-start;
        gap: 0.45rem;
    }
    .tg-dt-card__name {
        flex: 1;
        min-width: 0;
        overflow-wrap: anywhere;
        color: inherit;
        text-decoration: none;
        font-weight: 700;
        line-height: 1.3;
    }
    .tg-dt-hit {
        position: relative;
        z-index: 2;
    }
    .tg-dt-card .dt-card__value,
    .tg-dt-card .tg-card-expand {
        position: relative;
        z-index: 2;
    }
    .tg-card-expand-btn {
        appearance: none; border: none; background: transparent; padding: 2px;
        color: rgba(255,255,255,0.4); line-height: 1; flex-shrink: 0;
        margin-top: 0.15rem;
    }
    .tg-card-subtask-badge {
        flex-shrink: 0; font-size: 0.6rem; min-width: 30px; text-align: center;
        border-radius: 999px; padding: 1px 6px; margin-top: 0.2rem;
        background: rgba(255,255,255,0.1); color: var(--text-muted,#94a3b8);
    }
    .tg-card-source-link { flex-shrink: 0; color: #c084fc; line-height: 1; margin-top: 0.2rem; }
    .tg-card-expand {
        margin-top: 0.55rem; padding-top: 0.7rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .tg-dt-card__desc {
        white-space: pre-wrap;
        max-height: 160px;
        overflow-y: auto;
        background: rgba(0,0,0,0.25);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 0.8rem;
        line-height: 1.5;
        color: var(--text-main,#f1f5f9);
    }

    /* ── Nagłówek grupy (mobile) ── */
    .tg-group-card-header {
        display: flex; align-items: center; gap: 8px;
        padding: 0.35rem 0.15rem 0.15rem;
        margin-top: 0.35rem;
        font-size: 0.78rem; font-weight: 700;
        letter-spacing: 0.02em;
        color: var(--text-muted,#94a3b8);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding-bottom: 0.45rem;
    }
    .tg-group-card-header:first-child { margin-top: 0; }

    .xuiv2-tasks.is-edi-review .tg-add-composer,
    .xuiv2-tasks.is-edi-review .tg-add-kinds { display: none !important; }
    body:has(.xuiv2-tasks.is-edi-review) .ui-page-header__right {
        display: none !important;
    }
</style>

{{-- Flash message --}}
@if($flash && ! $this->isEdiReviewing())
<div class="alert alert-success alert-dismissible py-2 mb-2 d-flex align-items-center gap-2 small" role="alert"
     style="border-radius: 6px">
    <i class="bi bi-check-circle-fill text-success"></i>
    <span class="flex-grow-1">{{ $flash }}</span>
    <button type="button" wire:click="$set('flash', null)" class="btn-close" style="font-size:0.8rem"></button>
</div>
@endif

@if($ediLoading || $ediChanges !== [] || $ediError)
<div class="tg-edi-bar mb-2" @if($ediLoading) wire:init="fetchEdiProposals" @endif>
    @if($ediLoading)
        <div class="d-flex align-items-center gap-2 small">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span>Edi czyta eksport bieżącego filtra…</span>
        </div>
    @else
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="flex-grow-1 small">
                <strong>Zatwierdzanie zmian Ediego</strong>
                @if($ediError && $ediChanges === [])
                    — {{ $ediError }}
                @else
                    — kliknij podświetloną komórkę, żeby zastosować (znika z listy). × odrzuca bez zapisu.
                    Zostało {{ count($ediChanges) }}.
                    <span class="d-block mt-1" style="opacity:.8">
                        🟦 dodano · 🟨 zmieniono · 🟥 usunięto · kliknij nową wartość, żeby poprawić (bez zapisu)
                    </span>
                @endif
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="chronoChooseEdiExport">Eksport JSON</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="discardEdiChanges">Zamknij bez zmian</button>
            @if($ediChanges !== [])
                <button type="button" class="btn btn-sm btn-primary" wire:click="applyEdiChanges"
                        wire:loading.attr="disabled" wire:target="applyEdiChanges">
                    Zastosuj pozostałe ({{ count($ediChanges) }})
                </button>
            @endif
        </div>
    @endif
</div>
@endif

@unless($this->isEdiReviewing())
{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TOOLBAR — jeden rząd, jak w /recruitment-processes:          --}}
{{-- Szukaj + jeden przycisk „Filtry” (pogrupowany panel) zamiast --}}
{{-- rzędu osobnych przełączników.                                --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@php
    $filteredTaskCount = (int) ($chronoItemCount ?? 0);
    $filteredTaskHint = $filteredTaskCount === 1 ? '1 zadanie' : $filteredTaskCount.' zadań';
    $filterBadgeCount = $this->isPlanQueue()
        ? count(array_filter($filterChips, fn (array $chip) => empty($chip['locked'])))
        : count($filterChips);
@endphp
<div class="card mb-2 border-0 shadow-sm">
    <div class="card-body py-2 px-3 tg-toolbar">
        <div class="d-flex align-items-center gap-2 flex-wrap tg-toolbar__row">
            {{-- Search: Task --}}
            <div class="input-group tg-search-task tg-toolbar__search" style="width:175px">
                <span class="input-group-text px-2">
                    <i class="bi bi-search" style="font-size:0.72rem"></i>
                </span>
                <input wire:model.live.debounce.300ms="searchTask"
                       type="text"
                       placeholder="Szukaj zadania…"
                       class="form-control">
            </div>

            <div class="tg-toolbar__controls">
            {{-- Filtry: jeden przycisk, panel z pogrupowanymi sekcjami (SharePoint-style, jak w rekrutacji) --}}
            <div class="tg-toolbar__filters">
                <button type="button"
                        @click.stop="toggleAllFilters($el)"
                        class="btn btn-sm btn-outline-secondary tg-quiet-btn {{ $filterBadgeCount > 0 ? 'is-on' : '' }}">
                    <i class="bi bi-sliders me-1"></i>Filtry
                    @if($filterBadgeCount > 0)
                        <span class="tg-quiet-count">{{ $filterBadgeCount }}</span>
                    @endif
                    <i class="bi bi-chevron-down ms-1 d-none d-md-inline" style="font-size:.6rem"></i>
                </button>
            </div>

            <div class="tg-toolbar__columns" x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); const pw=Math.min(360, window.innerWidth-24); top=r.bottom+4; left=Math.max(4, Math.min(r.left, window.innerWidth-pw-4)); open=true"
                        class="btn btn-sm btn-outline-secondary tg-quiet-btn">
                    <i class="bi bi-layout-three-columns me-1"></i>Kolumny
                    <span class="tg-quiet-count">{{ $this->visibleColumnCount() }}</span>
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak
                         @click.outside="open = false"
                         :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;width:min(360px, calc(100vw - 24px));`"
                         class="rp-filter-panel tg-filter-panel-teal">
                        @include('livewire.partials.tg-columns-panel')
                    </div>
                </template>
            </div>
            </div>

            {{-- Zapisane widoki (pigułki) — na mobile tylko aktualny, w menu zakładki --}}
            @unless($this->isLockedToSprint() || $this->isPlanQueue())
                <div class="tg-toolbar__views d-flex align-items-center gap-2 flex-wrap">
                    @foreach($savedViews as $savedView)
                        @php $isActiveView = $activeViewId === $savedView->id; @endphp
                        <button type="button" wire:click="loadSavedView({{ $savedView->id }})"
                                class="btn btn-sm btn-outline-secondary rp-topbar-btn {{ $isActiveView ? 'is-on' : '' }}"
                                title="{{ $savedView->is_global ? 'Widok globalny (dla wszystkich)' : 'Twój zapisany widok' }}">
                            <i class="bi bi-{{ $savedView->is_global ? 'globe' : 'bookmark'.($isActiveView ? '-fill' : '') }} me-1"></i>{{ $savedView->name }}
                            <span class="tg-quiet-count">{{ $viewCounts[$savedView->id] ?? 0 }}</span>
                        </button>
                    @endforeach
                </div>
            @endunless

            {{-- Spinner tylko przy przebudowie listy, nie przy doklejaniu panelu / podzadaniu --}}
            <div wire:loading
                 wire:target.except="toggleExpand,toggleSubtask,startAddSubtask,saveSubtask,cancelAddSubtask,setColumnWidth,reorderColumns,moveTaskToGroup,moveSubtask">
                <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:14px;height:14px">
                    <span class="visually-hidden">Ładowanie…</span>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 tg-toolbar__meta">
                @unless($this->isLockedToSprint() || $this->isPlanQueue())
                    {{-- Zapisz / zarządzaj widokami — na mobile pokazuje nazwę aktualnego widoku --}}
                    <div class="tg-toolbar__view-menu" x-data="{ open: false, top: 0, left: 0, pw: 300 }">
                        <button type="button"
                                @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); pw=Math.min(300, window.innerWidth-24); top=r.bottom+4; left=Math.max(4, Math.min(r.right-pw, window.innerWidth-pw-4)); open=true"
                                class="btn btn-sm btn-outline-secondary tg-quiet-btn {{ $activeViewId ? 'is-on' : '' }}"
                                title="Zapisz i zarządzaj widokami">
                            <i class="bi bi-bookmark{{ $view ? '-fill' : '' }} tg-toolbar__view-icon"></i>
                            <span class="tg-toolbar__view-label d-md-none">{{ $activeViewName ?: 'Domyślny' }}</span>
                        </button>
                        <template x-teleport="body">
                            <div x-show="open" x-cloak
                                 @click.outside="open = false"
                                 :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;width:${pw}px;max-width:calc(100vw - 24px)`"
                                 class="dropdown-menu show p-3 shadow-lg">
                                @if($activeViewId && $activeViewName)
                                    <div class="mb-2 p-2 rounded" style="background:rgba(168,85,247,.08);border:1px solid rgba(168,85,247,.2)">
                                        <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:4px">
                                            Aktywny widok
                                        </div>
                                        <div class="fw-semibold small mb-2">{{ $activeViewName }}</div>
                                        <button type="button" wire:click="clearView" @click="open=false"
                                                class="btn btn-sm btn-outline-secondary w-100">
                                            Widok domyślny
                                        </button>
                                    </div>
                                    <hr class="my-2">
                                @endif
                                @if($savedViews->isNotEmpty())
                                    <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:8px">
                                        Zapisane
                                    </div>
                                    @foreach($savedViews as $savedView)
                                    @php $isActiveView = $activeViewId === $savedView->id; @endphp
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <button type="button" wire:click="loadSavedView({{ $savedView->id }})" @click="open=false"
                                                class="btn btn-sm btn-link text-start flex-grow-1 p-1 text-decoration-none {{ $isActiveView ? 'fw-bold' : '' }}"
                                                style="font-size:0.83rem">
                                            <i class="bi bi-{{ $savedView->is_global ? 'globe' : 'bookmark'.($isActiveView ? '-fill' : '') }} me-1"
                                               style="color:var(--primary,#3b82f6);font-size:0.75rem"></i>{{ $savedView->name }}
                                            @if($savedView->is_global)
                                                <span class="text-muted" style="font-size:.68rem">globalny</span>
                                            @endif
                                            <span class="text-muted ms-1" style="font-size:.75rem">({{ $viewCounts[$savedView->id] ?? 0 }})</span>
                                        </button>
                                        @if($savedView->canBeManagedBy(auth()->user()))
                                            <button type="button" wire:click="overwriteView({{ $savedView->id }})" @click="open=false"
                                                    class="btn btn-sm btn-link p-1 flex-shrink-0"
                                                    style="color:var(--text-muted,#94a3b8)"
                                                    title="Nadpisz ten widok bieżącymi filtrami">
                                                <i class="bi bi-floppy" style="font-size:0.78rem"></i>
                                            </button>
                                            <button type="button" wire:click="deleteView({{ $savedView->id }})" @click="open=false"
                                                    class="btn btn-sm btn-link p-1 flex-shrink-0"
                                                    style="color:var(--danger,#ef4444)" title="Usuń">
                                                <i class="bi bi-trash" style="font-size:0.78rem"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @endforeach
                                    <hr class="my-2">
                                @endif
                                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:8px">
                                    Zapisz jako nowy widok
                                </div>
                                <div class="d-flex gap-2">
                                    <input wire:model="saveViewName" type="text"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Nazwa widoku…"
                                           wire:keydown.enter="saveView"
                                           @click.stop>
                                    <button type="button" wire:click="saveView" @click="open=false"
                                            class="btn btn-sm btn-primary flex-shrink-0"
                                            title="Zapisz">
                                        <i class="bi bi-floppy"></i>
                                    </button>
                                </div>
                                <label class="form-check form-check-compact mt-2 mb-0" @click.stop>
                                    <input type="checkbox" class="form-check-input" wire:model="saveViewAsGlobal">
                                    <span class="small">Widok globalny (dla wszystkich)</span>
                                </label>
                            </div>
                        </template>
                    </div>

                    @unless($this->isPlanQueue())
                    {{-- Domyślny widok w menu --}}
                    <button type="button"
                            wire:click="setAsMenuDefaultView"
                            class="btn btn-sm btn-outline-secondary tg-quiet-btn tg-toolbar__home {{ $isMenuDefaultView ? 'is-on' : '' }}"
                            title="{{ $isMenuDefaultView ? 'Ten widok (z filtrami) otwiera się z menu' : 'Ustaw bieżący widok i filtry jako domyślne w menu' }}">
                        <i class="bi bi-house{{ $isMenuDefaultView ? '-fill' : '' }}"></i>
                    </button>
                    @endunless
                @endunless

                @unless($this->isPlanQueue())
                <x-chrono.trigger
                    target="openChronoModal"
                    class="tg-toolbar__chrono"
                    :size="28"
                    label="Chrono Assist"
                    :hint="$filteredTaskHint"
                    hint-loading="Otwieram…"
                    title="Chrono Assist — {{ $filteredTaskHint }} w bieżącym filtrze. Argus podsumuje, Impek zaimportuje, Chrono utworzy, Edi poprawi"
                />
                @endunless

                {{-- Liczba zadań jest w chipie Chrono Assist --}}
                @if($groupBy)
                    <span class="ms-1 badge tg-group-badge d-none d-md-inline-block"
                          title="Przeciągnij zadanie (uchwyt ⋮⋮) na inną grupę, żeby zmienić: {{ $availableColumns[$groupBy]['label'] ?? '' }}"
                          style="font-size:0.65rem;background:rgba(168,85,247,.15);color:#c084fc;border:1px solid rgba(168,85,247,.25)">grupowanie</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if(count($filterChips) > 0)
    @if($this->isPlanQueue())
        @teleport('#wi-plan-filters')
            @include('livewire.partials.tg-active-filters')
        @endteleport
    @else
        @include('livewire.partials.tg-active-filters')
    @endif
@endif
@endunless

<template x-teleport="body">
    <div x-show="filterOpen" x-cloak
         @click.outside="if (!$event.target.closest('.tg-toolbar__filters, .tg-col-filter-mark, .tg-col-menu')) closeFilters()"
         :style="`position:fixed;top:${filterTop}px;left:${filterLeft}px;z-index:1000002;width:${filterWidth}px;max-width:calc(100vw - 24px)`"
         :class="{ 'tg-filter-panel--column': filterMode !== 'all' }"
         class="rp-filter-panel tg-filter-panel-teal">
        @include('livewire.partials.tg-filter-panel')
    </div>
</template>

@include('livewire.partials.tasks-grid-bulk-bar')
@include('livewire.partials.tasks-grid-add-composer')

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- GRID TABLE                                                  --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@php
    $colCount = count($visibleColumns) + 2; // select + expand
    $colHeaderMeta = [];
    foreach ($visibleColumns as $metaKey) {
        $metaCol = $availableColumns[$metaKey] ?? null;
        if (! $metaCol) {
            continue;
        }
        $colHeaderMeta[$metaKey] = [
            'label' => $metaCol['label'],
            'sortable' => (bool) ($metaCol['sortable'] ?? false),
            'filterable' => $this->columnIsFilterable($metaKey),
            'canHide' => ! ($metaCol['always'] ?? false)
                && ! ($groupBy !== '' && $metaKey === $groupBy)
                && ! ($this->isLockedToSprint() && $metaKey === 'sprint')
                && ! ((! $this->usesWorkItems()) && $metaKey === 'type'),
        ];
    }
@endphp

@if($layout !== 'cards')
<div class="card border-0 shadow-sm tg-table-wrap"
     :style="Object.entries(colWidths).map(([k, v]) => `--tg-w-${k}:${Number(v)}px`).join(';')"
     x-data="{
         resizing: null,
         startX: 0,
         startW: 0,
         colMenu: null,
         colMenuMode: 'actions',
         colMenuT: 0,
         colMenuL: 0,
         colPointerX: 0,
         colPointerY: 0,
         colMeta: @js($colHeaderMeta),
         colWidths: @js($columnWidths),
         closeColPopovers() {
             this.colMenu = null;
             this.colMenuMode = 'actions';
         },
         startResize(e, col) {
             this.resizing = col;
             this.startX   = e.clientX;
             this.startW   = e.target.closest('th').offsetWidth;
             this._pendingW = this.startW;
             document.documentElement.classList.add('tg-resizing');
         },
         doResize(e) {
             if (!this.resizing) return;
             const w = Math.max(50, this.startW + e.clientX - this.startX);
             this._pendingW = w;
             const col = this.$el.querySelector('col[data-col=' + this.resizing + ']');
             if (col) {
                 col.style.width = w + 'px';
                 col.style.minWidth = w + 'px';
                 col.style.maxWidth = w + 'px';
             }
             this.$el.style.setProperty('--tg-w-' + this.resizing, w + 'px');
         },
         endResize() {
             if (!this.resizing) return;
             const w = this._pendingW != null ? this._pendingW : this.colWidths[this.resizing];
             this.colWidths = { ...this.colWidths, [this.resizing]: w };
             $wire.setColumnWidth(this.resizing, w);
             this.resizing = null;
             this._pendingW = null;
             document.documentElement.classList.remove('tg-resizing');
         },
         openColMenu(e, key) {
             if (window._tgColDragging || this.resizing) return;
             const dx = e.clientX - this.colPointerX;
             const dy = e.clientY - this.colPointerY;
             if ((dx * dx + dy * dy) >= 37) return;
             e.stopPropagation();
             this.$dispatch('tg-close-filters');
             if (this.colMenu === key && this.colMenuMode === 'actions') {
                 this.closeColPopovers();
                 return;
             }
             const r = e.currentTarget.getBoundingClientRect();
             this.colMenuT = r.bottom + 4;
             this.colMenuL = Math.max(4, Math.min(r.left, window.innerWidth - 230));
             this.colMenuMode = 'actions';
             this.$nextTick(() => { this.colMenu = key; });
         },
         emitColFilter(key, top, left) {
             this.closeColPopovers();
             this.$nextTick(() => this.$dispatch('tg-open-col-filter', { key, top, left }));
         },
         openColFilter(e, key) {
             if (window._tgColDragging || this.resizing) return;
             e.stopPropagation();
             const th = e.currentTarget.closest('th') || e.currentTarget;
             const r = th.getBoundingClientRect();
             this.emitColFilter(key, r.bottom + 4, r.left);
         },
         openColFilterFromMenu() {
             this.emitColFilter(this.colMenu, this.colMenuT, this.colMenuL);
         }
     }"
     @mousemove.window="resizing && doResize($event)"
     @mouseup.window="endResize()"
     @keydown.escape.window="closeColPopovers()"
     @tg-close-col-menu.window="closeColPopovers()">

    <div class="tg-scroll-container" style="overflow-x:auto; overflow-y:auto; max-height:calc(100vh - 268px)">
        <table class="table table-sm tg-table mb-0" style="min-width:640px; border-collapse:separate; border-spacing:0">

            {{-- ── Colgroup for column widths (Alpine-driven, updates on resize) ── --}}
            <colgroup>
                <col style="width:36px; min-width:36px">
                <col style="width:36px; min-width:36px">
                @foreach($visibleColumns as $colKey)
                @php
                    $defaultColStyle = match ($colKey) {
                        'priority' => 'width:10rem;max-width:10rem',
                        'sprint', 'category' => 'width:16rem;max-width:16rem',
                        default => '',
                    };
                @endphp
                <col data-col="{{ $colKey }}" :style="colWidths['{{ $colKey }}'] ? `width:${colWidths['{{ $colKey }}']}px;min-width:${colWidths['{{ $colKey }}']}px;max-width:${colWidths['{{ $colKey }}']}px` : '{{ $defaultColStyle }}'">
                @endforeach
            </colgroup>

            {{-- ── Header ── --}}
            <thead>
                <tr>
                    <th style="width:36px; padding:8px 4px; border-bottom:none; text-align:center">
                        <x-ui.input type="checkbox"
                                    id="tg-select-all"
                                    class="form-check-compact form-check-table tg-select mb-0"
                                    :checked="$this->pageIsFullySelected() && $listedIds !== []"
                                    wire:click="toggleSelectVisible"
                                    aria-label="Zaznacz widoczne" />
                    </th>
                    <th style="width:36px; padding:8px 4px; border-bottom:none"></th>

                    @php $activeChipKeys = array_column($filterChips, 'key'); @endphp
                    @foreach($visibleColumns as $colKey)
                    @php
                        $col = $availableColumns[$colKey] ?? null;
                        $colMetaRow = $colHeaderMeta[$colKey] ?? null;
                        $colFilterable = (bool) ($colMetaRow['filterable'] ?? false);
                        $colFiltered = $colFilterable && array_intersect($this->columnFilterChipKeys($colKey), $activeChipKeys) !== [];
                    @endphp
                    @if($col)
                    <th data-col="{{ $colKey }}"
                        style="position:relative; padding:8px 20px 8px 8px; border-bottom:none; white-space:nowrap"
                        @class(['sortable' => $col['sortable'] ?? false, 'tg-col--filtered' => $colFiltered])
                        @mousedown="colPointerX = $event.clientX; colPointerY = $event.clientY"
                        @click="openColMenu($event, '{{ $colKey }}')">
                        <span style="pointer-events:none; user-select:none">
                            {{ $col['label'] }}
                            @if(($col['sortable'] ?? false) && $sortField === $colKey)
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1 text-primary" style="font-size:0.7rem"></i>
                            @elseif($col['sortable'] ?? false)
                                <i class="bi bi-arrow-down-up ms-1 opacity-25" style="font-size:0.65rem"></i>
                            @endif
                        </span>
                        @if($colKey === 'due_date')
                            <button type="button"
                                    class="tg-col-legend"
                                    data-tip="Klik na dacie: do tego dnia włącznie. × : później niż ta data (karty z terminem)."
                                    aria-label="Jak działa filtr Do kiedy"
                                    @click.stop>
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                            </button>
                        @endif
                        @if($colFiltered)
                            <button type="button"
                                    class="tg-col-filter-mark"
                                    title="Filtr aktywny — kliknij, aby zmienić"
                                    aria-label="Filtr kolumny {{ $col['label'] }}"
                                    @click.stop="openColFilter($event, '{{ $colKey }}')">
                                <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                            </button>
                        @endif
                        <div class="tg-resize-handle"
                             @mousedown.stop.prevent="startResize($event, '{{ $colKey }}')"
                             @click.stop></div>
                    </th>
                    @endif
                    @endforeach
                </tr>
            </thead>

            {{-- ── Body ── --}}
            <tbody>
                @if($groupedTasks)
                    {{-- GROUPED VIEW --}}
                    @foreach($groupedTasks as $groupValue => $groupItems)
                    @include('livewire.partials.tasks-grid-group-header', [
                        'groupName' => $this->groupKeyFor($groupItems->first()),
                        'groupValue' => (string) $groupValue,
                        'groupItems' => $groupItems,
                        'groupSubtitle' => $groupBy === 'sprint' ? $groupItems->first()?->sprint?->goal : null,
                    ])
                    @unless($this->isGroupCollapsed((string) $groupValue))
                        @foreach($groupItems as $task)
                            @include('livewire.partials.tasks-grid-row', compact('task'))
                        @endforeach
                    @endunless
                    @endforeach

                @elseif($tasks && $tasks->count() > 0)
                    {{-- FLAT / PAGINATED VIEW --}}
                    @foreach($tasks as $task)
                        @include('livewire.partials.tasks-grid-row', compact('task'))
                    @endforeach

                @else
                    {{-- EMPTY STATE --}}
                    <tr>
                        <td colspan="{{ $colCount }}" class="text-center py-5">
                            <i class="bi bi-inbox d-block mb-2 opacity-25" style="font-size:2.2rem"></i>
                            <div class="tg-mono" style="font-size:0.68rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted,#94a3b8)">Brak wyników</div>
                            <div class="mt-1" style="font-size:0.86rem; color:rgba(255,255,255,0.4)">Żadne zadanie nie spełnia obecnych kryteriów</div>
                            @if($searchTask || $searchCategory || $searchAssignedTo)
                                <button wire:click="clearFilters" class="btn btn-sm btn-link mt-2">Wyczyść filtry</button>
                            @endif
                        </td>
                    </tr>
                @endif

            </tbody>
        </table>
    </div>

    <template x-teleport="body">
        <div x-cloak
             class="dropdown-menu py-1 shadow-lg tg-col-menu"
             :class="{ 'is-open': colMenu, 'tg-col-menu--filter': colMenuMode === 'filter' }"
             @click.outside="closeColPopovers()"
             @click.stop
             :style="`position:fixed;top:${colMenuT}px;left:${colMenuL}px`">
            <div x-show="colMenuMode === 'actions'">
                <button type="button" class="dropdown-item py-2"
                        x-show="colMenu && colMeta[colMenu] && colMeta[colMenu].sortable"
                        @click="$wire.sortColumn(colMenu, 'asc'); closeColPopovers()">
                    Sortuj A → Z
                </button>
                <button type="button" class="dropdown-item py-2"
                        x-show="colMenu && colMeta[colMenu] && colMeta[colMenu].sortable"
                        @click="$wire.sortColumn(colMenu, 'desc'); closeColPopovers()">
                    Sortuj Z → A
                </button>
                <button type="button" class="dropdown-item py-2"
                        x-show="colMenu && colMeta[colMenu] && colMeta[colMenu].filterable"
                        @click.stop="openColFilterFromMenu()">
                    Filtruj…
                </button>
                <button type="button" class="dropdown-item py-2 d-flex align-items-center justify-content-between gap-2"
                        x-show="colMenu && colMeta[colMenu] && colMeta[colMenu].canHide"
                        @click="$wire.toggleColumn(colMenu); closeColPopovers()">
                    <span>Ukryj kolumnę</span>
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </template>

    {{-- Pagination (only in flat view) --}}
    @if($tasks instanceof \Illuminate\Contracts\Pagination\Paginator && $tasks->hasPages())
    <div class="card-footer border-top py-2 px-3 bg-white">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MOBILE CARD LIST (< 768px) — zastępuje tabelę powyżej        --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($layout === 'cards')
<div class="tg-cards">
    @if($groupedTasks)
        @foreach($groupedTasks as $groupValue => $groupItems)
            @include('livewire.partials.tasks-grid-group-header-card', [
                'groupName' => $this->groupKeyFor($groupItems->first()),
                'groupValue' => (string) $groupValue,
                'groupItems' => $groupItems,
            ])
            @unless($this->isGroupCollapsed((string) $groupValue))
                @foreach($groupItems as $task)
                    @include('livewire.partials.tasks-grid-row-card', compact('task'))
                @endforeach
            @endunless
        @endforeach
    @elseif($tasks && $tasks->count() > 0)
        @foreach($tasks as $task)
            @include('livewire.partials.tasks-grid-row-card', compact('task'))
        @endforeach
    @else
        <div class="text-center text-muted py-4">
            <i class="bi bi-inbox display-5 d-block mb-2 opacity-30"></i>
            <div>Brak zadań spełniających kryteria</div>
            @if($this->isPlanQueue())
                <div class="small mt-1">Nic do przypięcia — wszystko ma slot od dziś albo filtr nic nie zostawił.</div>
            @elseif($searchTask || $searchCategory || $searchAssignedTo)
                <button wire:click="clearFilters" class="btn btn-sm btn-link mt-1">Wyczyść filtry</button>
            @endif
        </div>
    @endif

    {{-- Pagination (only in flat view) --}}
    @if($tasks instanceof \Illuminate\Contracts\Pagination\Paginator && $tasks->hasPages())
    <div class="mt-2">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endif

<script>
    document.addEventListener('livewire:init', function () {
        if (!window.Livewire || typeof Livewire.hook !== 'function') return;
        Livewire.hook('commit', function ({ succeed }) {
            succeed(function () {
                document.querySelectorAll('body > ul.tg-teleport-menu').forEach(function (el) {
                    el.remove();
                });
            });
        });
    });
@unless($this->isPlanQueue())
    (function () {
        function applyTgSelection(d) {
            if (!d || window._tgSelApplying) return;
            window._tgSelApplying = true;
            try {
                const ids = new Set((Array.isArray(d.ids) ? d.ids : []).map(Number));
                const count = Number(d.count || 0);
                const root = document.getElementById('xuiv2Tasks');
                if (!root) return;
                root.querySelectorAll('input[id^="tg-sel-"]').forEach(function (input) {
                    const id = Number(input.value);
                    const on = ids.has(id);
                    if (id && input.checked !== on) input.checked = on;
                });
                const all = document.getElementById('tg-select-all');
                if (all && 'checked' in all && all.checked !== !!d.allVisible) {
                    all.checked = !!d.allVisible;
                }
                root.querySelectorAll('[data-tg-id]').forEach(function (el) {
                    el.classList.toggle('is-selected', ids.has(Number(el.getAttribute('data-tg-id'))));
                });
                const bar = document.getElementById('tg-bulk-bar');
                if (bar) {
                    bar.classList.toggle('is-on', count > 0);
                    const countEl = bar.querySelector('[data-tg-bulk-count]');
                    if (countEl) countEl.textContent = 'Wybrano ' + count;
                    const visEl = bar.querySelector('[data-tg-bulk-visible]');
                    if (visEl) visEl.textContent = d.allVisible ? 'Odznacz widoczne' : 'Zaznacz widoczne';
                }
            } finally {
                window._tgSelApplying = false;
            }
        }
        if (!window._tgSelBound) {
            window._tgSelBound = true;
            window.addEventListener('tg-selection-changed', function (e) {
                const detail = e.detail;
                const d = detail && (detail.ids !== undefined || detail.count !== undefined)
                    ? detail
                    : (Array.isArray(detail) ? detail[0] : detail);
                applyTgSelection(d);
            });
        }
    })();

    (function () {
        if (window._tgDndAbort) {
            try { window._tgDndAbort.abort(); } catch (e) {}
        }
        window._tgDndAbort = new AbortController();
        const signal = window._tgDndAbort.signal;
        const opts = { capture: true, signal, passive: false };

        function tgWire() {
            const el = document.getElementById('xuiv2Tasks');
            if (!el || !window.Livewire) return null;
            const id = el.getAttribute('wire:id');
            return id ? window.Livewire.find(id) : null;
        }

        function hitAt(x, y, selector) {
            const stack = document.elementsFromPoint(x, y);
            for (let i = 0; i < stack.length; i++) {
                const n = stack[i];
                if (!n || n.nodeType !== 1) continue;
                if (n.classList && n.classList.contains('tg-drag-ghost')) continue;
                if (n.matches && n.matches(selector)) return n;
                if (n.closest) {
                    const f = n.closest(selector);
                    if (f) return f;
                }
            }
            return null;
        }

        let drag = null;
        let ghost = null;
        let lastEl = null;
        let moveRaf = 0;
        let lastX = 0;
        let lastY = 0;

        function clearMark() {
            if (!lastEl) return;
            lastEl.classList.remove('tg-col-drag-over', 'tg-group-drop', 'tg-row-sub-drop');
            lastEl = null;
        }
        function mark(el, cls) {
            if (lastEl === el) return;
            clearMark();
            lastEl = el;
            if (el) el.classList.add(cls);
        }

        function ghostLabel(d) {
            if (d.type === 'col') return (d.el.textContent || d.col || '').replace(/\s+/g, ' ').trim();
            if (d.type === 'sub') {
                const span = d.el.querySelector('span.flex-grow-1');
                return (span && span.textContent ? span.textContent : 'Podzadanie').trim();
            }
            const row = d.el.closest('tr');
            const a = row && row.querySelector('.tg-facet__value');
            return (a && a.textContent ? a.textContent : 'Zadanie').trim();
        }

        function placeGhost(x, y) {
            if (!ghost) return;
            ghost.style.transform = 'translate(' + (x + 14) + 'px,' + (y + 14) + 'px)';
        }

        function arm() {
            if (!drag || drag.armed) return;
            drag.armed = true;
            window._tgColDragging = drag.type === 'col';
            document.documentElement.classList.add('tg-pointer-drag');
            window.dispatchEvent(new CustomEvent('tg-close-col-menu'));
            window.dispatchEvent(new CustomEvent('tg-close-filters'));
            ghost = document.createElement('div');
            ghost.className = 'tg-drag-ghost';
            ghost.textContent = ghostLabel(drag);
            document.body.appendChild(ghost);
            placeGhost(lastX, lastY);
        }

        function cleanup() {
            const d = drag;
            drag = null;
            if (moveRaf) {
                cancelAnimationFrame(moveRaf);
                moveRaf = 0;
            }
            clearMark();
            if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
            ghost = null;
            document.documentElement.classList.remove('tg-pointer-hold', 'tg-pointer-drag');
            const cap = (d && d.capEl) || (d && d.el);
            if (cap) {
                try {
                    if (cap.hasPointerCapture && cap.hasPointerCapture(d.pointerId)) {
                        cap.releasePointerCapture(d.pointerId);
                    }
                } catch (e) {}
            }
            window._tgColDrag = null;
            window._tgTaskDrag = null;
            window._tgSubDrag = null;
            if (d && d.armed) {
                window._tgSuppressClick = true;
                setTimeout(function () {
                    window._tgSuppressClick = false;
                    window._tgColDragging = false;
                }, 0);
            } else {
                window._tgColDragging = false;
            }
        }

        function updateMarks() {
            moveRaf = 0;
            if (!drag || !drag.armed) return;
            if (drag.type === 'col') {
                const th = hitAt(lastX, lastY, '.tg-table th[data-col]');
                mark(th && th.dataset.col !== drag.col ? th : null, 'tg-col-drag-over');
                return;
            }
            if (drag.type === 'sub') {
                const sub = hitAt(lastX, lastY, '[data-tg-sub-id]');
                const row = hitAt(lastX, lastY, 'tr[data-tg-drop-task]');
                if (sub && Number(sub.dataset.tgSubId) !== drag.id) {
                    mark(sub, 'tg-row-sub-drop');
                } else if (row && row.dataset.tgAcceptsSub === '1') {
                    mark(row, 'tg-row-sub-drop');
                } else {
                    mark(null, '');
                }
                return;
            }
            const row = hitAt(lastX, lastY, 'tr[data-tg-drop-group]');
            if (row && String(row.dataset.tgDropGroup) !== String(drag.fromGroup)) {
                mark(row, 'tg-group-drop');
            } else {
                mark(null, '');
            }
        }

        function reorderDomCols(from, to) {
            const table = document.querySelector('#xuiv2Tasks .tg-table');
            if (!table || from === to) return;
            const esc = (window.CSS && CSS.escape) ? CSS.escape : function (s) { return s; };
            const ths = Array.from(table.querySelectorAll('thead th[data-col]'));
            const iFrom = ths.findIndex(function (th) { return th.dataset.col === from; });
            const iTo = ths.findIndex(function (th) { return th.dataset.col === to; });
            if (iFrom < 0 || iTo < 0) return;
            const after = iFrom < iTo;
            function move(a, b) {
                if (!a || !b || !a.parentNode) return;
                if (after) b.parentNode.insertBefore(a, b.nextSibling);
                else b.parentNode.insertBefore(a, b);
            }
            move(ths[iFrom], ths[iTo]);
            const fromCol = table.querySelector('col[data-col="' + esc(from) + '"]');
            const toCol = table.querySelector('col[data-col="' + esc(to) + '"]');
            move(fromCol, toCol);
            table.querySelectorAll('tr.tg-task-row, tr.tg-add-row').forEach(function (tr) {
                const cells = tr.children;
                move(cells[iFrom + 2], cells[iTo + 2]);
            });
        }

        function commitDrop() {
            if (!drag || !drag.armed || drag.dropped) return;
            drag.dropped = true;
            const wire = tgWire();
            if (!wire) return;
            if (drag.type === 'col') {
                const th = hitAt(lastX, lastY, '.tg-table th[data-col]');
                if (th && th.dataset.col && th.dataset.col !== drag.col) {
                    reorderDomCols(drag.col, th.dataset.col);
                    wire.reorderColumns(drag.col, th.dataset.col);
                }
                return;
            }
            if (drag.type === 'sub') {
                const sub = hitAt(lastX, lastY, '[data-tg-sub-id]');
                const row = hitAt(lastX, lastY, 'tr[data-tg-drop-task]');
                if (sub && row && Number(sub.dataset.tgSubId) !== drag.id) {
                    wire.moveSubtask(drag.id, Number(row.dataset.tgDropTask), Number(sub.dataset.tgSubId));
                } else if (row && row.dataset.tgAcceptsSub === '1' && row.dataset.tgDropTask !== String(drag.fromTask)) {
                    wire.moveSubtask(drag.id, Number(row.dataset.tgDropTask));
                }
                return;
            }
            const row = hitAt(lastX, lastY, 'tr[data-tg-drop-group]');
            if (row && String(row.dataset.tgDropGroup) !== String(drag.fromGroup)) {
                wire.moveTaskToGroup(drag.id, row.dataset.tgDropGroup);
            }
        }

        document.addEventListener('pointerdown', function (e) {
            if (e.button !== 0 || drag) return;
            const src = e.target;
            if (!src || !src.closest) return;
            if (src.closest('.tg-resize-handle, .tg-col-filter-mark, a, button, input, select, textarea, label, .form-check')) return;

            let next = null;
            const th = src.closest('.tg-table th[data-col]');
            const grip = src.closest('.tg-task-grip');
            const sub = src.closest('[data-tg-sub-id]');
            if (th) {
                next = { type: 'col', col: th.dataset.col, el: th };
            } else if (grip) {
                const row = grip.closest('tr[data-tg-drop-task]');
                if (!row) return;
                next = {
                    type: 'task',
                    id: Number(row.dataset.tgDropTask),
                    fromGroup: row.dataset.tgDropGroup,
                    el: grip,
                };
            } else if (sub) {
                const row = sub.closest('tr[data-tg-drop-task]');
                next = {
                    type: 'sub',
                    id: Number(sub.dataset.tgSubId),
                    fromTask: row ? row.dataset.tgDropTask : '',
                    el: sub,
                };
            } else {
                return;
            }

            lastX = e.clientX;
            lastY = e.clientY;
            const capEl = (src.nodeType === 1 ? src : src.parentElement) || next.el;
            drag = Object.assign(next, {
                x0: e.clientX,
                y0: e.clientY,
                pointerId: e.pointerId,
                armed: false,
                dropped: false,
                capEl: capEl,
            });
            document.documentElement.classList.add('tg-pointer-hold');
            if (next.type !== 'col' && e.cancelable) e.preventDefault();
            try { capEl.setPointerCapture(e.pointerId); } catch (err) {}
        }, opts);

        document.addEventListener('pointermove', function (e) {
            if (!drag || e.pointerId !== drag.pointerId) return;
            lastX = e.clientX;
            lastY = e.clientY;
            const dx = lastX - drag.x0;
            const dy = lastY - drag.y0;
            if (!drag.armed && (dx * dx + dy * dy) >= 36) {
                arm();
            }
            if (!drag.armed) return;
            if (e.cancelable) e.preventDefault();
            placeGhost(lastX, lastY);
            if (!moveRaf) moveRaf = requestAnimationFrame(updateMarks);
        }, opts);

        document.addEventListener('pointerup', function (e) {
            if (!drag || e.pointerId !== drag.pointerId) return;
            lastX = e.clientX;
            lastY = e.clientY;
            if (drag.armed) {
                if (e.cancelable) e.preventDefault();
                commitDrop();
            }
            cleanup();
        }, opts);

        document.addEventListener('pointercancel', function (e) {
            if (!drag || e.pointerId !== drag.pointerId) return;
            cleanup();
        }, opts);

        document.addEventListener('lostpointercapture', function (e) {
            if (!drag || e.pointerId !== drag.pointerId) return;
            if (drag.armed) commitDrop();
            cleanup();
        }, opts);

        window.addEventListener('blur', function () {
            if (drag) cleanup();
        }, { signal: signal });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drag) cleanup();
        }, opts);

        document.addEventListener('click', function (e) {
            if (!window._tgSuppressClick) return;
            e.preventDefault();
            e.stopPropagation();
        }, opts);

        document.addEventListener('dragstart', function (e) {
            const t = e.target;
            if (t && t.closest && t.closest('.tg-table, .tg-subtask-item, .tg-task-grip')) {
                e.preventDefault();
            }
        }, opts);
    })();

    (function () {
        const mq = window.matchMedia('(max-width: 767.98px)');
        function desired() { return mq.matches ? 'cards' : 'table'; }
        function persist(layout) {
            document.cookie = 'tg_layout=' + layout + ';path=/;max-age=31536000;SameSite=Lax';
        }
        function sync() {
            const layout = desired();
            persist(layout);
            const wire = (function () {
                const el = document.getElementById('xuiv2Tasks');
                if (!el || !window.Livewire) return null;
                const id = el.getAttribute('wire:id');
                return id ? window.Livewire.find(id) : null;
            })();
            if (!wire) return;
            const current = typeof wire.get === 'function' ? wire.get('layout') : wire.layout;
            if (current === layout) return;
            wire.setLayout(layout);
        }
        persist(desired());
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', sync);
        } else {
            queueMicrotask(sync);
        }
        if (mq.addEventListener) mq.addEventListener('change', sync);
        else mq.addListener(sync);
    })();
@endunless

    (function () {
        const pending = new Map();

        function tgRoot() {
            return document.getElementById('xuiv2Tasks');
        }

        function tgWire() {
            const el = tgRoot();
            if (!el || !window.Livewire) return null;
            const id = el.getAttribute('wire:id');
            return id ? window.Livewire.find(id) : null;
        }

        function skelHtml() {
            return '<div class="tg-expand-skel" aria-busy="true">'
                + '<div class="tg-expand-skel__head">'
                + '<span class="tg-expand-spinner" aria-hidden="true"><\/span>'
                + '<span>Wczytuję szczegóły…<\/span>'
                + '<\/div>'
                + '<i class="tg-expand-skel__line"></i>'
                + '<i class="tg-expand-skel__line"></i>'
                + '<i class="tg-expand-skel__line"></i>'
                + '<\/div>';
        }

        function panel(id) {
            const root = tgRoot();
            return root ? root.querySelector('[data-tg-expand-for="' + id + '"]') : null;
        }

        function expandBtn(id) {
            const root = tgRoot();
            return root ? root.querySelector('.tg-expand-btn[data-tg-expand="' + id + '"]') : null;
        }

        function setOpenUi(btn, id, open) {
            if (!btn) btn = expandBtn(id);
            if (!btn) return;
            btn.classList.toggle('is-open', open);
            btn.classList.toggle('is-opening', !open ? false : btn.classList.contains('is-opening'));
            if (!open) btn.classList.remove('is-opening');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.title = open ? 'Zwiń' : 'Rozwiń';
            const row = btn.closest('tr.tg-task-row');
            if (row) row.classList.toggle('tg-expanded', open);
            const card = btn.closest('.tg-dt-card, .dt-card');
            if (card) card.classList.toggle('is-expanded', open);
        }

        function applyCounts(id, done, total) {
            if (done == null || total == null) return;
            const root = tgRoot();
            if (!root) return;
            root.querySelectorAll('[data-tg-sub-stats="' + id + '"]').forEach(function (el) {
                el.textContent = done + '/' + total;
                el.setAttribute('data-tip', done + '/' + total + ' podzadań');
            });
            root.querySelectorAll('[data-tg-sub-bar="' + id + '"]').forEach(function (el) {
                const pct = total > 0 ? Math.round((done / total) * 100) : 0;
                el.style.width = pct + '%';
                el.style.background = (total > 0 && done === total) ? '#10b981' : '#a855f7';
            });
        }

        function insertPending(btn, id) {
            const row = btn && btn.closest('tr.tg-task-row');
            if (row) {
                const tr = document.createElement('tr');
                tr.className = 'tg-expand-row tg-expand-pending';
                tr.setAttribute('data-tg-expand-for', String(id));
                const spacer = document.createElement('td');
                spacer.style.cssText = 'width:36px;padding:0 !important;background:rgba(10,15,29,0.6) !important';
                const cell = document.createElement('td');
                cell.colSpan = Math.max(1, row.cells.length - 1);
                cell.innerHTML = '<div class="tg-expand-body">' + skelHtml() + '<\/div>';
                tr.appendChild(spacer);
                tr.appendChild(cell);
                row.after(tr);
                return tr;
            }
            const card = btn && btn.closest('.tg-dt-card, .dt-card');
            if (card) {
                const box = document.createElement('div');
                box.className = 'tg-card-expand tg-expand-body tg-expand-pending';
                box.setAttribute('data-tg-expand-for', String(id));
                box.innerHTML = skelHtml();
                card.appendChild(box);
                return box;
            }
            return null;
        }

        function hostFor(btn, id) {
            if (btn) {
                return {
                    row: btn.closest('tr.tg-task-row'),
                    card: btn.closest('.tg-dt-card, .dt-card'),
                };
            }
            const fallback = expandBtn(id);
            return {
                row: fallback ? fallback.closest('tr.tg-task-row') : null,
                card: fallback ? fallback.closest('.tg-dt-card, .dt-card') : null,
            };
        }

        function mountHtml(btn, id, html) {
            html = (html || '').trim();
            if (!html) return;
            const existing = panel(id);
            if (existing) {
                existing.outerHTML = html;
            } else {
                const host = hostFor(btn, id);
                if (host.row) host.row.insertAdjacentHTML('afterend', html);
                else if (host.card) host.card.insertAdjacentHTML('beforeend', html);
            }
            const mounted = panel(id);
            if (mounted) {
                mounted.hidden = false;
                mounted.classList.remove('tg-expand-pending');
                if (window.Alpine && typeof Alpine.initTree === 'function') {
                    Alpine.initTree(mounted);
                }
            }
        }

        window.tgApplyExpand = function (p) {
            if (!p || p.id == null) return;
            const id = Number(p.id);
            const btn = expandBtn(id);
            if (p.open === false) {
                const el = panel(id);
                if (el) {
                    if (el.classList.contains('tg-expand-pending')) el.remove();
                    else el.hidden = true;
                }
                setOpenUi(btn, id, false);
                pending.delete(id);
                return;
            }
            setOpenUi(btn, id, true);
            if (p.html) {
                mountHtml(btn, id, p.html);
                pending.delete(id);
                if (btn) btn.classList.remove('is-opening');
            } else {
                const el = panel(id);
                if (el) el.hidden = false;
            }
            applyCounts(id, p.subDone, p.subTotal);
        };

        document.addEventListener('click', function (e) {
            const btn = e.target && e.target.closest && e.target.closest('#xuiv2Tasks .tg-expand-btn');
            if (!btn) return;
            const id = Number(btn.getAttribute('data-tg-expand'));
            if (!id) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            const wire = tgWire();
            if (!wire) return;

            const opening = btn.getAttribute('aria-expanded') !== 'true' && !btn.classList.contains('is-open');
            const el = panel(id);

            if (!opening) {
                if (el) {
                    if (el.classList.contains('tg-expand-pending')) el.remove();
                    else el.hidden = true;
                }
                setOpenUi(btn, id, false);
                pending.delete(id);
                wire.toggleExpand(id, true, false, false);
                return;
            }

            setOpenUi(btn, id, true);
            if (el && !el.classList.contains('tg-expand-pending')) {
                el.hidden = false;
                wire.toggleExpand(id, true, false, true);
                return;
            }

            if (!el) insertPending(btn, id);
            else el.hidden = false;
            const token = {};
            pending.set(id, token);
            btn.classList.add('is-opening');
            wire.toggleExpand(id, true, true, true).then(function (html) {
                if (pending.get(id) !== token) return;
                pending.delete(id);
                btn.classList.remove('is-opening');
                if (html) mountHtml(btn, id, html);
                else {
                    const sk = panel(id);
                    if (sk && sk.classList.contains('tg-expand-pending')) sk.remove();
                }
            }).catch(function () {
                if (pending.get(id) !== token) return;
                pending.delete(id);
                btn.classList.remove('is-opening');
                setOpenUi(btn, id, false);
                const sk = panel(id);
                if (sk && sk.classList.contains('tg-expand-pending')) sk.remove();
            });
        }, true);
    })();
</script>

@if($showChronoModal)
    @if($chronoMode === 'menu')
        <livewire:chrono-assist
            context="grid"
            :context-chips="$chronoFilterLabels"
            :item-count="$chronoItemCount"
            wire:key="tasks-grid-chrono-assist"
        />
    @else
    <x-chrono.modal
        key="tasks-grid-chrono"
        close="closeChronoModal"
        :fetch="$chronoMode === 'summary' && $chronoLoading ? 'fetchChronoSummary' : null"
        :loading="$chronoLoading"
        :error="$chronoError"
        :ready="$chronoMode === 'menu' || $chronoMode === 'import' || $chronoMode === 'export' || $chronoMode === 'edi-import' || $chronoMode === 'edi-export' || $chronoSummary !== null"
        :title="match ($chronoMode) {
            'edi-import' => 'Edi — wklej JSON',
            'edi-export' => 'Edi — eksport JSON',
            default => 'AskChrono — lista zadań',
        }"
        :status-ready="match ($chronoMode) {
            'menu' => 'Wybierz akcję dla bieżącego filtra',
            'summary' => 'Podsumowanie gotowe',
            'import' => 'Mam '.count($importProposals).' propozycji — sprawdź i zatwierdź',
            'export' => 'Eksport: '.$exportCount.($exportTotal > $exportCount ? ' z '.$exportTotal : '').' zadań',
            'edi-import' => 'Wklej changes[] z ChatGPT albo tasks[] z Impki',
            'edi-export' => 'Paczka dla promptu: '.$exportCount.($exportTotal > $exportCount ? ' z '.$exportTotal : '').' zadań',
            default => 'Sprawdź i zatwierdź',
        }"
        :thinking="$chronoMode === 'summary'
            ? 'Chrono czyta aktywne filtry i próbkę zadań z listy…'
            : 'Chrono przygotowuje propozycję.'"
        empty-message="Wybierz podsumowanie, import albo eksport."
        dialog-class="modal-lg modal-dialog-scrollable"
    >
        @if($chronoMode === 'menu')
            <p class="text-muted small mb-3">
                Działam na <strong>bieżącym filtrze</strong> listy — te same chipy, które widzisz nad tabelą.
                @if(count($chronoFilterLabels) > 0)
                    <span class="d-block mt-1">{{ implode(' · ', $chronoFilterLabels) }}</span>
                @endif
            </p>
            <div class="d-grid gap-2">
                <button type="button"
                        class="btn btn-outline-primary text-start"
                        wire:click="chronoChooseSummary"
                        @disabled(! $llmConfigured)>
                    <i class="bi bi-journal-text me-2"></i>
                    <span class="fw-semibold">Podsumuj widok</span>
                    <span class="d-block small text-muted ms-4">Narracja z ryzyka i wyróżnień na podstawie przefiltrowanych zadań</span>
                </button>
                <button type="button" class="btn btn-outline-secondary text-start" wire:click="chronoChooseImport">
                    <i class="bi bi-box-arrow-in-down me-2"></i>
                    <span class="fw-semibold">Importuj zadania</span>
                    <span class="d-block small text-muted ms-4">
                        Wklej JSON / listę — nowe taski dostaną kontekst filtra
                        @if($chronoImportDefaultsHint !== '')
                            ({{ $chronoImportDefaultsHint }})
                        @endif
                    </span>
                </button>
                <button type="button" class="btn btn-outline-secondary text-start" wire:click="chronoChooseExport">
                    <i class="bi bi-box-arrow-up me-2"></i>
                    <span class="fw-semibold">Eksportuj zadania</span>
                    <span class="d-block small text-muted ms-4">Pobierz JSON z bieżącego filtra — ten sam format co import</span>
                </button>
            </div>
            @unless($llmConfigured)
                <p class="small text-muted mt-3 mb-0">Podsumowanie wymaga skonfigurowanego AI w Akcjach systemowych. Import JSON / listy działa bez modelu.</p>
            @endunless
        @elseif($chronoMode === 'summary' && $chronoSummary)
            <h6 class="fw-semibold mb-2">{{ $chronoSummary['headline'] }}</h6>
            <p class="mb-3" style="line-height:1.55">{{ $chronoSummary['summary'] }}</p>
            @if(($chronoSummary['highlights'] ?? []) !== [])
                <div class="mb-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">Wyróżnienia</div>
                    <ul class="mb-0 ps-3">
                        @foreach($chronoSummary['highlights'] as $item)
                            <li class="small mb-1">{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(($chronoSummary['risks'] ?? []) !== [])
                <div>
                    <div class="small text-warning text-uppercase fw-semibold mb-1">Ryzyka</div>
                    <ul class="mb-0 ps-3">
                        @foreach($chronoSummary['risks'] as $item)
                            <li class="small mb-1">{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @elseif($chronoMode === 'import')
            @if($importProposals === [])
                @if($importMode === 'list')
                    <p class="text-muted small mb-3">
                        Jedna linia = jedno zadanie. Notacja jak w komentarzach:
                        <code>zrób kolacje@karol -//ma być smaczna</code>
                        — tytuł, <code>@osoba</code> i opis po <code>//</code>.
                        Nic nie trafi do bazy bez zatwierdzenia.
                    </p>
                    <label class="form-label small fw-semibold">Lista linii</label>
                    <textarea rows="9" class="form-control font-monospace" wire:model.defer="importText"
                              placeholder="zrób kolacje@karol -//ma być smaczna"
                              spellcheck="false"></textarea>
                @else
                    <p class="text-muted small mb-3">
                        Wklej JSON z tablicą <code>tasks</code>. Impka <strong>tylko tworzy nowe</strong> rekordy — id z eksportu są ignorowane. Edycja istniejących to Edi, nie import.
                        Brakujące pola biorą się z filtra. Nic nie trafi do bazy bez zatwierdzenia.
                    </p>
                    <details class="mb-3">
                        <summary class="small fw-semibold" style="cursor:pointer">Oczekiwany format JSON</summary>
                        <pre class="small mb-0 mt-2 p-3 rounded" style="background:rgba(0,0,0,.25);border:1px solid var(--glass-border);max-height:180px;overflow:auto"><code>{{ $importFormatExample }}</code></pre>
                    </details>
                    <label class="form-label small fw-semibold">Wklej JSON</label>
                    <textarea rows="9" class="form-control font-monospace" wire:model.defer="importText"
                              placeholder='{"tasks":[{"name":"Pierwsze zadanie","subtasks":["Krok 1"]}]}'
                              spellcheck="false"></textarea>
                @endif
            @else
                <p class="text-muted small mb-3">
                    Propozycje z kontekstem filtra. Możesz poprawić nazwę przed zapisem.
                </p>
                <ul class="list-unstyled mb-0">
                    @foreach($importProposals as $index => $proposal)
                        <li class="d-flex align-items-start gap-2 mb-2 p-2 rounded"
                            style="background:rgba(255,255,255,.03);border:1px solid var(--glass-border)"
                            wire:key="import-proposal-{{ $index }}">
                            <input type="checkbox" class="form-check-input mt-1" value="{{ $index }}" wire:model="importSelected">
                            <div class="flex-grow-1">
                                <input type="text" class="form-control form-control-sm mb-1"
                                       wire:model.defer="importProposals.{{ $index }}.name">
                                @if(($proposal['meta'] ?? '') !== '')
                                    <div class="small text-muted">{{ $proposal['meta'] }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        @elseif($chronoMode === 'export')
            <p class="text-muted small mb-3">
                Eksport z bieżącego filtra
                @if($exportTotal > $exportCount)
                    (pierwsze {{ $exportCount }} z {{ $exportTotal }})
                @else
                    ({{ $exportCount }} {{ $exportCount === 1 ? 'zadanie' : 'zadań' }})
                @endif
                — zrzut filtra dla Ediego: typy, kolumny siatki i podzadania. Import JSON nie aktualizuje po id.
            </p>
            <textarea
                id="chronoExportJson"
                rows="12"
                class="form-control font-monospace"
                readonly
                spellcheck="false"
            >{{ $exportJson }}</textarea>
        @elseif($chronoMode === 'edi-import')
            <p class="text-muted small mb-3">
                Bez tokenów — PHP robi DIFF względem żywych rekordów z filtra.
                Wklej <code>{"changes":[{"id":123,"field":"category","value":"Transport"}]}</code>
                z ChatGPT albo <code>{"tasks":[…]}</code> z eksportu Impki (edytowane pola).
                Nic nie trafi do bazy bez zatwierdzenia w tabeli.
            </p>
            <details class="mb-3">
                <summary class="small fw-semibold" style="cursor:pointer">Oczekiwany format</summary>
                <pre class="small mb-0 mt-2 p-3 rounded" style="background:rgba(0,0,0,.25);border:1px solid var(--glass-border);max-height:180px;overflow:auto"><code>{
  "changes": [
    {"id": 123, "field": "category", "value": "Transport"}
  ]
}</code></pre>
            </details>
            <label class="form-label small fw-semibold">Wklej JSON Ediego</label>
            <textarea rows="9" class="form-control font-monospace" wire:model.defer="importText"
                      placeholder='{"changes":[{"id":123,"field":"name","value":"Poprawiona nazwa"}]}'
                      spellcheck="false"></textarea>
        @elseif($chronoMode === 'edi-export')
            <p class="text-muted small mb-3">
                Paczka dla ChatGPT: instrukcja + snapshot filtra
                @if($this->isEdiReviewing())
                    + aktualne propozycje (po ręcznej korekcie).
                @else
                    (jeszcze bez zmian — wklej odpowiedź z powrotem przez „Wklej JSON”).
                @endif
                Pola poza name / description / category / priority / due_date są ignorowane przy imporcie.
            </p>
            <textarea
                id="chronoExportJson"
                rows="12"
                class="form-control font-monospace"
                readonly
                spellcheck="false"
            >{{ $exportJson }}</textarea>
        @endif

        <x-slot:footer>
            @if($chronoMode !== 'menu' && ! $this->isEdiReviewing())
                <button type="button" class="btn btn-outline-secondary" wire:click="chronoBackToMenu">Wstecz</button>
            @endif
            <button type="button" class="btn btn-outline-secondary" wire:click="closeChronoModal">Zamknij</button>
            @if($chronoMode === 'import' && $importProposals === [])
                <button type="button" class="btn btn-primary" wire:click="parseImportText"
                        wire:loading.attr="disabled" wire:target="parseImportText">
                    <span wire:loading.remove wire:target="parseImportText">Wczytaj propozycje</span>
                    <span wire:loading wire:target="parseImportText">Parsuję…</span>
                </button>
            @elseif($chronoMode === 'import')
                <button type="button" class="btn btn-primary" wire:click="confirmImportProposals"
                        wire:loading.attr="disabled" wire:target="confirmImportProposals"
                        @disabled(count($importSelected) === 0)>
                    Zastosuj zaznaczone ({{ count($importSelected) }})
                </button>
            @elseif($chronoMode === 'edi-import')
                <button type="button" class="btn btn-primary" wire:click="parseEdiImportText"
                        wire:loading.attr="disabled" wire:target="parseEdiImportText">
                    <span wire:loading.remove wire:target="parseEdiImportText">Pokaż DIFF</span>
                    <span wire:loading wire:target="parseEdiImportText">Parsuję…</span>
                </button>
            @elseif(($chronoMode === 'export' || $chronoMode === 'edi-export') && $exportJson !== '')
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    x-data
                    @click="
                        navigator.clipboard.writeText(document.getElementById('chronoExportJson').value);
                        $el.textContent = 'Skopiowano';
                        setTimeout(() => $el.textContent = 'Kopiuj JSON', 1500);
                    "
                >
                    Kopiuj JSON
                </button>
                <button type="button" class="btn btn-primary" wire:click="downloadChronoExport"
                        wire:loading.attr="disabled" wire:target="downloadChronoExport">
                    <i class="bi bi-download me-1"></i> Pobierz plik
                </button>
            @endif
        </x-slot:footer>
    </x-chrono.modal>
    @endif
@endif
</div>
