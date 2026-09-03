<x-app-layout :edgeToEdge="true">
    {{-- No header slot — editor takes full viewport height minus navbar --}}

    <div id="peShell" class="pe-shell">

        {{-- ── TOOLBAR ──────────────────────────────────────────────── --}}
        <div class="pe-toolbar">
            <a id="btnBack" href="{{ route('procedure-templates.index') }}" class="pe-btn pe-btn-ghost" title="Powrót do listy procedur">
                <i class="bi bi-arrow-left"></i> <span class="pe-btn-text">Procedury</span>
            </a>
            <a href="{{ route('procedure-templates.show', $template) }}" class="pe-btn pe-btn-ghost" title="Podgląd szablonu">
                <i class="bi bi-eye"></i> <span class="pe-btn-text">Podgląd</span>
            </a>
            <div class="pe-sep pe-sep-lg"></div>
            <input type="text" id="procNameInput" class="pe-procname" value="{{ $template->name }}" placeholder="Nazwa procedury">
            <span class="pe-dirty-dot" id="dirtyDot" title="Niezapisane zmiany"></span>
            <div class="pe-sep pe-sep-lg"></div>
            <button class="pe-btn pe-btn-icon" id="btnUndo" title="Cofnij (Ctrl+Z)" disabled><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="pe-btn pe-btn-icon" id="btnRedo" title="Ponów (Ctrl+Y)" disabled><i class="bi bi-arrow-clockwise"></i></button>
            @if($chronoEnabled)
                <div class="pe-sep pe-sep-lg"></div>
                <button class="pe-btn" id="btnChrono" title="AskChrono — zaproponuj przepływ na podstawie nazwy, kategorii i opisu">
                    <x-ask-chrono-bot :size="20" /> <span class="pe-btn-text">Chrono</span>
                </button>
            @endif
            <button class="pe-btn pe-btn-icon" id="btnImport" title="Importuj przepływ z tekstu (JSON) — nadpisuje canvas">
                <i class="bi bi-box-arrow-in-down"></i>
            </button>
            <button class="pe-btn pe-btn-icon" id="btnExport" title="Eksportuj bieżący przepływ do pliku JSON">
                <i class="bi bi-box-arrow-up"></i>
            </button>
            <div class="pe-toolbar-spacer"></div>
            <span class="pe-zoom-indicator" id="zoomIndicator">100%</span>
            <button type="button" class="pe-btn pe-btn-icon pe-narrow-only" id="btnToggleBottom" title="Walidacja i logi">
                <i class="bi bi-exclamation-circle"></i>
                <span class="pe-count" id="validationCountFab">0</span>
            </button>
            <button type="button" class="pe-btn pe-btn-icon pe-narrow-only" id="btnToggleProps" title="Właściwości">
                <i class="bi bi-sliders"></i>
            </button>
            <button class="pe-btn pe-btn-primary" id="btnSave" title="Zapisz (Ctrl+S)"><i class="bi bi-save"></i> <span class="pe-btn-text">Zapisz</span></button>
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
                    <button class="pe-btn pe-btn-icon" id="btnZoomOut" title="Oddalenie"><i class="bi bi-dash-lg"></i></button>
                    <button class="pe-btn pe-btn-icon" id="btnZoomReset" title="Resetuj zoom"><i class="bi bi-aspect-ratio"></i></button>
                    <button class="pe-btn pe-btn-icon" id="btnZoomIn" title="Przybliżenie"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>

            <div class="pe-scrim" id="peScrim" hidden></div>

            {{-- BOTTOM PANEL --}}
            <div class="pe-bottom-panel">
                <div class="pe-bp-tabs">
                    <div class="pe-bp-tab active" data-tab="validation">Walidacja <span class="pe-count" id="validationCount">0</span></div>
                    <div class="pe-bp-tab" data-tab="logs">Logi <span class="pe-count" id="logsCount">0</span></div>
                    <div class="pe-toolbar-spacer"></div>
                    <button type="button" class="pe-btn pe-btn-icon pe-panel-close" id="btnCloseBottom" title="Zamknij" aria-label="Zamknij panel walidacji">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="pe-bp-body">
                    <div class="pe-bp-pane active" id="paneValidation"></div>
                    <div class="pe-bp-pane" id="paneLogs"></div>
                </div>
            </div>

            {{-- PROPERTIES --}}
            <div class="pe-sidebar-right">
                <div class="pe-prop-head">
                    <div class="pe-prop-head-text">
                        <h2>Właściwości</h2>
                        <div class="pe-prop-sub" id="propSub">Nic nie wybrano</div>
                    </div>
                    <button type="button" class="pe-btn pe-btn-icon pe-panel-close" id="btnCloseProps" title="Zamknij" aria-label="Zamknij właściwości">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="pe-prop-body" id="propBody"></div>
            </div>

        </div>{{-- .pe-content --}}
    </div>{{-- #peShell --}}

    {{-- Toast container --}}
    <div id="toastWrap" class="pe-toast-wrap"></div>

    {{-- Import modal --}}
    <div id="importModal" class="pe-import-modal" hidden>
        <div class="pe-import-dialog" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
            <div class="pe-import-head">
                <h5 id="importModalTitle"><i class="bi bi-box-arrow-in-down me-2"></i>Importuj przepływ z tekstu</h5>
                <button type="button" class="pe-import-close" id="btnImportClose" aria-label="Zamknij"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="pe-import-body">
                <p class="pe-import-lead">
                    Wklej JSON z krokami (<code>steps</code>) albo pełną definicję (<code>nodes</code> + <code>edges</code>).
                    Import <strong>nadpisze</strong> bieżący canvas — możesz cofnąć przez Ctrl+Z. Zapis do bazy dopiero po „Zapisz”.
                </p>
                <details class="pe-import-format" open>
                    <summary>Oczekiwany format (steps — np. z ChatGPT)</summary>
                    <pre><code>{{ $importFormatExample }}</code></pre>
                </details>
                <details class="pe-import-format">
                    <summary>Alternatywnie: pełna definicja z edytora (export)</summary>
                    <pre><code>{
  "nodes": [ … ],
  "edges": [ … ]
}</code></pre>
                </details>
                <label class="pe-field pe-field--import">
                    <span class="pe-lbl">Wklej tekst</span>
                    <textarea id="importTextarea" rows="10" spellcheck="false" placeholder='{"steps":[{"type":"task","name":"Pierwszy krok"}]}'></textarea>
                </label>
                <div id="importError" class="pe-import-error" hidden></div>
            </div>
            <div class="pe-import-foot">
                <button type="button" class="pe-btn pe-btn-ghost" id="btnImportCancel">Anuluj</button>
                <button type="button" class="pe-btn pe-btn-primary" id="btnImportConfirm">
                    <i class="bi bi-box-arrow-in-down"></i> Importuj
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    window.ProcedureEditorData = {
        template: @json($template),
        subjectTypes: @json(\App\Enums\ProcedureSubjectType::formOptions()),
        users: @json($users),
        actions: @json(app(\App\ProcedureActions\ActionCatalog::class)->editorOptions()),
        saveUrl:  "{{ route('procedure-templates.update', $template) }}",
        csrfToken: "{{ csrf_token() }}",
        indexUrl: "{{ route('procedure-templates.index') }}",
        chronoUrl: @json($chronoEnabled ? route('procedure-templates.chrono-flow', $template) : null),
        chronoProposal: @json($chronoProposal),
        importFlowUrl: @json(route('procedure-templates.import-flow', $template)),
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
body:has(.pe-shell) {
    overflow: hidden;
}
body:has(.pe-shell) main.flex-grow-1 {
    padding: 0 !important;
}
body:has(.pe-shell) .app-page-shell {
    padding: 0;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none;
    border-radius: 0;
}
.pe-shell {
    display: flex;
    flex-direction: column;
    height: calc(100dvh - 5.5rem); /* navbar fallback; JS nadpisuje dokładną wysokość */
    overflow: hidden;
    overscroll-behavior: none;
    --pe-bg: #070a13;
    --pe-panel: #10141d;
    --pe-panel2: #151b26;
    --pe-panel3: #1a2130;
    --pe-border: rgba(255,255,255,.08);
    --pe-border-strong: rgba(255,255,255,.14);
    --pe-text-hi: #eef1f8;
    --pe-text-lo: #8b96b3;
    --pe-text-dim: #5b6478;
    /* Dociągnięte do ChronoLogic — ten sam niebiesko-fioletowy co --primary/
       --accent w app.css, żeby edytor procedur nie wyglądał jak osobna apka.
       --pe-accent2 zostaje "sukcesowo" zielony (var(--success) z app.css),
       bo tu ma znaczenie semantyczne (poprawne połączenie/brak błędów). */
    --pe-accent: #3b82f6;
    --pe-accent2: #10b981;
    --pe-warn: #f59e0b;
    --pe-danger: #ef4444;
    --pe-mono: 'JetBrains Mono','SF Mono','Fira Code',Consolas,monospace;
    --pe-sans: 'Space Grotesk','Inter',-apple-system,BlinkMacSystemFont,sans-serif;
    font-family: var(--pe-sans);
    font-size: 13px;
    color: var(--pe-text-hi);
    background: var(--pe-bg);
}

/* ─ Toolbar ─────────────────────────────────────────────────── */
.pe-toolbar {
    min-height: 52px; flex: 0 0 auto;
    display: flex; align-items: center; gap: 8px;
    padding: 6px 12px;
    background: var(--pe-panel);
    border-bottom: 1px solid var(--pe-border);
    z-index: 20;
    flex-wrap: wrap;
}
.pe-sep { width: 1px; align-self: stretch; background: var(--pe-border); margin: 0 4px; }
.pe-toolbar-spacer { flex: 1; min-width: 4px; }
.pe-procname {
    background: transparent; border: 1px solid transparent;
    color: var(--pe-text-hi); font-size: 13px; font-weight: 600;
    padding: 5px 8px; border-radius: 7px; width: 220px; min-width: 0;
}
.pe-procname:hover,.pe-procname:focus { background: var(--pe-panel2); border-color: var(--pe-border); outline: none; }
.pe-dirty-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--pe-warn); margin-left: 2px; display: none; flex-shrink: 0; }
.pe-dirty-dot.show { display: inline-block; }
.pe-zoom-indicator { font-family: var(--pe-mono); font-size: 11px; color: var(--pe-text-lo); padding: 0 6px; min-width: 42px; text-align: center; }
.pe-narrow-only { display: none !important; }
.pe-panel-close { display: none; }

/* ─ Buttons ─────────────────────────────────────────────────── */
.pe-btn {
    background: var(--pe-panel3); border: 1px solid var(--pe-border); color: var(--pe-text-hi);
    padding: 6px 12px; border-radius: 8px; font-size: 12.5px;
    display: inline-flex; align-items: center; gap: 6px;
    cursor: pointer; transition: background .15s, border-color .15s; white-space: nowrap;
    text-decoration: none; font-family: var(--pe-sans);
    touch-action: manipulation;
}
.pe-btn:hover { background: #212a3d; border-color: var(--pe-border-strong); }
.pe-btn:active { transform: translateY(1px); }
.pe-btn:disabled { opacity: .35; cursor: not-allowed; pointer-events: none; }
.pe-btn-primary { background: linear-gradient(135deg,var(--pe-accent),#a855f7); border-color: transparent; color: #fff; font-weight: 600; }
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
    grid-template-columns: 1fr 340px;
    grid-template-rows: 1fr 220px;
    min-height: 0;
    position: relative;
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
    touch-action: none;
}
.pe-canvas-bg.panning { cursor: grabbing; }
.pe-world { position: absolute; top: 0; left: 0; transform-origin: 0 0; }
.pe-edges-svg { position: absolute; top: 0; left: 0; width: 9999px; height: 9999px; overflow: visible; pointer-events: none; }
#nodesLayer { position: absolute; top: 0; left: 0; }

/* ─ Node ─────────────────────────────────────────────────────── */
.pe-node {
    position: absolute; width: 190px; min-height: 88px;
    background: var(--pe-panel2); border: 1.5px solid var(--pe-border);
    border-radius: 12px; padding: 10px 12px 8px;
    cursor: grab; user-select: none; box-sizing: border-box;
    transition: border-color .12s, box-shadow .12s;
    touch-action: none;
}
.pe-node:hover { border-color: var(--pe-border-strong); box-shadow: 0 0 0 3px rgba(91,141,239,.15); }
.pe-node.selected { border-color: var(--pe-accent); box-shadow: 0 0 0 3px rgba(91,141,239,.25); }
.pe-n-del { position: absolute; top: 5px; right: 6px; font-size: 10px; color: var(--pe-text-dim); cursor: pointer; padding: 2px 4px; border-radius: 4px; opacity: 0; transition: opacity .15s; }
.pe-node:hover .pe-n-del, .pe-node.selected .pe-n-del { opacity: 1; }
.pe-n-del:hover { background: rgba(239,90,111,.15); color: var(--pe-danger); }
.pe-n-top { display: flex; align-items: flex-start; gap: 7px; margin-bottom: 5px; }
.pe-n-icon { width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; margin-top: 1px; }
.pe-n-name { font-size: 12.5px; font-weight: 600; color: var(--pe-text-hi); line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
.pe-n-type { font-size: 10px; color: var(--pe-text-lo); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
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
.pe-prop-head { padding: 16px 16px 12px; border-bottom: 1px solid var(--pe-border); flex-shrink: 0; display: flex; align-items: flex-start; gap: 8px; }
.pe-prop-head-text { flex: 1; min-width: 0; }
.pe-prop-head h2 { margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: var(--pe-text-lo); font-weight: 700; }
.pe-prop-sub { font-size: 13px; color: var(--pe-text-hi); font-weight: 600; line-height: 1.35; overflow-wrap: anywhere; }
.pe-prop-body { flex: 1; overflow-y: auto; padding: 14px 16px 18px; }
.pe-prop-empty { color: var(--pe-text-lo); font-size: 12.5px; padding: 16px 0; line-height: 1.5; }
.pe-prop-section-title { font-size: 10.5px; text-transform: uppercase; letter-spacing: .07em; color: var(--pe-text-lo); font-weight: 700; margin: 16px 0 10px; }
.pe-prop-section-title:first-child { margin-top: 0; }

/* ─ Form fields ─────────────────────────────────────────────── */
.pe-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 13px; }
.pe-lbl { font-size: 11px; color: var(--pe-text-lo); text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
.pe-field input[type=text],.pe-field input[type=number],.pe-field input[type=date],
.pe-field select,.pe-field textarea {
    background: var(--pe-panel3); border: 1px solid var(--pe-border-strong); color: var(--pe-text-hi);
    padding: 8px 10px; border-radius: 8px; font-size: 13px; width: 100%; outline: none; font-family: var(--pe-sans);
    line-height: 1.45;
}
.pe-field input:focus,.pe-field select:focus,.pe-field textarea:focus { border-color: var(--pe-accent); box-shadow: 0 0 0 2px rgba(59,130,246,.18); }
.pe-field textarea { resize: vertical; min-height: 64px; line-height: 1.55; }
.pe-field textarea#propInstructions,
.pe-field textarea#metaDesc { min-height: 96px; }
.pe-field textarea#propDesc { min-height: 72px; }
.pe-field input[type=color] { height: 36px; padding: 2px; }
.pe-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.pe-checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 12.5px; margin-bottom: 10px; }
.pe-checkbox-row input { accent-color: var(--pe-accent2); width: 15px; height: 15px; }
.pe-hint { font-size: 11px; color: var(--pe-text-lo); margin-top: 3px; margin-bottom: 8px; line-height: 1.45; }
.pe-oktext { font-size: 10.5px; color: var(--pe-accent2); }
.pe-errtext { font-size: 10.5px; color: var(--pe-danger); }

/* ─ List editor (checklist, decision opts) ──────────────────── */
.pe-list-editor { margin-bottom: 8px; }
.pe-list-row { background: var(--pe-panel3); border: 1px solid var(--pe-border-strong); border-radius: 9px; padding: 9px 11px; margin-bottom: 7px; }
.pe-row-head { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
.pe-row-head input { flex: 1; background: var(--pe-panel2); border: 1px solid var(--pe-border); color: var(--pe-text-hi); padding: 6px 9px; border-radius: 6px; font-size: 12.5px; outline: none; font-family: var(--pe-sans); }
.pe-row-head input:focus { border-color: var(--pe-accent); }
.pe-list-row input[type=text]:not(.pe-row-head input) { background: var(--pe-panel2); border: 1px solid var(--pe-border); color: var(--pe-text-hi); padding: 6px 9px; border-radius: 6px; font-size: 12px; outline: none; width: 100%; margin-bottom: 5px; font-family: var(--pe-sans); }
.pe-mini-check { font-size: 12px; color: var(--pe-text-lo); display: flex; align-items: center; gap: 6px; }
.pe-add-row-btn { width: 100%; background: transparent; border: 1px dashed var(--pe-border); color: var(--pe-text-lo); padding: 6px; border-radius: 8px; cursor: pointer; font-size: 12px; transition: all .12s; font-family: var(--pe-sans); }
.pe-add-row-btn:hover { border-color: var(--pe-accent); color: var(--pe-accent); }

/* ─ Import modal ─────────────────────────────────────────────── */
.pe-import-modal {
    position: fixed; inset: 0; z-index: 3000;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,.78); padding: 16px;
}
.pe-import-modal[hidden] { display: none !important; }
.pe-import-dialog {
    width: min(720px, 100%); max-height: calc(100vh - 32px);
    display: flex; flex-direction: column;
    background: var(--pe-panel); border: 1px solid var(--pe-border-strong);
    border-radius: 16px; box-shadow: 0 16px 48px rgba(0,0,0,.45);
}
.pe-import-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 14px 16px; border-bottom: 1px solid var(--pe-border);
}
.pe-import-head h5 { margin: 0; font-size: 15px; font-weight: 600; }
.pe-import-close { background: none; border: none; color: var(--pe-text-lo); cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.pe-import-close:hover { background: var(--pe-panel3); color: var(--pe-text-hi); }
.pe-import-body { padding: 14px 16px; overflow-y: auto; }
.pe-import-lead { font-size: 12.5px; color: var(--pe-text-lo); line-height: 1.55; margin: 0 0 12px; }
.pe-import-lead code { color: var(--pe-accent); font-family: var(--pe-mono); font-size: 11.5px; }
.pe-import-format { margin-bottom: 10px; border: 1px solid var(--pe-border); border-radius: 10px; overflow: hidden; }
.pe-import-format summary { cursor: pointer; padding: 8px 12px; font-size: 12px; font-weight: 600; color: var(--pe-text-lo); background: var(--pe-panel2); }
.pe-import-format pre {
    margin: 0; padding: 10px 12px; max-height: 180px; overflow: auto;
    background: rgba(0,0,0,.28); font-family: var(--pe-mono); font-size: 11px; line-height: 1.45; color: var(--pe-text-hi);
}
.pe-field--import textarea { font-family: var(--pe-mono); font-size: 12px; min-height: 160px; }
.pe-import-error { margin-top: 10px; padding: 10px 12px; border-radius: 8px; background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.35); color: #fca5a5; font-size: 12.5px; line-height: 1.45; }
.pe-import-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px; border-top: 1px solid var(--pe-border); }

/* ─ Toast ───────────────────────────────────────────────────── */
.pe-toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.pe-toast { background: var(--pe-panel3); border: 1px solid var(--pe-border-strong); color: var(--pe-text-hi); padding: 10px 16px; border-radius: 10px; font-size: 13px; font-family: var(--pe-sans); box-shadow: 0 4px 16px rgba(0,0,0,.4); animation: peToastIn .2s ease; }
.pe-toast.success { border-color: var(--pe-accent2); color: var(--pe-accent2); }
.pe-toast.danger  { border-color: var(--pe-danger); color: var(--pe-danger); }
@keyframes peToastIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

/* ─ Scrollbars ──────────────────────────────────────────────── */
.pe-prop-body::-webkit-scrollbar,.pe-bp-pane::-webkit-scrollbar { width: 6px; }
.pe-prop-body::-webkit-scrollbar-thumb,.pe-bp-pane::-webkit-scrollbar-thumb { background: var(--pe-panel3); border-radius: 6px; }

.pe-scrim {
    display: none;
    position: absolute; inset: 0; z-index: 24;
    background: rgba(0,0,0,.5);
}

/* ─ Responsive: tablet / phone ─────────────────────────────────────── */
@media (max-width: 1099.98px) {
    .pe-narrow-only { display: inline-flex !important; }
    .pe-panel-close { display: inline-flex; }
    .pe-content {
        grid-template-columns: 1fr;
        grid-template-rows: 1fr;
    }
    .pe-canvas-area { grid-column: 1; grid-row: 1; }
    .pe-sidebar-right,
    .pe-bottom-panel {
        position: absolute;
        z-index: 30;
        left: auto; right: 0; top: 0; bottom: 0;
        width: min(360px, 100%);
        max-width: 100%;
        grid-column: auto; grid-row: auto;
        transform: translateX(105%);
        transition: transform .2s ease;
        box-shadow: -12px 0 32px rgba(0,0,0,.4);
        border-left: 1px solid var(--pe-border);
    }
    .pe-bottom-panel {
        top: auto;
        left: 0; right: 0;
        width: 100%;
        height: min(48vh, 360px);
        transform: translateY(110%);
        box-shadow: 0 -12px 32px rgba(0,0,0,.4);
        border-left: none;
        border-top: 1px solid var(--pe-border);
        border-radius: 16px 16px 0 0;
    }
    .pe-shell.pe-props-open .pe-sidebar-right { transform: none; }
    .pe-shell.pe-bottom-open .pe-bottom-panel { transform: none; }
    .pe-shell.pe-props-open .pe-scrim,
    .pe-shell.pe-bottom-open .pe-scrim { display: block; }
    .pe-canvas-hint { display: none; }
}

@media (max-width: 767.98px) {
    .pe-btn-text { display: none; }
    .pe-sep-lg { display: none; }
    .pe-zoom-indicator { display: none; }
    .pe-toolbar { padding: 6px 8px; gap: 6px; }
    .pe-procname { flex: 1 1 110px; width: auto; }
    .pe-btn { padding: 6px 8px; }
    .pe-sidebar-right {
        top: auto; left: 0; right: 0; bottom: 0;
        width: 100%;
        height: min(78dvh, 640px);
        transform: translateY(110%);
        border-left: none;
        border-top: 1px solid var(--pe-border);
        border-radius: 16px 16px 0 0;
        box-shadow: 0 -12px 32px rgba(0,0,0,.45);
    }
    .pe-shell.pe-props-open .pe-sidebar-right { transform: none; }
    .pe-bottom-panel { height: min(56dvh, 420px); }
    .pe-palette {
        flex-direction: row;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x;
        bottom: 8px; left: 8px; right: 92px;
        max-width: none;
        padding: 6px;
        gap: 2px;
    }
    .pe-p-label { display: none; }
    .pe-palette-item {
        flex-direction: column;
        gap: 2px;
        padding: 6px 8px;
        font-size: 9.5px;
        flex-shrink: 0;
        min-width: 52px;
        text-align: center;
    }
    .pe-zoom-controls { bottom: 8px; right: 8px; }
    .pe-row2 { grid-template-columns: 1fr; }
    .pe-import-dialog { max-height: calc(100dvh - 16px); border-radius: 14px; }
    .pe-import-format pre { max-height: 110px; }
    .pe-field--import textarea { min-height: 120px; }
    .pe-import-foot { flex-wrap: wrap; }
    .pe-import-foot .pe-btn { flex: 1; justify-content: center; }
    .pe-toast-wrap { left: 12px; right: 12px; bottom: 12px; }
    .pe-n-del { opacity: 1; }
}
</style>
