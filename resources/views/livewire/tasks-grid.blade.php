<div class="xuiv2-tasks" id="xuiv2Tasks">
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
       (renderowany przez x-app-layout), więc dociągamy go tą samą klasą na <body>.
       Tytuł ma już globalny gradient primary→accent, a mono-font na przyciskach
       w headerze jest już globalny (".app-header .btn-outline-secondary" w
       app.css) — tu zostaje już tylko treść specyficzna dla /tasks2: kicker. */
    body.xuiv2-page header h2::before {
        content: 'ZARZĄDZANIE ZADANIAMI';
        display: block;
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #a855f7;
        margin-bottom: 7px;
    }

    /* Magnetyczne CTA (Filtry, Dodaj zadanie) — zostaje lokalne (opt-in przez klasę),
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
    .tg-expanded > td {
        background: rgba(168,85,247,0.06) !important;
    }

    /* ── Hover-edit cells ── */
    .tg-hover-edit:hover {
        background: rgba(168,85,247,0.12) !important;
        outline: 1px dashed rgba(168,85,247,0.5);
        border-radius: 4px;
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
        .tg-search-task { width: 100% !important; flex: 1 1 auto; }

        /* Tabela znika, karty przejmują ── */
        .tg-table-wrap { display: none !important; }
        .tg-cards { display: block !important; }
    }

    /* ── Karty zadań (mobile) ── */
    .tg-card {
        position: relative;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.07);
        border-left: 3px solid rgba(255,255,255,0.15);
        background: rgba(255,255,255,0.02);
        padding: 9px 10px;
        margin-bottom: 7px;
    }
    .tg-card-top { display: flex; align-items: center; gap: 6px; }
    .tg-card-expand-btn {
        appearance: none; border: none; background: transparent; padding: 2px;
        color: rgba(255,255,255,0.4); line-height: 1; flex-shrink: 0;
    }
    .tg-card-subtask-badge {
        flex-shrink: 0; font-size: 0.6rem; min-width: 30px; text-align: center;
        border-radius: 999px; padding: 1px 6px;
        background: rgba(255,255,255,0.1); color: var(--text-muted,#94a3b8);
    }
    .tg-card-name {
        flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        color: var(--text-main,#f1f5f9); text-decoration: none; font-size: 0.88rem; font-weight: 500;
        padding: 2px 0;
    }
    .tg-card-source-link { flex-shrink: 0; color: #c084fc; line-height: 1; }
    .tg-card-meta {
        display: flex; flex-wrap: wrap; align-items: center; gap: 5px 8px;
        margin-top: 7px; font-size: 0.74rem; color: var(--text-muted,#94a3b8);
    }
    .tg-card-meta .tg-meta-item { display: inline-flex; align-items: center; gap: 3px; line-height: 1.3; }
    .tg-card-expand {
        margin-top: 9px; padding-top: 9px; border-top: 1px dashed rgba(255,255,255,0.08);
    }

    /* ── Nagłówek grupy (mobile) ── */
    .tg-group-card-header {
        display: flex; align-items: center; gap: 8px;
        padding: 9px 4px 6px; margin-top: 4px;
        font-size: 0.78rem; font-weight: 600;
        color: var(--text-muted,#94a3b8); letter-spacing: 0.3px;
    }
    .tg-group-card-header:first-child { margin-top: 0; }

    /* ── Add-task (mobile) ── */
    .tg-add-card {
        border: 1px dashed rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.02);
    }
    .tg-add-card .form-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-muted,#94a3b8); margin-bottom: 2px; }
</style>

{{-- Flash message --}}
@if($flash)
<div class="alert alert-success alert-dismissible py-2 mb-2 d-flex align-items-center gap-2 small" role="alert"
     style="border-radius: 6px">
    <i class="bi bi-check-circle-fill text-success"></i>
    <span class="flex-grow-1">{{ $flash }}</span>
    <button type="button" wire:click="$set('flash', null)" class="btn-close" style="font-size:0.8rem"></button>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TOOLBAR — jeden rząd, jak w /recruitment-processes:          --}}
{{-- Szukaj + jeden przycisk „Filtry” (pogrupowany panel) zamiast --}}
{{-- rzędu osobnych przełączników.                                --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-2 border-0 shadow-sm">
    <div class="card-body py-2 px-3 tg-toolbar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Search: Task --}}
            <div class="input-group tg-search-task" style="width:175px">
                <span class="input-group-text px-2">
                    <i class="bi bi-search" style="font-size:0.72rem"></i>
                </span>
                <input wire:model.live.debounce.300ms="searchTask"
                       type="text"
                       placeholder="Szukaj zadania…"
                       class="form-control">
            </div>

            {{-- Filtry: jeden przycisk, panel z pogrupowanymi sekcjami (SharePoint-style, jak w rekrutacji) --}}
            <div x-data="{ open: false, top: 0, left: 0, openStatus: false, openVisibility: false, openType: false, openSearch: false, openGroup: false, openColumns: false }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); const pw=Math.min(600, window.innerWidth-24); top=r.bottom+4; left=Math.max(4, Math.min(r.left, window.innerWidth-pw-4)); open=true"
                        class="btn btn-sm btn-outline-secondary tg-quiet-btn {{ count($this->activeFilterChips()) > 0 ? 'is-on' : '' }}">
                    <i class="bi bi-sliders me-1"></i>Filtry
                    @if(count($this->activeFilterChips()) > 0)
                        <span class="tg-quiet-count">{{ count($this->activeFilterChips()) }}</span>
                    @endif
                    <i class="bi bi-chevron-down ms-1" style="font-size:.6rem"></i>
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

            {{-- Zapisane widoki (pigułki, jak w rekrutacji) --}}
            @unless($this->isLockedToSprint())
                @foreach($savedViews as $savedView)
                    @php $isActiveView = $activeViewId === $savedView->id; @endphp
                    <button type="button" wire:click="loadSavedView({{ $savedView->id }})"
                            class="btn btn-sm btn-outline-secondary rp-topbar-btn {{ $isActiveView ? 'is-on' : '' }}"
                            title="{{ $savedView->is_global ? 'Widok globalny (dla wszystkich)' : 'Twój zapisany widok' }}">
                        <i class="bi bi-{{ $savedView->is_global ? 'globe' : 'bookmark'.($isActiveView ? '-fill' : '') }} me-1"></i>{{ $savedView->name }}
                        <span class="tg-quiet-count">{{ $viewCounts[$savedView->id] ?? 0 }}</span>
                    </button>
                @endforeach
            @endunless

            {{-- Loading spinner --}}
            <div wire:loading>
                <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:14px;height:14px">
                    <span class="visually-hidden">Ładowanie…</span>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                @unless($this->isLockedToSprint())
                    {{-- Zapisz / zarządzaj widokami ── --}}
                    <div x-data="{ open: false, top: 0, left: 0 }">
                        <button type="button"
                                @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=Math.max(4, r.right-300); open=true"
                                class="btn btn-sm btn-outline-secondary tg-quiet-btn"
                                title="Zapisz i zarządzaj widokami">
                            <i class="bi bi-bookmark{{ $view ? '-fill' : '' }}"></i>
                        </button>
                        <template x-teleport="body">
                            <div x-show="open" x-cloak
                                 @click.outside="open = false"
                                 :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:300px`"
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
                            class="btn btn-sm btn-outline-secondary tg-quiet-btn {{ $isMenuDefaultView ? 'is-on' : '' }}"
                            title="{{ $isMenuDefaultView ? 'Ten widok (z filtrami) otwiera się z menu' : 'Ustaw bieżący widok i filtry jako domyślne w menu' }}">
                        <i class="bi bi-house{{ $isMenuDefaultView ? '-fill' : '' }}"></i>
                    </button>
                @endunless

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
    <div class="rp-active-filters mb-2 px-1">
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
                @if($showAddRow)
                <tr class="tg-add-row">
                    <td style="padding:6px 4px; text-align:center">
                        <button wire:click="$set('showAddRow', false)"
                                class="btn btn-sm btn-link text-muted p-0"
                                title="Anuluj">
                            <i class="bi bi-x-lg" style="font-size:0.8rem"></i>
                        </button>
                    </td>

                    {{-- Name (always) --}}
                    @if(in_array('name', $visibleColumns))
                    <td style="padding:4px 6px; min-width:220px">
                        <div class="d-flex gap-1 align-items-start">
                            <input type="text"
                                   wire:model="newTaskName"
                                   class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
                                   placeholder="Nazwa zadania *"
                                   wire:keydown.enter="addTask"
                                   wire:keydown.escape="$set('showAddRow', false)"
                                   x-data x-init="$el.focus()">
                            <button wire:click="addTask" class="btn btn-sm tg-add-submit flex-shrink-0">
                                <i class="bi bi-plus-lg me-1"></i>Dodaj
                            </button>
                        </div>
                        @error('newTaskName')
                            <div class="invalid-feedback" style="font-size:0.72rem">{{ $message }}</div>
                        @enderror
                    </td>
                    @endif

                    @if(in_array('type', $visibleColumns))
                    <td></td>
                    @endif

                    @if(in_array('status', $visibleColumns))
                    <td style="padding:4px 6px">
                        <span class="tg-add-status">Oczekujące</span>
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
                            <option value="">Nieprzypisane</option>
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
                    @foreach(['subtasks','comments','created_at','updated_at'] as $_ec)
                        @if(in_array($_ec, $visibleColumns))<td></td>@endif
                    @endforeach
                </tr>
                @endif

                {{-- ── Add-task footer row ── --}}
                <tr class="tg-footer-row">
                    <td colspan="{{ $colCount }}" style="padding:7px 12px">
                        @if(!$showAddRow)
                        <button wire:click="$set('showAddRow', true)"
                                class="btn btn-sm btn-link text-primary text-decoration-none p-0">
                            <i class="bi bi-plus-circle me-1"></i>Dodaj zadanie
                        </button>
                        @else
                        <span class="text-muted small">Naciśnij <kbd>Enter</kbd> aby dodać lub <kbd>Esc</kbd> aby anulować</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pagination (only in flat view) --}}
    @if($tasks?->hasPages())
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
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox display-5 d-block mb-2 opacity-30"></i>
            <div>Brak zadań spełniających kryteria</div>
            @if($searchTask || $searchCategory || $searchAssignedTo)
                <button wire:click="clearFilters" class="btn btn-sm btn-link mt-1">Wyczyść filtry</button>
            @endif
        </div>
    @endif

    {{-- ── Inline add-task card ── --}}
    @if($showAddRow)
    <div class="tg-card tg-add-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold small">Nowe zadanie</span>
            <button wire:click="$set('showAddRow', false)" class="btn btn-sm btn-link text-muted p-0" title="Anuluj">
                <i class="bi bi-x-lg" style="font-size:0.8rem"></i>
            </button>
        </div>
        <div class="d-flex flex-column gap-2">
            @if(in_array('name', $visibleColumns))
            <div>
                <input type="text"
                       wire:model="newTaskName"
                       class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
                       placeholder="Nazwa zadania *"
                       wire:keydown.enter="addTask"
                       wire:keydown.escape="$set('showAddRow', false)">
                @error('newTaskName')
                    <div class="invalid-feedback" style="font-size:0.72rem">{{ $message }}</div>
                @enderror
            </div>
            @endif

            @if(in_array('sprint', $visibleColumns))
            <select wire:model="newTaskSprint" class="form-select form-select-sm">
                <option value="">Poza sprintem</option>
                @foreach($allSprints as $sprintOption)
                    <option value="{{ $sprintOption->id }}">{{ $sprintOption->name }}</option>
                @endforeach
            </select>
            @endif

            @if(in_array('category', $visibleColumns))
            <input type="text" wire:model="newTaskCategory" class="form-control form-control-sm" placeholder="Kategoria…">
            @endif

            @if(in_array('assigned_to', $visibleColumns))
            <select wire:model="newTaskAssignedTo" class="form-select form-select-sm">
                <option value="">Nieprzypisane</option>
                @foreach($allUsers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            @endif

            @if(in_array('priority', $visibleColumns))
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

            <button wire:click="addTask" class="btn btn-sm tg-add-submit w-100">
                <i class="bi bi-plus-lg me-1"></i>Dodaj zadanie
            </button>
        </div>
    </div>
    @else
    <button wire:click="$set('showAddRow', true)"
            class="btn btn-sm btn-link text-primary text-decoration-none p-0 mt-1">
        <i class="bi bi-plus-circle me-1"></i>Dodaj zadanie
    </button>
    @endif

    {{-- Pagination (only in flat view) --}}
    @if($tasks?->hasPages())
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
        // "ZARZĄDZANIE ZADANIAMI" kicker nad "Backlog" i mono-styl przycisków w
        // headerze (patrz body.xuiv2-page w <style> wyżej) — tło/fonty/poświata
        // kursora są już globalne (app.css + app.js), więc nic więcej tu nie trzeba.
        document.body.classList.add('xuiv2-page');

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
</div>
