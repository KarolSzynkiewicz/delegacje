<div>
<style>
    /* ── Scope: override Bootstrap defaults inside the grid ── */

    /* All text inside any dropdown rendered by this component must be light */
    .dropdown-menu { color: var(--text-main, #f1f5f9) !important; }
    .dropdown-menu hr { border-color: rgba(255,255,255,0.1) !important; }

    /* Compact btn-sm — app.css hardcodes 10px 24px on .btn, killing Bootstrap's btn-sm vars */
    .tg-toolbar .btn {
        padding: 5px 12px !important;
        font-size: 0.8rem !important;
        border-radius: 8px !important;
        gap: 4px !important;
    }
    .tg-toolbar .btn-group .btn { border-radius: 0 !important; }
    .tg-toolbar .btn-group .btn:first-child { border-radius: 8px 0 0 8px !important; }
    .tg-toolbar .btn-group .btn:last-child  { border-radius: 0 8px 8px 0 !important; }

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

    /* ── Sticky header ── */
    .tg-table > thead > tr > th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: rgba(10, 15, 29, 0.97) !important;
        backdrop-filter: blur(12px);
        border-bottom: 2px solid rgba(255,255,255,0.12) !important;
        border-top: none !important;
        color: var(--text-muted, #94a3b8) !important;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        padding: 10px 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,.4);
    }
    .tg-table > thead > tr > th.sortable { cursor: pointer; }
    .tg-table > thead > tr > th.sortable:hover { color: var(--primary, #3b82f6) !important; }

    /* ── Task rows ── */
    .tg-table > tbody > tr.tg-task-row > td {
        padding: 7px 10px !important;
        border-bottom: 1px solid rgba(255,255,255,0.05) !important;
        background: transparent !important;
        font-size: 0.84rem;
    }
    .tg-table > tbody > tr.tg-task-row:hover > td {
        background: rgba(255,255,255,0.035) !important;
    }
    .tg-expanded > td {
        background: rgba(59,130,246,0.06) !important;
    }

    /* ── Hover-edit cells ── */
    .tg-hover-edit:hover {
        background: rgba(59,130,246,0.12) !important;
        outline: 1px dashed rgba(59,130,246,0.5);
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
    .tg-status-badge.s-in_progress{ background: rgba(59,130,246,.18); color: #60a5fa; border: 1px solid rgba(59,130,246,.35); }
    .tg-status-badge.s-completed  { background: rgba(16,185,129,.18); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
    .tg-status-badge.s-cancelled  { background: rgba(239,68,68,.18);  color: #f87171; border: 1px solid rgba(239,68,68,.35); }

    /* ── Avatar initials ── */
    .tg-avatar {
        width: 26px; height: 26px; font-size: 0.62rem; font-weight: 700;
        border-radius: 50%; display: inline-flex; align-items: center;
        justify-content: center; color: #fff;
        background: var(--primary, #3b82f6); flex-shrink: 0;
    }

    /* ── Group header ── */
    .tg-group-header > td {
        background: rgba(255,255,255,0.04) !important;
        border-top: 2px solid rgba(255,255,255,0.1) !important;
        padding: 6px 12px !important;
        font-size: 0.78rem; font-weight: 600;
        color: var(--text-muted, #94a3b8) !important;
        letter-spacing: 0.3px;
    }
    .tg-group-collapsed > td { opacity: .85; }

    /* ── Expanded detail panel ── */
    .tg-expand-row > td {
        background: rgba(10,15,29,0.6) !important;
        border-bottom: 2px solid rgba(59,130,246,0.3) !important;
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
        border-top: 2px solid var(--primary, #3b82f6) !important;
        background: rgba(59,130,246,0.07) !important;
        padding: 5px 6px !important;
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
        background: rgba(59,130,246,.18) !important;
        outline: 2px dashed rgba(59,130,246,.6) !important;
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
    .tg-resizing .tg-resize-handle { border-right-color: rgba(59,130,246,.7); }
    .tg-resizing * { cursor: col-resize !important; user-select: none !important; }
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
{{-- TOOLBAR                                                     --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-2 border-0 shadow-sm">
    <div class="card-body py-2 px-3 tg-toolbar">
        {{-- Row 1: Search + Status filter + My tasks --}}
        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
            {{-- Search: Task --}}
            <div class="input-group" style="width:175px">
                <span class="input-group-text px-2">
                    <i class="bi bi-search" style="font-size:0.72rem"></i>
                </span>
                <input wire:model.live.debounce.300ms="searchTask"
                       type="text"
                       placeholder="Szukaj zadania…"
                       class="form-control">
            </div>

            {{-- Search: Category --}}
            <input wire:model.live.debounce.300ms="searchCategory"
                   type="text" placeholder="Kategoria…"
                   class="form-control" style="width:110px">

            {{-- Search: Assignee --}}
            <input wire:model.live.debounce.300ms="searchAssignedTo"
                   type="text" placeholder="Osoba…"
                   class="form-control" style="width:95px">

            <div class="vr mx-1" style="background:rgba(255,255,255,.12)"></div>

            {{-- Status filter --}}
            <div class="btn-group">
                <button wire:click="$set('status', '')"
                        class="btn {{ $status === '' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Aktywne
                </button>
                <button wire:click="$set('status', 'closed')"
                        class="btn {{ $status === 'closed' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    Zamknięte
                </button>
                <button wire:click="$set('status', 'all')"
                        class="btn {{ $status === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    Wszystkie
                </button>
            </div>

            {{-- My tasks --}}
            <button wire:click="$toggle('myTasksOnly')"
                    class="btn {{ $myTasksOnly ? 'btn-primary' : 'btn-outline-secondary' }}"
                    title="Tylko moje zadania">
                <i class="bi bi-person-check"></i>Moje
            </button>

            {{-- Clear --}}
            @if($searchTask || $searchCategory || $searchAssignedTo || $myTasksOnly || $groupBy || $sortField !== 'created_at')
            <button wire:click="clearFilters"
                    class="btn btn-outline-danger"
                    title="Wyczyść filtry">
                <i class="bi bi-x-lg"></i>
            </button>
            @endif

            {{-- Loading spinner --}}
            <div wire:loading class="ms-1">
                <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:14px;height:14px">
                    <span class="visually-hidden">Ładowanie…</span>
                </div>
            </div>
        </div>

        {{-- Row 2: View controls — dropdowny przez x-teleport żeby ominąć backdrop-filter stacking context --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">

            {{-- ── Grupuj ── --}}
            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=r.left; open=true"
                        class="btn btn-sm {{ $groupBy ? 'btn-info' : 'btn-outline-secondary' }}">
                    <i class="bi bi-collection me-1"></i>Grupuj{{ $groupBy ? ': '.$availableColumns[$groupBy]['label'] : '' }}
                    <i class="bi bi-chevron-down ms-1" style="font-size:0.6rem"></i>
                </button>
                <template x-teleport="body">
                    <ul x-show="open" x-cloak
                        @click.outside="open = false"
                        :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:195px`"
                        class="dropdown-menu show py-1 shadow-lg">
                        <li>
                            <button class="dropdown-item small py-2 {{ $groupBy === '' ? 'active' : '' }}"
                                    wire:click="setGroupBy('')" @click="open=false">
                                <i class="bi bi-x-circle me-2"></i>Bez grupowania
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        @foreach(['status'=>'Status','sprint'=>'Sprint','category'=>'Kategoria','assigned_to'=>'Przypisany','priority'=>'Priorytet'] as $gf=>$gl)
                        @if($this->isLockedToSprint() && $gf === 'sprint')
                            @continue
                        @endif
                        <li>
                            <button class="dropdown-item small py-2 {{ $groupBy===$gf ? 'active' : '' }}"
                                    wire:click="setGroupBy('{{ $gf }}')" @click="open=false">
                                <i class="bi bi-tag me-2"></i>{{ $gl }}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </template>
            </div>

            {{-- ── Kolumny ── --}}
            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=r.left; open=true"
                        class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-layout-three-columns me-1"></i>Kolumny
                    <i class="bi bi-chevron-down ms-1" style="font-size:0.6rem"></i>
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak
                         @click.outside="open = false"
                         :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:215px`"
                         class="dropdown-menu show p-3 shadow-lg">
                        <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:10px">
                            Widoczne kolumny
                        </div>
                        @foreach($availableColumns as $colKey => $col)
                        @if($this->isLockedToSprint() && $colKey === 'sprint')
                            @continue
                        @endif
                        @php
                            $colLockedByGroup = $groupBy !== '' && $colKey === $groupBy;
                            $colChecked = in_array($colKey, $visibleColumns) && ! $colLockedByGroup;
                            $colDisabled = ($col['always'] ?? false) || $colLockedByGroup;
                        @endphp
                        <div class="d-flex align-items-center gap-2 py-1">
                            <button type="button"
                                    wire:click="toggleColumn('{{ $colKey }}')"
                                    @click.stop
                                    {{ $colDisabled ? 'disabled' : '' }}
                                    style="width:18px;height:18px;border-radius:4px;border:2px solid;flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:0;transition:all .15s;
                                        {{ $colChecked ? 'background:var(--primary,#3b82f6);border-color:var(--primary,#3b82f6);color:#fff' : 'background:transparent;border-color:rgba(255,255,255,0.25);color:transparent' }};
                                        {{ $colDisabled ? 'opacity:.4;cursor:not-allowed' : 'cursor:pointer' }}">
                                <i class="bi bi-check" style="font-size:0.65rem"></i>
                            </button>
                            <span style="font-size:0.84rem;cursor:{{ $colDisabled ? 'default' : 'pointer' }};opacity:{{ $colDisabled ? '.5' : '1' }}"
                                  @if(! $colDisabled) wire:click="toggleColumn('{{ $colKey }}')" @click.stop @endif>
                                {{ $col['label'] }}@if($colLockedByGroup)<span class="text-muted"> · grupowanie</span>@endif
                            </span>
                        </div>
                        @endforeach
                    </div>
                </template>
            </div>

            {{-- ── Widoki ── --}}
            @unless($this->isLockedToSprint())
            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if(open){open=false;return} const r=$el.getBoundingClientRect(); top=r.bottom+4; left=r.left; open=true"
                        class="btn btn-sm {{ $view ? 'btn-info' : 'btn-outline-secondary' }}">
                    <i class="bi bi-bookmark me-1"></i>Widoki
                    @if($activeViewName)
                        <span class="opacity-75">: {{ $activeViewName }}</span>
                    @elseif(!empty($savedViews))
                        <span class="badge bg-primary ms-1" style="font-size:0.6rem;border-radius:8px">{{ count($savedViews) }}</span>
                    @endif
                    <i class="bi bi-chevron-down ms-1" style="font-size:0.6rem"></i>
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak
                         @click.outside="open = false"
                         :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:260px`"
                         class="dropdown-menu show p-3 shadow-lg">
                        @if($view && $activeViewName)
                            <div class="mb-2 p-2 rounded" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2)">
                                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:4px">
                                    Aktywny widok
                                </div>
                                <div class="fw-semibold small mb-2">{{ $activeViewName }}</div>
                                <button type="button" wire:click="clearView" @click="open=false"
                                        class="btn btn-sm btn-outline-secondary mt-2 w-100">
                                    Widok domyślny
                                </button>
                            </div>
                            <hr class="my-2">
                        @endif
                        @if(!empty($savedViews))
                            <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:8px">
                                Zapisane widoki
                            </div>
                            @foreach($savedViews as $savedView)
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <button wire:click="loadView('{{ $savedView->slug }}')" @click="open=false"
                                        class="btn btn-sm btn-link text-start flex-grow-1 p-1 text-decoration-none {{ $view === $savedView->slug ? 'fw-bold' : '' }}"
                                        style="font-size:0.83rem">
                                    <i class="bi bi-bookmark{{ $view === $savedView->slug ? '-fill' : '' }} me-1"
                                       style="color:var(--primary,#3b82f6);font-size:0.75rem"></i>{{ $savedView->name }}
                                </button>
                                <button wire:click="deleteView('{{ $savedView->slug }}')" @click="open=false"
                                        class="btn btn-sm btn-link p-1 flex-shrink-0"
                                        style="color:var(--danger,#ef4444)" title="Usuń">
                                    <i class="bi bi-trash" style="font-size:0.78rem"></i>
                                </button>
                            </div>
                            @endforeach
                            <hr class="my-2">
                        @endif
                        <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.6;margin-bottom:8px">
                            {{ $view ? 'Nadpisz aktywny widok' : 'Zapisz bieżący widok' }}
                        </div>
                        <div class="d-flex gap-2">
                            <input wire:model="saveViewName" type="text"
                                   class="form-control form-control-sm flex-grow-1"
                                   placeholder="Nazwa widoku…"
                                   wire:keydown.enter="saveView"
                                   @click.stop>
                            <button wire:click="saveView" @click="open=false"
                                    class="btn btn-sm btn-primary flex-shrink-0"
                                    title="Zapisz">
                                <i class="bi bi-floppy"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            @endunless

            {{-- Domyślny widok menu --}}
            @unless($this->isLockedToSprint())
            <button type="button"
                    wire:click="setAsMenuDefaultView"
                    class="btn btn-sm {{ $isMenuDefaultView ? 'btn-primary' : 'btn-outline-secondary' }}"
                    title="{{ $isMenuDefaultView ? 'Ten widok (z filtrami) otwiera się z menu' : 'Ustaw bieżący widok i filtry jako domyślne w menu' }}">
                <i class="bi bi-house{{ $isMenuDefaultView ? '-fill' : '' }} me-1"></i>Domyślny
            </button>
            @endunless

            {{-- Task count --}}
            <div class="ms-auto" style="font-size:0.78rem;color:var(--text-muted,#94a3b8)">
                @if($tasks)
                    {{ $tasks->total() }} zadań
                @elseif($groupedTasks)
                    {{ $groupedTasks->flatten()->count() }} zadań
                @endif
                @if($groupBy)
                    <span class="ms-2 badge"
                          title="Przeciągnij zadanie (uchwyt ⋮⋮) na inną grupę, żeby zmienić: {{ $availableColumns[$groupBy]['label'] ?? '' }}"
                          style="font-size:0.65rem;background:rgba(6,182,212,.15);color:#67e8f9;border:1px solid rgba(6,182,212,.25)">grupowanie</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- GRID TABLE                                                  --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@php
    $colCount = count($visibleColumns) + 2; // expand col + actions col
@endphp

<div class="card border-0 shadow-sm"
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
                <col style="width:90px; min-width:90px">
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

                    <th style="width:90px; padding:8px 8px; border-bottom:none; text-align:right">Akcje</th>
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
                        <td colspan="{{ $colCount }}" class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-5 d-block mb-2 opacity-30"></i>
                            <div>Brak zadań spełniających kryteria</div>
                            @if($searchTask || $searchCategory || $searchAssignedTo)
                                <button wire:click="clearFilters" class="btn btn-sm btn-link mt-1">Wyczyść filtry</button>
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
                        <input type="text"
                               wire:model="newTaskName"
                               class="form-control form-control-sm @error('newTaskName') is-invalid @enderror"
                               placeholder="Nazwa zadania *"
                               wire:keydown.enter="addTask"
                               wire:keydown.escape="$set('showAddRow', false)"
                               x-data x-init="$el.focus()">
                        @error('newTaskName')
                            <div class="invalid-feedback" style="font-size:0.72rem">{{ $message }}</div>
                        @enderror
                    </td>
                    @endif

                    @if(in_array('status', $visibleColumns))
                    <td style="padding:4px 6px">
                        <span class="badge bg-warning text-dark" style="font-size:0.72rem">⏳ Oczekujące</span>
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

                    <td style="padding:4px 6px; text-align:right">
                        <button wire:click="addTask" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Dodaj
                        </button>
                    </td>
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
</div>
