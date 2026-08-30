<div class="xuiv2-tasks{{ $this->isEdiReviewing() ? ' is-edi-review' : '' }}" id="xuiv2Tasks">
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

    /* Magnetyczne CTA — zostaje lokalne (opt-in przez klasę),
       bo odpalanie tego na WSZYSTKICH .btn-primary w gęstych tabelach CRUD (dziesiątki
       przycisków akcji per wiersz) odtworzyłoby ten sam problem z wydajnością, który
       naprawiliśmy przy /tasks2 (patrz commit o N+1 / mousemove). Globalna poświata
       kursora (.cl-cursor-glow) jest tania — jeden element — więc jest już globalna. */
    .xuiv2-magnetic { will-change: transform; transition: transform .15s cubic-bezier(.2,.8,.2,1); }
    @media (prefers-reduced-motion: reduce) { .xuiv2-magnetic { display: none !important; } }

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
    .tg-status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 20px;
        font-size: 0.76rem; font-weight: 600; line-height: 1.3;
        white-space: nowrap; border: none;
        transition: filter .15s;
    }
    button.tg-status-badge:hover { filter: brightness(1.18); cursor: pointer; }
    .tg-status-badge.s-pending    { background: rgba(245,158,11,.18); color: #f59e0b; border: 1px solid rgba(245,158,11,.35); }
    .tg-status-badge.s-in_progress{ background: rgba(168,85,247,.18); color: #c084fc; border: 1px solid rgba(168,85,247,.35); }
    .tg-status-badge.s-completed  { background: rgba(16,185,129,.18); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
    .tg-status-badge.s-cancelled  { background: rgba(239,68,68,.18);  color: #f87171; border: 1px solid rgba(239,68,68,.35); }

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

    /* ── Footer / add-task rows ── */
    .tg-footer-row > td {
        background: rgba(255,255,255,0.02) !important;
        border-top: 1px dashed rgba(255,255,255,0.1) !important;
        padding: 8px 12px !important;
        color: var(--text-muted, #94a3b8) !important;
    }
    .tg-add-row > td {
        border-top: 1px dashed rgba(255, 255, 255, 0.1) !important;
        background: rgba(255, 255, 255, 0.02) !important;
        padding: 5px 6px !important;
    }
    .tg-add-row .form-control,
    .tg-add-row .form-select {
        background: rgba(15, 23, 42, 0.45) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        color: var(--text-main, #f1f5f9) !important;
        font-size: 0.8rem !important;
        box-shadow: none !important;
    }
    .tg-add-row .form-control:focus,
    .tg-add-row .form-select:focus {
        border-color: rgba(168, 85, 247, 0.35) !important;
        box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.12) !important;
    }
    .tg-add-row .tg-add-submit,
    .tg-add-card .tg-add-submit {
        background: rgba(168, 85, 247, 0.12) !important;
        border: 1px solid rgba(168, 85, 247, 0.28) !important;
        color: #d8b4fe !important;
        box-shadow: none !important;
        filter: none !important;
        transform: none !important;
    }
    .tg-add-row .tg-add-submit:hover,
    .tg-add-card .tg-add-submit:hover {
        background: rgba(168, 85, 247, 0.2) !important;
        color: #f1e8ff !important;
        filter: none !important;
        transform: none !important;
        box-shadow: none !important;
    }
    .tg-add-row .tg-add-status {
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 0.68rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--text-muted, #94a3b8);
        white-space: nowrap;
    }

    /* ── Subtask drag-and-drop ── */
    .tg-subtask-item { transition: background .1s; }
    .tg-subtask-item:hover { background: rgba(255,255,255,0.04); }
    .tg-subtask-item[draggable="true"]:active .tg-subtask-grip { color: rgba(255,255,255,.6) !important; }
    .tg-subtask-item .form-check { margin-bottom: 0 !important; }

    /* Drop target on a collapsed task row */
    .tg-task-row.tg-row-sub-drop > td {
        background: rgba(16,185,129,.08) !important;
        box-shadow: inset 0 0 0 2px rgba(16,185,129,.4);
    }

    /* ── Task drag between groups (Kanban) ── */
    .tg-task-grip { cursor: grab; color: rgba(255,255,255,0.22); font-size: 0.95rem; padding: 2px 4px; user-select: none; }
    .tg-task-grip:hover { color: rgba(255,255,255,0.6); }
    .tg-task-grip:active { cursor: grabbing; color: rgba(255,255,255,0.75); }
    .tg-group-header.tg-group-drop > td,
    .tg-task-row.tg-group-drop > td {
        background: rgba(16,185,129,.10) !important;
        box-shadow: inset 0 0 0 2px rgba(16,185,129,.45);
    }

    /* ── Column drag-to-reorder ── */
    .tg-table th[draggable="true"] { cursor: grab; }
    .tg-table th[draggable="true"]:active { cursor: grabbing; }
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
       MOBILE (< 768px): karty zamiast tabeli
       ══════════════════════════════════════════════════════════ */
    .tg-cards { display: none; }

    @media (max-width: 767.98px) {
        /* Tabela znika, karty przejmują ── */
        .tg-table-wrap { display: none !important; }
        .tg-cards {
            display: flex !important;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Jeden zwarty rząd: widok + filtry + liczba + grupowanie. */
        .tg-toolbar__row {
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.4rem;
        }
        .tg-toolbar__search,
        .tg-toolbar__views,
        .tg-toolbar__home {
            display: none !important;
        }
        .tg-toolbar__chrono .ac-trigger__text {
            display: none !important;
        }
        .tg-toolbar__chrono {
            padding: 4px 6px !important;
        }
        .tg-toolbar__meta {
            margin-left: 0 !important;
            flex: 1 1 auto;
            min-width: 0;
            justify-content: flex-end;
        }
        .tg-toolbar__view-label {
            display: inline !important;
            max-width: 38vw;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }
        .tg-toolbar__view-icon { display: none !important; }
        .tg-toolbar .btn { padding: 4px 8px !important; font-size: 0.72rem !important; }
        .tg-active-filters { display: none !important; }
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

    /* ── Add-task (mobile) ── */
    .tg-add-card {
        border: 1px dashed rgba(255, 255, 255, 0.16) !important;
        background: rgba(255, 255, 255, 0.02);
    }
        .tg-add-card .form-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-muted,#94a3b8); margin-bottom: 2px; }

        .tg-add-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.35rem;
        }
        .tg-add-actions .btn {
            padding: 0.14rem 0.55rem !important;
            font-size: 0.68rem !important;
            font-weight: 500 !important;
            line-height: 1.3 !important;
            border-radius: 999px !important;
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid var(--glass-border, rgba(255,255,255,0.1)) !important;
            color: var(--text-muted, #94a3b8) !important;
            text-decoration: none !important;
            box-shadow: none !important;
            width: auto !important;
            gap: 0.25rem !important;
        }
        .tg-add-actions .btn i { font-size: 0.72rem; }
    .xuiv2-tasks.is-edi-review .tg-add-actions { display: none !important; }
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

            {{-- Filtry: jeden przycisk, panel z pogrupowanymi sekcjami (SharePoint-style, jak w rekrutacji) --}}
            <div class="tg-toolbar__filters" x-data="{ open: false, top: 0, left: 0, openStatus: false, openVisibility: false, openType: false, openSearch: false, openGroup: false, openColumns: false }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); const pw=Math.min(600, window.innerWidth-24); top=r.bottom+4; left=Math.max(4, Math.min(r.left, window.innerWidth-pw-4)); open=true"
                        class="btn btn-sm btn-outline-secondary tg-quiet-btn {{ count($this->activeFilterChips()) > 0 ? 'is-on' : '' }}">
                    <i class="bi bi-sliders me-1"></i>Filtry
                    @if(count($this->activeFilterChips()) > 0)
                        <span class="tg-quiet-count">{{ count($this->activeFilterChips()) }}</span>
                    @endif
                    <i class="bi bi-chevron-down ms-1 d-none d-md-inline" style="font-size:.6rem"></i>
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak
                         @click.outside="open = false"
                         :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;`"
                         class="rp-filter-panel tg-filter-panel-teal">
                        @include('livewire.partials.tg-filter-panel')
                    </div>
                </template>
            </div>

            {{-- Zapisane widoki (pigułki) — na mobile tylko aktualny, w menu zakładki --}}
            @unless($this->isLockedToSprint())
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

            {{-- Loading spinner --}}
            <div wire:loading>
                <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:14px;height:14px">
                    <span class="visually-hidden">Ładowanie…</span>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 tg-toolbar__meta">
                @unless($this->isLockedToSprint())
                    {{-- Zapisz / zarządzaj widokami — na mobile pokazuje nazwę aktualnego widoku --}}
                    <div class="tg-toolbar__view-menu" x-data="{ open: false, top: 0, left: 0, pw: 300 }">
                        <button type="button"
                                @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); pw=Math.min(300, window.innerWidth-24); top=r.bottom+4; left=Math.max(4, Math.min(r.right-pw, window.innerWidth-pw-4)); open=true"
                                class="btn btn-sm btn-outline-secondary tg-quiet-btn {{ $activeViewId ? 'is-on' : '' }}"
                                title="Zapisz i zarządzaj widokami">
                            <i class="bi bi-bookmark{{ $view ? '-fill' : '' }} tg-toolbar__view-icon"></i>
                            <span class="tg-toolbar__view-label d-none">{{ $activeViewName ?: 'Domyślny' }}</span>
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

                    {{-- Domyślny widok w menu --}}
                    <button type="button"
                            wire:click="setAsMenuDefaultView"
                            class="btn btn-sm btn-outline-secondary tg-quiet-btn tg-toolbar__home {{ $isMenuDefaultView ? 'is-on' : '' }}"
                            title="{{ $isMenuDefaultView ? 'Ten widok (z filtrami) otwiera się z menu' : 'Ustaw bieżący widok i filtry jako domyślne w menu' }}">
                        <i class="bi bi-house{{ $isMenuDefaultView ? '-fill' : '' }}"></i>
                    </button>
                @endunless

                <x-chrono.trigger
                    target="openChronoModal"
                    class="tg-toolbar__chrono"
                    :size="28"
                    label="Chrono Assist"
                    hint="Filtr"
                    hint-loading="Otwieram…"
                    title="Chrono Assist — Argus podsumuje, Impek zaimportuje, Chrono utworzy, Edi poprawi"
                />

                {{-- Task count --}}
                <span class="tg-mono" style="font-size:0.76rem;color:var(--text-muted,#94a3b8);white-space:nowrap">
                    @if($tasks)
                        {{ $tasks->total() }} zadań
                    @elseif($groupedTasks)
                        {{ $groupedTasks->flatten()->count() }} zadań
                    @endif
                    @if($groupBy)
                        <span class="ms-1 badge"
                              title="Przeciągnij zadanie (uchwyt ⋮⋮) na inną grupę, żeby zmienić: {{ $availableColumns[$groupBy]['label'] ?? '' }}"
                              style="font-size:0.65rem;background:rgba(168,85,247,.15);color:#c084fc;border:1px solid rgba(168,85,247,.25)">grupowanie</span>
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>

@if(count($this->activeFilterChips()) > 0)
    <div class="rp-active-filters tg-active-filters mb-2 px-1">
        <span class="rp-active-filters__label">Filtry:</span>
        @foreach($this->activeFilterChips() as $chip)
            <span class="rp-active-filters__chip">
                {{ $chip['label'] }}
                <button type="button"
                        wire:click="clearFilter('{{ $chip['key'] }}')"
                        class="rp-active-filters__chip-remove"
                        title="Usuń filtr">
                    <i class="bi bi-x"></i>
                </button>
            </span>
        @endforeach
        <button type="button" wire:click="clearFilters" class="rp-active-filters__clear">Wyczyść</button>
    </div>
@endif
@endunless

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- GRID TABLE                                                  --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@php
    $colCount = count($visibleColumns) + 1; // expand col
@endphp

<div class="card border-0 shadow-sm tg-table-wrap d-none d-md-block"
     x-data="{
         dragFrom: null,
         dragOver: null,
         resizing: null,
         startX: 0,
         startW: 0,
         colWidths: @js($columnWidths),
         startResize(e, col) {
             this.resizing = col;
             this.startX   = e.clientX;
             this.startW   = e.target.closest('th').offsetWidth;
             document.documentElement.classList.add('tg-resizing');
         },
         doResize(e) {
             if (!this.resizing) return;
             const w = Math.max(50, this.startW + e.clientX - this.startX);
             this.colWidths = { ...this.colWidths, [this.resizing]: w };
         },
         endResize() {
             if (!this.resizing) return;
             $wire.setColumnWidth(this.resizing, this.colWidths[this.resizing]);
             this.resizing = null;
             document.documentElement.classList.remove('tg-resizing');
         }
     }"
     @mousemove.window="doResize($event)"
     @mouseup.window="endResize()">
    <div class="tg-scroll-container" style="overflow-x:auto; overflow-y:auto; max-height:calc(100vh - 268px)">
        <table class="table table-sm tg-table mb-0" style="min-width:640px; border-collapse:separate; border-spacing:0">

            {{-- ── Colgroup for column widths (Alpine-driven, updates on resize) ── --}}
            <colgroup>
                <col style="width:36px; min-width:36px">
                @foreach($visibleColumns as $colKey)
                <col :style="colWidths['{{ $colKey }}'] ? `width:${colWidths['{{ $colKey }}']}px;min-width:${colWidths['{{ $colKey }}']}px` : ''">
                @endforeach
            </colgroup>

            {{-- ── Header ── --}}
            <thead>
                <tr>
                    <th style="width:36px; padding:8px 4px; border-bottom:none"></th>

                    @foreach($visibleColumns as $colKey)
                    @php $col = $availableColumns[$colKey] ?? null @endphp
                    @if($col)
                    <th draggable="true"
                        style="position:relative; padding:8px 20px 8px 8px; border-bottom:none; white-space:nowrap"
                        :class="{ 'tg-col-drag-over': dragOver === '{{ $colKey }}' }"
                        @class(['sortable' => $col['sortable'] ?? false])
                        @dragstart.self="dragFrom = '{{ $colKey }}'"
                        @dragover.prevent="dragOver = '{{ $colKey }}'"
                        @dragleave="if (!$el.contains($event.relatedTarget)) dragOver = null"
                        @drop.prevent="if (dragFrom && dragFrom !== '{{ $colKey }}') $wire.reorderColumns(dragFrom, '{{ $colKey }}'); dragFrom = null; dragOver = null"
                        @dragend="dragFrom = null; dragOver = null"
                        @click="if (!dragFrom) {{ ($col['sortable'] ?? false) ? "\$wire.sortBy('{$colKey}')" : '' }}">
                        <span style="pointer-events:none; user-select:none">
                            {{ $col['label'] }}
                            @if(($col['sortable'] ?? false) && $sortField === $colKey)
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1 text-primary" style="font-size:0.7rem"></i>
                            @elseif($col['sortable'] ?? false)
                                <i class="bi bi-arrow-down-up ms-1 opacity-25" style="font-size:0.65rem"></i>
                            @endif
                        </span>
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

                {{-- ── Inline add-task row ── --}}
                @if($showAddRow && ! $this->isEdiReviewing())
                <tr class="tg-add-row">
                    <td style="padding:6px 4px; text-align:center">
                        <button wire:click="cancelAdd"
                                class="btn btn-sm btn-link text-muted p-0"
                                title="Anuluj">
                            <i class="bi bi-x-lg" style="font-size:0.8rem"></i>
                        </button>
                    </td>

                    {{-- Name (always) --}}
                    @if(in_array('name', $visibleColumns))
                    <td style="padding:4px 6px; min-width:220px">
                        <div class="d-flex flex-column gap-1">
                            @if($addKind === 'procedure')
                                <select wire:model.live="newProcedureTemplateId"
                                        class="form-select form-select-sm @error('newProcedureTemplateId') is-invalid @enderror">
                                    <option value="">Szablon procedury *</option>
                                    @foreach($procedureTemplates as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                    @endforeach
                                </select>
                                @error('newProcedureTemplateId')
                                    <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
                                @enderror
                            @endif
                            <div class="d-flex gap-1 align-items-start">
                                <input type="text"
                                       wire:model="newTaskName"
                                       class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
                                       placeholder="{{ $addKind === 'approval' ? 'O co prosisz? *' : ($addKind === 'procedure' ? 'Nazwa procedury *' : 'Nazwa zadania *') }}"
                                       wire:keydown.enter="submitAdd"
                                       wire:keydown.escape="cancelAdd"
                                       x-data x-init="$el.focus()">
                                <button wire:click="submitAdd" class="btn btn-sm tg-add-submit flex-shrink-0">
                                    @if($addKind === 'procedure')
                                        <i class="bi bi-play-fill me-1"></i>Uruchom
                                    @elseif($addKind === 'approval')
                                        <i class="bi bi-check2-circle me-1"></i>Poproś
                                    @else
                                        <i class="bi bi-plus-lg me-1"></i>Dodaj
                                    @endif
                                </button>
                            </div>
                            @error('newTaskName')
                                <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
                            @enderror
                        </div>
                    </td>
                    @endif

                    @if(in_array('type', $visibleColumns))
                    <td class="small text-muted" style="white-space:nowrap">
                        @if($addKind === 'procedure') Procedura
                        @elseif($addKind === 'approval') Zatwierdzenie
                        @else Zadanie
                        @endif
                    </td>
                    @endif

                    @if(in_array('status', $visibleColumns))
                    <td style="padding:4px 6px">
                        <span class="tg-add-status">{{ $addKind === 'procedure' ? 'W trakcie' : ($addKind === 'approval' ? 'Oczekuje' : 'Oczekujące') }}</span>
                    </td>
                    @endif

                    @if(in_array('sprint', $visibleColumns))
                    <td style="padding:4px 6px">
                        <select wire:model="newTaskSprint" class="form-select form-select-sm" style="min-width:130px">
                            <option value="">Poza sprintem</option>
                            @foreach($allSprints as $sprintOption)
                                <option value="{{ $sprintOption->id }}">{{ $sprintOption->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    @endif

                    @if(in_array('category', $visibleColumns))
                    <td style="padding:4px 6px">
                        <input type="text" wire:model="newTaskCategory"
                               class="form-control form-control-sm" placeholder="Kategoria…">
                    </td>
                    @endif

                    @if(in_array('assigned_to', $visibleColumns))
                    <td style="padding:4px 6px">
                        <select wire:model="newTaskAssignedTo" class="form-select form-select-sm" style="min-width:110px">
                            <option value="">{{ $addKind === 'approval' ? 'Zatwierdzający *' : 'Nieprzypisane' }}</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    @endif

                    @if(in_array('priority', $visibleColumns))
                    <td style="padding:4px 6px">
                        <select wire:model="newTaskPriority" class="form-select form-select-sm">
                            <option value="">Brak</option>
                            <option value="1">1 – Najniższy</option>
                            <option value="2">2 – Niski</option>
                            <option value="3">3 – Średni</option>
                            <option value="4">4 – Wysoki</option>
                            <option value="5">5 – Krytyczny</option>
                        </select>
                    </td>
                    @endif

                    @if(in_array('due_date', $visibleColumns))
                    <td style="padding:4px 6px">
                        <input type="date" wire:model="newTaskDueDate" class="form-control form-control-sm">
                    </td>
                    @endif

                    {{-- Empty cells for read-only columns --}}
                    @foreach(['subtasks','comments','created_by','created_at','updated_at'] as $_ec)
                        @if(in_array($_ec, $visibleColumns))<td></td>@endif
                    @endforeach
                </tr>
                @endif

                {{-- ── Add-task footer row ── --}}
                @unless($this->isEdiReviewing())
                <tr class="tg-footer-row">
                    <td colspan="{{ $colCount }}" style="padding:7px 12px">
                        @if(!$showAddRow)
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <button type="button" wire:click="startAdd('task')"
                                    class="btn btn-sm btn-link text-primary text-decoration-none p-0">
                                <i class="bi bi-plus-circle me-1"></i>Dodaj zadanie
                            </button>
                            @if($this->usesWorkItems())
                            <button type="button" wire:click="startAdd('procedure')"
                                    class="btn btn-sm btn-link text-primary text-decoration-none p-0">
                                <i class="bi bi-play-circle me-1"></i>Uruchom procedurę
                            </button>
                            <button type="button" wire:click="startAdd('approval')"
                                    class="btn btn-sm btn-link text-primary text-decoration-none p-0">
                                <i class="bi bi-check2-circle me-1"></i>Poproś o zatwierdzenie
                            </button>
                            @endif
                        </div>
                        @else
                        <span class="text-muted small">Naciśnij <kbd>Enter</kbd> aby dodać lub <kbd>Esc</kbd> aby anulować</span>
                        @endif
                    </td>
                </tr>
                @endunless
            </tbody>
        </table>
    </div>

    {{-- Pagination (only in flat view) --}}
    @if($tasks instanceof \Illuminate\Contracts\Pagination\Paginator && $tasks->hasPages())
    <div class="card-footer border-top py-2 px-3 bg-white">
        {{ $tasks->links() }}
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MOBILE CARD LIST (< 768px) — zastępuje tabelę powyżej        --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="tg-cards d-md-none">
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
            @if($searchTask || $searchCategory || $searchAssignedTo)
                <button wire:click="clearFilters" class="btn btn-sm btn-link mt-1">Wyczyść filtry</button>
            @endif
        </div>
    @endif

    {{-- ── Inline add-task card ── --}}
    @if($showAddRow)
    <x-ui.card class="dt-card tg-add-card">
        <div class="dt-card__title d-flex justify-content-between align-items-start gap-2">
            <span>
                @if($addKind === 'procedure') Uruchom procedurę
                @elseif($addKind === 'approval') Poproś o zatwierdzenie
                @else Nowe zadanie
                @endif
            </span>
            <button wire:click="cancelAdd" class="btn btn-sm btn-link text-muted p-0 tg-dt-hit" title="Anuluj">
                <i class="bi bi-x-lg" style="font-size:0.8rem"></i>
            </button>
        </div>
        <div class="d-flex flex-column gap-2">
            @if($addKind === 'procedure')
            <select wire:model.live="newProcedureTemplateId"
                    class="form-select form-select-sm @error('newProcedureTemplateId') is-invalid @enderror">
                <option value="">Szablon procedury *</option>
                @foreach($procedureTemplates as $tpl)
                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                @endforeach
            </select>
            @error('newProcedureTemplateId')
                <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
            @enderror
            @endif

            @if(in_array('name', $visibleColumns))
            <div>
                <input type="text"
                       wire:model="newTaskName"
                       class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
                       placeholder="{{ $addKind === 'approval' ? 'O co prosisz? *' : ($addKind === 'procedure' ? 'Nazwa procedury *' : 'Nazwa zadania *') }}"
                       wire:keydown.enter="submitAdd"
                       wire:keydown.escape="cancelAdd">
                @error('newTaskName')
                    <div class="invalid-feedback" style="font-size:0.72rem">{{ $message }}</div>
                @enderror
            </div>
            @endif

            @if(in_array('sprint', $visibleColumns) && $addKind !== 'procedure')
            <select wire:model="newTaskSprint" class="form-select form-select-sm">
                <option value="">Poza sprintem</option>
                @foreach($allSprints as $sprintOption)
                    <option value="{{ $sprintOption->id }}">{{ $sprintOption->name }}</option>
                @endforeach
            </select>
            @endif

            @if(in_array('category', $visibleColumns) && $addKind !== 'procedure')
            <input type="text" wire:model="newTaskCategory" class="form-control form-control-sm" placeholder="Kategoria…">
            @endif

            @if(in_array('assigned_to', $visibleColumns) || $addKind === 'approval')
            <select wire:model="newTaskAssignedTo" class="form-select form-select-sm @error('newTaskAssignedTo') is-invalid @enderror">
                <option value="">{{ $addKind === 'approval' ? 'Zatwierdzający *' : 'Nieprzypisane' }}</option>
                @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            @error('newTaskAssignedTo')
                <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
            @enderror
            @endif

            @if(in_array('priority', $visibleColumns) && $addKind !== 'procedure')
            <select wire:model="newTaskPriority" class="form-select form-select-sm">
                <option value="">Priorytet: brak</option>
                <option value="1">1 – Najniższy</option>
                <option value="2">2 – Niski</option>
                <option value="3">3 – Średni</option>
                <option value="4">4 – Wysoki</option>
                <option value="5">5 – Krytyczny</option>
            </select>
            @endif

            @if(in_array('due_date', $visibleColumns))
            <input type="date" wire:model="newTaskDueDate" class="form-control form-control-sm">
            @endif

            <button wire:click="submitAdd" class="btn btn-sm tg-add-submit w-100">
                @if($addKind === 'procedure')
                    <i class="bi bi-play-fill me-1"></i>Uruchom procedurę
                @elseif($addKind === 'approval')
                    <i class="bi bi-check2-circle me-1"></i>Poproś o zatwierdzenie
                @else
                    <i class="bi bi-plus-lg me-1"></i>Dodaj zadanie
                @endif
            </button>
        </div>
    </x-ui.card>
    @else
    <div class="tg-add-actions">
        <button type="button" wire:click="startAdd('task')" class="btn btn-sm">
            <i class="bi bi-plus-circle"></i>Dodaj zadanie
        </button>
        @if($this->usesWorkItems())
        <button type="button" wire:click="startAdd('procedure')" class="btn btn-sm">
            <i class="bi bi-play-circle"></i>Uruchom procedurę
        </button>
        <button type="button" wire:click="startAdd('approval')" class="btn btn-sm">
            <i class="bi bi-check2-circle"></i>Poproś o zatwierdzenie
        </button>
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

<script>
    (function () {
        const root = document.getElementById('xuiv2Tasks');
        if (!root || root.dataset.xuiv2Bound) return;
        root.dataset.xuiv2Bound = '1';

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches
            || !window.matchMedia('(pointer: fine)').matches) return;

        // Cache buttons once; Livewire re-renders morph the DOM but the buttons
        // keep stable wire:key-less identity for this simple case, so a light
        // re-scan on click is enough — no need to query on every mousemove.
        let magneticBtns = Array.from(document.querySelectorAll('.xuiv2-magnetic'));
        const rescan = () => { magneticBtns = Array.from(document.querySelectorAll('.xuiv2-magnetic')); };
        document.addEventListener('click', rescan, { passive: true, capture: true });
        document.addEventListener('livewire:morphed', rescan, { passive: true });

        let mouseX = 0;
        let mouseY = 0;
        let ticking = false;

        function onFrame() {
            ticking = false;
            for (const btn of magneticBtns) {
                const r = btn.getBoundingClientRect();
                const cx = r.left + r.width / 2;
                const cy = r.top + r.height / 2;
                const dx = mouseX - cx;
                const dy = mouseY - cy;
                const dist = Math.hypot(dx, dy);
                const radius = 70;
                if (dist < radius) {
                    const pull = (1 - dist / radius) * 0.35;
                    btn.style.transform = `translate(${dx * pull}px, ${dy * pull}px)`;
                } else if (btn.style.transform) {
                    btn.style.transform = '';
                }
            }
        }

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(onFrame);
            }
        }, { passive: true });
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
