<x-app-layout :edgeToEdge="true">
    {{-- No header slot — editor takes full viewport height minus navbar --}}

    <div id="peShell" class="pe-shell">

        {{-- ── TOOLBAR ──────────────────────────────────────────────── --}}
        <div class="pe-toolbar">
            <a id="btnBack" href="{{ route('procedure-templates.index') }}" class="pe-btn pe-btn-ghost" title="Powrót do listy procedur">← Procedury</a>
            <div class="pe-sep"></div>
            <input type="text" id="procNameInput" class="pe-procname" value="{{ $template->name }}" placeholder="Nazwa procedury">
            <span class="pe-dirty-dot" id="dirtyDot" title="Niezapisane zmiany"></span>
            <div class="pe-sep"></div>
            <button class="pe-btn" id="btnUndo" title="Cofnij (Ctrl+Z)" disabled>↶</button>
            <button class="pe-btn" id="btnRedo" title="Ponów (Ctrl+Y)" disabled>↷</button>
            <div class="pe-toolbar-spacer"></div>
            <span class="pe-zoom-indicator" id="zoomIndicator">100%</span>
            <button class="pe-btn pe-btn-primary" id="btnSave" title="Zapisz (Ctrl+S)">💾 Zapisz</button>
        </div>

        {{-- ── CONTENT GRID ─────────────────────────────────────────── --}}
        <div class="pe-content">

            {{-- CANVAS --}}
            <div class="pe-canvas-area">
                <div class="pe-canvas-bg" id="canvasBg">
                    <div class="pe-world" id="world">
                        <svg class="pe-edges-svg" id="edgesSvg"></svg>
                        <div id="nodesLayer"></div>
                    </div>
                </div>

                <div class="pe-palette" id="palette"></div>

                <div class="pe-canvas-hint">
                    Przeciągnij węzeł z palety · scroll = zoom · przeciągnij tło = przesuwanie · Del = usuń zaznaczony
                </div>

                <div class="pe-zoom-controls">
                    <button class="pe-btn pe-btn-icon" id="btnZoomOut" title="Oddalenie">－</button>
                    <button class="pe-btn pe-btn-icon" id="btnZoomReset" title="Resetuj zoom">⤢</button>
                    <button class="pe-btn pe-btn-icon" id="btnZoomIn" title="Przybliżenie">＋</button>
                </div>
            </div>

            {{-- BOTTOM PANEL --}}
            <div class="pe-bottom-panel">
                <div class="pe-bp-tabs">
                    <div class="pe-bp-tab active" data-tab="validation">Walidacja <span class="pe-count" id="validationCount">0</span></div>
                    <div class="pe-bp-tab" data-tab="logs">Logi <span class="pe-count" id="logsCount">0</span></div>
                </div>
                <div class="pe-bp-body">
                    <div class="pe-bp-pane active" id="paneValidation"></div>
                    <div class="pe-bp-pane" id="paneLogs"></div>
                </div>
            </div>

            {{-- PROPERTIES --}}
            <div class="pe-sidebar-right">
                <div class="pe-prop-head">
                    <h2>Właściwości</h2>
                    <div class="pe-prop-sub" id="propSub">Nic nie wybrano</div>
                </div>
                <div class="pe-prop-body" id="propBody"></div>
            </div>

        </div>{{-- .pe-content --}}
    </div>{{-- #peShell --}}

    {{-- Toast container --}}
    <div id="toastWrap" class="pe-toast-wrap"></div>

    @push('scripts')
    <script>
    window.ProcedureEditorData = {
        template: @json($template),
        subjectTypes: @json(\App\Enums\ProcedureSubjectType::formOptions()),
        users: @json($users),
        saveUrl:  "{{ route('procedure-templates.update', $template) }}",
        csrfToken: "{{ csrf_token() }}",
        indexUrl: "{{ route('procedure-templates.index') }}",
    };
    </script>
    <script src="{{ asset('js/procedure-editor.js') }}"></script>
    @endpush
</x-app-layout>

@once
@push('scripts')
@endpush
@endonce

<style>
/* ============================================================
   PROCEDURE EDITOR STYLES
   Scoped under .pe-* prefix. Overrides body overflow for full-height.
   ============================================================ */

/* Full-page shell */
.pe-shell {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 56px); /* minus navbar height */
    overflow: hidden;
    --pe-bg: #0a0d14;
    --pe-panel: #10141d;
    --pe-panel2: #151b26;
    --pe-panel3: #1a2130;
    --pe-border: rgba(255,255,255,.08);
    --pe-border-strong: rgba(255,255,255,.14);
    --pe-text-hi: #eef1f8;
    --pe-text-lo: #8b96b3;
    --pe-text-dim: #5b6478;
    --pe-accent: #5b8def;
    --pe-accent2: #3ecf8e;
    --pe-warn: #f0a84e;
    --pe-danger: #ef5a6f;
    --pe-mono: 'SF Mono','Fira Code',Consolas,monospace;
    --pe-sans: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
    font-family: var(--pe-sans);
    font-size: 13px;
    color: var(--pe-text-hi);
    background: var(--pe-bg);
}

/* ─ Toolbar ─────────────────────────────────────────────────── */
.pe-toolbar {
    height: 52px; flex: 0 0 52px;
    display: flex; align-items: center; gap: 8px;
    padding: 0 12px;
    background: var(--pe-panel);
    border-bottom: 1px solid var(--pe-border);
    z-index: 20;
}
.pe-sep { width: 1px; align-self: stretch; background: var(--pe-border); margin: 0 4px; }
.pe-toolbar-spacer { flex: 1; }
.pe-procname {
    background: transparent; border: 1px solid transparent;
    color: var(--pe-text-hi); font-size: 13px; font-weight: 600;
    padding: 5px 8px; border-radius: 7px; width: 220px;
}
.pe-procname:hover,.pe-procname:focus { background: var(--pe-panel2); border-color: var(--pe-border); outline: none; }
.pe-dirty-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--pe-warn); margin-left: 2px; display: none; }
.pe-dirty-dot.show { display: inline-block; }
.pe-zoom-indicator { font-family: var(--pe-mono); font-size: 11px; color: var(--pe-text-lo); padding: 0 6px; min-width: 42px; text-align: center; }

/* ─ Buttons ─────────────────────────────────────────────────── */
.pe-btn {
    background: var(--pe-panel3); border: 1px solid var(--pe-border); color: var(--pe-text-hi);
    padding: 6px 12px; border-radius: 8px; font-size: 12.5px;
    display: inline-flex; align-items: center; gap: 6px;
    cursor: pointer; transition: background .15s, border-color .15s; white-space: nowrap;
    text-decoration: none; font-family: var(--pe-sans);
}
.pe-btn:hover { background: #212a3d; border-color: var(--pe-border-strong); }
.pe-btn:active { transform: translateY(1px); }
.pe-btn:disabled { opacity: .35; cursor: not-allowed; pointer-events: none; }
.pe-btn-primary { background: linear-gradient(135deg,var(--pe-accent),#4a7de0); border-color: transparent; color: #fff; font-weight: 600; }
.pe-btn-primary:hover { filter: brightness(1.08); }
.pe-btn-ghost { background: transparent; border-color: transparent; color: var(--pe-text-lo); }
.pe-btn-ghost:hover { background: var(--pe-panel3); color: var(--pe-text-hi); }
.pe-btn-danger { color: var(--pe-danger); }
.pe-btn-icon { padding: 6px 8px; }
.pe-icon-btn { background: none; border: none; cursor: pointer; padding: 2px 5px; border-radius: 5px; color: var(--pe-text-dim); font-size: 12px; }
.pe-icon-btn:hover { background: rgba(239,90,111,.1); color: var(--pe-danger); }

/* ─ Content grid ─────────────────────────────────────────────── */
.pe-content {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 300px;
    grid-template-rows: 1fr 220px;
    min-height: 0;
}
.pe-canvas-area   { grid-column: 1; grid-row: 1; position: relative; overflow: hidden; background: var(--pe-bg); }
.pe-bottom-panel  { grid-column: 1; grid-row: 2; background: var(--pe-panel); border-top: 1px solid var(--pe-border); display: flex; flex-direction: column; min-height: 0; }
.pe-sidebar-right { grid-column: 2; grid-row: 1/3; background: var(--pe-panel); border-left: 1px solid var(--pe-border); display: flex; flex-direction: column; min-height: 0; }

/* ─ Canvas ───────────────────────────────────────────────────── */
.pe-canvas-bg {
    position: absolute; inset: 0; cursor: grab;
    background-color: var(--pe-bg);
    background-image: radial-gradient(rgba(255,255,255,.09) 1px, transparent 1px);
    overflow: hidden;
}
.pe-canvas-bg.panning { cursor: grabbing; }
.pe-world { position: absolute; top: 0; left: 0; transform-origin: 0 0; }
.pe-edges-svg { position: absolute; top: 0; left: 0; width: 9999px; height: 9999px; overflow: visible; pointer-events: none; }
#nodesLayer { position: absolute; top: 0; left: 0; }

/* ─ Node ─────────────────────────────────────────────────────── */
.pe-node {
    position: absolute; width: 190px; min-height: 84px;
    background: var(--pe-panel2); border: 1.5px solid var(--pe-border);
    border-radius: 12px; padding: 10px 12px 8px;
    cursor: grab; user-select: none; box-sizing: border-box;
    transition: border-color .12s, box-shadow .12s;
}
.pe-node:hover { border-color: var(--pe-border-strong); box-shadow: 0 0 0 3px rgba(91,141,239,.15); }
.pe-node.selected { border-color: var(--pe-accent); box-shadow: 0 0 0 3px rgba(91,141,239,.25); }
.pe-n-del { position: absolute; top: 5px; right: 6px; font-size: 10px; color: var(--pe-text-dim); cursor: pointer; padding: 2px 4px; border-radius: 4px; opacity: 0; transition: opacity .15s; }
.pe-node:hover .pe-n-del { opacity: 1; }
.pe-n-del:hover { background: rgba(239,90,111,.15); color: var(--pe-danger); }
.pe-n-top { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; }
.pe-n-icon { width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.pe-n-name { font-size: 12.5px; font-weight: 600; color: var(--pe-text-hi); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pe-n-type { font-size: 10px; color: var(--pe-text-dim); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.pe-n-meta { display: flex; flex-wrap: wrap; gap: 4px; }
.pe-chip { background: var(--pe-panel3); border: 1px solid var(--pe-border); border-radius: 20px; padding: 1px 7px; font-size: 10px; color: var(--pe-text-lo); }

/* ─ Ports ───────────────────────────────────────────────────── */
/* Visual dot stays small, but ::after adds a much larger invisible
   hit-area so the ports are easy to grab (especially when zoomed out). */
.pe-port { position: absolute; width: 14px; height: 14px; border-radius: 50%; background: var(--pe-accent); border: 2px solid var(--pe-panel); cursor: crosshair; z-index: 12; transition: transform .12s, background .12s; }
.pe-port::after { content: ''; position: absolute; top: 50%; left: 50%; width: 34px; height: 34px; transform: translate(-50%, -50%); border-radius: 50%; }
.pe-port:hover { transform: scale(1.5); background: #78a4ff; }
.pe-port-in  { top: -7px;  left: 50%; transform: translateX(-50%); cursor: default; }
.pe-port-in:hover { transform: translateX(-50%) scale(1.15); background: var(--pe-accent); }
.pe-port-out { bottom: -7px; left: 50%; transform: translateX(-50%); }
.pe-port-out:hover { transform: translateX(-50%) scale(1.5); background: #78a4ff; }
.pe-port-out.connecting { transform: translateX(-50%) scale(1.5); background: var(--pe-accent2); }

/* Highlight a node while it is a valid drop target for a connection being drawn */
.pe-node.pe-drop-target { border-color: var(--pe-accent2); box-shadow: 0 0 0 3px rgba(62,207,142,.3); }
.pe-node.pe-drop-target .pe-port-in { background: var(--pe-accent2); transform: translateX(-50%) scale(1.4); }

/* While drawing a connection: whole canvas hints at the crosshair action */
body.pe-connecting .pe-node { cursor: crosshair; }
body.pe-connecting .pe-canvas-bg { cursor: crosshair; }

/* ─ Edges SVG ───────────────────────────────────────────────── */
.pe-edge-hit  { fill: none; stroke: transparent; stroke-width: 16; cursor: pointer; pointer-events: auto; }
.pe-edge-line { fill: none; stroke: rgba(255,255,255,.22); stroke-width: 1.8; pointer-events: none; }
.pe-edge-line.selected { stroke: var(--pe-accent); stroke-width: 2.5; }
.pe-edge-arrow { fill: rgba(255,255,255,.22); }
.pe-edge-arrow.selected { fill: var(--pe-accent); }
.pe-edge-label-bg { fill: var(--pe-panel3); }
.pe-edge-label-text { fill: var(--pe-text-hi); font-size: 11px; font-family: var(--pe-sans); }
.pe-temp-line { fill: none; stroke: var(--pe-accent); stroke-width: 2; stroke-dasharray: 5 3; pointer-events: none; }

/* ─ Palette ─────────────────────────────────────────────────── */
.pe-palette {
    position: absolute; bottom: 52px; left: 12px;
    display: flex; flex-direction: column; gap: 4px;
    background: var(--pe-panel); border: 1px solid var(--pe-border);
    border-radius: 12px; padding: 10px 10px 8px; z-index: 10;
}
.pe-p-label { font-size: 9.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--pe-text-dim); margin-bottom: 4px; font-weight: 700; }
.pe-palette-item {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 9px; border-radius: 8px; cursor: pointer;
    font-size: 12px; color: var(--pe-text-hi);
    transition: background .12s;
}
.pe-palette-item:hover { background: var(--pe-panel2); }
.pe-pi-icon { width: 20px; height: 20px; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }

/* ─ Canvas hint & zoom controls ────────────────────────────── */
.pe-canvas-hint {
    position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
    font-size: 10.5px; color: var(--pe-text-dim); pointer-events: none; white-space: nowrap;
}
.pe-zoom-controls { position: absolute; bottom: 10px; right: 12px; display: flex; gap: 4px; }

/* ─ Bottom panel ─────────────────────────────────────────────── */
.pe-bp-tabs { display: flex; border-bottom: 1px solid var(--pe-border); flex-shrink: 0; }
.pe-bp-tab { padding: 8px 14px; font-size: 11.5px; cursor: pointer; color: var(--pe-text-lo); display: flex; align-items: center; gap: 6px; transition: color .12s; }
.pe-bp-tab:hover { color: var(--pe-text-hi); }
.pe-bp-tab.active { color: var(--pe-text-hi); border-bottom: 2px solid var(--pe-accent); }
.pe-count { background: var(--pe-panel3); border-radius: 20px; padding: 1px 7px; font-size: 10px; }
.pe-count.pe-err { background: rgba(239,90,111,.25); color: var(--pe-danger); }
.pe-count.pe-warn { background: rgba(240,168,78,.2); color: var(--pe-warn); }
.pe-bp-body { flex: 1; overflow: hidden; position: relative; }
.pe-bp-pane { position: absolute; inset: 0; overflow-y: auto; padding: 8px 12px; display: none; }
.pe-bp-pane.active { display: block; }
.pe-validation-item { display: flex; align-items: flex-start; gap: 8px; padding: 5px 0; font-size: 12px; border-bottom: 1px solid var(--pe-border); }
.pe-vi-icon { font-size: 11px; flex-shrink: 0; margin-top: 1px; }
.pe-validation-ok { color: var(--pe-accent2); padding: 12px 0; font-size: 12.5px; }
.pe-log-line { font-size: 11.5px; padding: 3px 0; border-bottom: 1px solid var(--pe-border); display: flex; gap: 8px; }
.pe-t { color: var(--pe-text-dim); flex-shrink: 0; font-family: var(--pe-mono); }

/* ─ Properties sidebar ─────────────────────────────────────── */
.pe-prop-head { padding: 14px 14px 10px; border-bottom: 1px solid var(--pe-border); flex-shrink: 0; }
.pe-prop-head h2 { margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: var(--pe-text-lo); font-weight: 700; }
.pe-prop-sub { font-size: 11.5px; color: var(--pe-text-hi); font-weight: 600; }
.pe-prop-body { flex: 1; overflow-y: auto; padding: 12px 14px; }
.pe-prop-empty { color: var(--pe-text-dim); font-size: 12px; padding: 16px 0; }
.pe-prop-section-title { font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: var(--pe-text-dim); font-weight: 700; margin: 14px 0 8px; }
.pe-prop-section-title:first-child { margin-top: 0; }

/* ─ Form fields ─────────────────────────────────────────────── */
.pe-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 11px; }
.pe-lbl { font-size: 11px; color: var(--pe-text-lo); text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
.pe-field input[type=text],.pe-field input[type=number],.pe-field input[type=date],
.pe-field select,.pe-field textarea {
    background: var(--pe-panel2); border: 1px solid var(--pe-border); color: var(--pe-text-hi);
    padding: 7px 9px; border-radius: 7px; font-size: 12.5px; width: 100%; outline: none; font-family: var(--pe-sans);
}
.pe-field input:focus,.pe-field select:focus,.pe-field textarea:focus { border-color: var(--pe-accent); }
.pe-field textarea { resize: vertical; min-height: 52px; line-height: 1.5; }
.pe-field input[type=color] { height: 32px; padding: 2px; }
.pe-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.pe-checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 12.5px; margin-bottom: 10px; }
.pe-checkbox-row input { accent-color: var(--pe-accent2); width: 15px; height: 15px; }
.pe-hint { font-size: 10.5px; color: var(--pe-text-dim); margin-top: 3px; margin-bottom: 8px; }
.pe-oktext { font-size: 10.5px; color: var(--pe-accent2); }
.pe-errtext { font-size: 10.5px; color: var(--pe-danger); }

/* ─ List editor (checklist, decision opts) ──────────────────── */
.pe-list-editor { margin-bottom: 8px; }
.pe-list-row { background: var(--pe-panel2); border: 1px solid var(--pe-border); border-radius: 9px; padding: 8px 10px; margin-bottom: 6px; }
.pe-row-head { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
.pe-row-head input { flex: 1; background: var(--pe-panel3); border: 1px solid var(--pe-border); color: var(--pe-text-hi); padding: 5px 8px; border-radius: 6px; font-size: 12px; outline: none; font-family: var(--pe-sans); }
.pe-row-head input:focus { border-color: var(--pe-accent); }
.pe-list-row input[type=text]:not(.pe-row-head input) { background: var(--pe-panel3); border: 1px solid var(--pe-border); color: var(--pe-text-hi); padding: 4px 7px; border-radius: 6px; font-size: 11.5px; outline: none; width: 100%; margin-bottom: 4px; font-family: var(--pe-sans); }
.pe-mini-check { font-size: 11.5px; color: var(--pe-text-lo); display: flex; align-items: center; gap: 6px; }
.pe-add-row-btn { width: 100%; background: transparent; border: 1px dashed var(--pe-border); color: var(--pe-text-lo); padding: 6px; border-radius: 8px; cursor: pointer; font-size: 12px; transition: all .12s; font-family: var(--pe-sans); }
.pe-add-row-btn:hover { border-color: var(--pe-accent); color: var(--pe-accent); }

/* ─ Toast ───────────────────────────────────────────────────── */
.pe-toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.pe-toast { background: var(--pe-panel3); border: 1px solid var(--pe-border-strong); color: var(--pe-text-hi); padding: 10px 16px; border-radius: 10px; font-size: 13px; font-family: var(--pe-sans); box-shadow: 0 4px 16px rgba(0,0,0,.4); animation: peToastIn .2s ease; }
.pe-toast.success { border-color: var(--pe-accent2); color: var(--pe-accent2); }
.pe-toast.danger  { border-color: var(--pe-danger); color: var(--pe-danger); }
@keyframes peToastIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

/* ─ Scrollbars ──────────────────────────────────────────────── */
.pe-prop-body::-webkit-scrollbar,.pe-bp-pane::-webkit-scrollbar { width: 6px; }
.pe-prop-body::-webkit-scrollbar-thumb,.pe-bp-pane::-webkit-scrollbar-thumb { background: var(--pe-panel3); border-radius: 6px; }
</style>
