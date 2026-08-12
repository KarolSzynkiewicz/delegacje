{{-- Style kroku 1 planera (przypisania projekt/role) — używane w livewire/steps/step1-project-assignments --}}
<style>
/* ── Step 1 – redesign ────────────────────────────────────────── */
.s1-panel { height: 100%; }

/* Left: compact filters */
.s1-filters { display:flex; flex-direction:column; gap:8px; margin-bottom:14px; }
.s1-filters .form-select-sm,
.s1-filters .form-control-sm { border-radius:10px !important; }

/* Employee card – compact chip-style */
.s1-emp-card {
    display:flex; align-items:center; gap:10px;
    padding: 8px 10px;
    border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    cursor: grab;
    transition: all .15s ease;
    margin-bottom: 6px;
}
.s1-emp-card:hover {
    background: rgba(59,130,246,0.08);
    border-color: rgba(59,130,246,0.3);
}
.s1-emp-avatar {
    width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.75rem; flex:0 0 auto;
    background: rgba(59,130,246,0.2); color:#93c5fd;
}
.s1-emp-avatar img { width:34px; height:34px; border-radius:50%; object-fit:cover; }
.s1-emp-name { font-size:.85rem; font-weight:600; line-height:1.2; }
.s1-emp-roles { display:flex; flex-wrap:wrap; gap:3px; margin-top:3px; }
.s1-emp-role-pill {
    font-size:.68rem; padding:1px 7px; border-radius:20px;
    background:rgba(168,85,247,0.10); border:1px solid rgba(168,85,247,0.18); color:#c4b5fd;
}
.s1-emp-doc-warn { font-size:.68rem; color:#fbbf24; margin-top:3px; }
.s1-emp-rotation { font-size:.68rem; color:#94a3b8; margin-top:2px; opacity:.75; }
.s1-emp-rotation--missing {
    display:flex; align-items:center; justify-content:space-between; gap:6px;
    color:#fca5a5; opacity:1;
}
.s1-emp-add-rotation {
    flex:0 0 auto;
    border:1px solid rgba(252,165,165,0.35);
    background:rgba(239,68,68,0.12);
    color:#fecaca;
    border-radius:999px;
    font-size:.65rem;
    font-weight:600;
    line-height:1;
    padding:3px 8px;
    cursor:pointer;
}
.s1-emp-add-rotation:hover {
    background:rgba(239,68,68,0.22);
    border-color:rgba(252,165,165,0.55);
    color:#fff;
}
.s1-emp-drag-hint {
    font-size:.68rem; color:rgba(148,163,184,0.5);
    margin-top:3px; display:flex; align-items:center; gap:3px;
}
.s1-full-banner {
    border-radius:10px;
    padding: 8px 12px;
    background: rgba(239,68,68,0.09);
    border: 1px solid rgba(239,68,68,0.22);
    color: #fca5a5;
    font-size: .8rem;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}

/* Right: project + role cards */
.s1-project-block {
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.02);
    padding: 14px 16px;
    margin-bottom: 16px;
}
.s1-project-header { margin-bottom: 12px; }
.s1-project-name { font-size:.95rem; font-weight:700; }
.s1-project-loc { font-size:.75rem; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:4px; }
.s1-roles-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; }
@media(max-width:900px){ .s1-roles-grid{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px){ .s1-roles-grid{ grid-template-columns: 1fr; } }

.s1-role-card {
    border-radius: 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    padding: 10px 12px;
    display: flex; flex-direction: column; gap: 8px;
}
.s1-role-header { display:flex; align-items:center; justify-content:space-between; gap:6px; }
.s1-role-name { font-size:.82rem; font-weight:700; }
.s1-gap-pill {
    font-size:.68rem; padding:2px 8px; border-radius:20px; white-space:nowrap;
    background:rgba(245,158,11,0.09); border:1px solid rgba(245,158,11,0.18); color:#d97706;
    display:flex; align-items:center; gap:4px;
}
.s1-gap-pill > i { font-size:.65rem; }

/* Drop zone */
.s1-drop-zone {
    border-radius: 10px;
    border: 1px dashed rgba(255,255,255,0.10);
    background: rgba(255,255,255,0.01);
    min-height: 48px;
    display: flex; flex-direction:row; align-items: center; justify-content: center; gap: 6px;
    transition: all .15s ease;
    cursor: pointer;
    padding: 6px 10px;
}
.s1-drop-zone:hover,
.s1-drop-zone.drag-over {
    border-color: rgba(59,130,246,0.40);
    background: rgba(59,130,246,0.05);
}
.s1-drop-hint { font-size:.72rem; color:rgba(148,163,184,0.40); line-height:1.3; }
.s1-drop-zone-icon {
    color: rgba(148,163,184,0.3);
    font-size: .95rem;
    flex: 0 0 auto;
}

/* Assigned chips */
.s1-assigned-list { display:flex; flex-direction:column; gap:5px; }
.s1-assigned-chip {
    display:flex; align-items:center; gap:7px;
    border-radius: 8px; padding: 5px 8px;
    background: rgba(59,130,246,0.09);
    border: 1px solid rgba(59,130,246,0.22);
    font-size:.78rem;
}
.s1-chip-avatar {
    width:22px; height:22px; border-radius:50%;
    background: rgba(59,130,246,0.25); color:#93c5fd;
    display:flex; align-items:center; justify-content:center;
    font-size:.63rem; font-weight:700; flex:0 0 auto;
}
.s1-chip-avatar img { width:22px; height:22px; border-radius:50%; object-fit:cover; }
.s1-chip-name { flex:1 1 auto; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.s1-chip-remove {
    background:none; border:none; padding:0; line-height:1;
    color:rgba(148,163,184,0.5); cursor:pointer; font-size:.75rem; flex:0 0 auto;
}
.s1-chip-remove:hover { color:#fca5a5; }

/* Left panel hint text */
.s1-hint { font-size:.77rem; color:rgba(148,163,184,0.55); margin-bottom:10px; display:flex; align-items:center; gap:5px; }

/* Nagłówki paneli, badge pojemności, puste stany, paginacja */
.s1-panel-title { font-size: .9rem; }
.s1-capacity-badge {
    font-size: .7rem;
    background: rgba(239,68,68,0.14);
    color: #fca5a5;
    border: 1px solid rgba(239,68,68,0.28);
    border-radius: 20px;
    padding: 2px 8px;
}
.s1-grip-icon {
    opacity: .3;
    font-size: .8rem;
    flex: 0 0 auto;
}
.s1-empty-state {
    text-align: center;
    padding: 24px 0;
    color: rgba(148,163,184,0.5);
}
.s1-empty-state__icon {
    font-size: 1.6rem;
    display: block;
    margin-bottom: 6px;
}
.s1-empty-state__text { font-size: .82rem; }

.s1-pagination-bar { border-top: 1px solid rgba(255,255,255,0.07); }
.s1-pagination-btn {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    font-size: .75rem;
    padding: 3px 10px;
    color: #94a3b8;
}
.s1-pagination-btn--disabled { color: rgba(148,163,184,0.3); }
.s1-pagination-info {
    font-size: .72rem;
    color: rgba(148,163,184,0.6);
}

.s1-section-subtitle {
    font-size: .75rem;
    color: rgba(148,163,184,0.6);
    margin-top: 2px;
}
.s1-project-search-input {
    max-width: 200px;
    border-radius: 10px !important;
}
.s1-project-loc-icon { color: #6366f1; }

.s1-empty-success {
    text-align: center;
    padding: 32px 0;
    color: rgba(148,163,184,0.5);
}
.s1-empty-success__icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 8px;
    color: #10b981;
}
.s1-empty-success__text { font-size: .85rem; }

.s1-assignment-modal-visible { display: block; }
.s1-modal-footer-error { font-size: 0.875rem; }

/* Podgląd statyczny (np. katalog /2) — bez kursora „grab” */
.s1-emp-card--static,
.s1-drop-zone--static { cursor: default !important; }
</style>
