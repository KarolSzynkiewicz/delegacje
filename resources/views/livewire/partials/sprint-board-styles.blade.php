<style>
    .sb { --sb-line: rgba(255,255,255,.08); }
    .sb-hero { display:grid; grid-template-columns: auto 1fr auto; gap:1.35rem; align-items:center; }
    .sb-goal-icon { width:72px; height:72px; border-radius:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:linear-gradient(135deg, rgba(59,130,246,.2), rgba(168,85,247,.22)); border:1px solid rgba(168,85,247,.38); color:var(--accent); font-size:1.85rem; }
    .sb-goal-kicker { display:inline-flex; align-items:center; gap:.4rem; color:var(--accent) !important; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; font-weight:700; margin-bottom:.35rem; }
    .sb-goal-text { font-size:1.32rem; font-weight:600; letter-spacing:-.03em; line-height:1.35; color:var(--text-main) !important; margin:0 0 .7rem; }
    .sb-goal-text.is-empty { color:var(--text-muted) !important; font-weight:500; font-size:1.05rem; }
    .sb-ring { position:relative; width:110px; height:110px; }
    .sb-ring svg { transform:rotate(-90deg); }
    .sb-ring-label { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .sb-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; }
    .sb-kpi { background:rgba(15,23,42,.45); border:1px solid var(--sb-line); border-radius:14px; padding:.9rem 1rem; }
    .sb-kpi .v { font-size:1.45rem; font-weight:700; letter-spacing:-.03em; line-height:1.1; }
    .sb-kpi .l { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-top:.15rem; }
    .sb-grid { display:grid; grid-template-columns: 1.4fr .9fr; gap:1rem; }
    .sb-chart { position:relative; height:220px; overflow:hidden; }
    .sb-chart canvas { display:block; }
    .sb-runway { position:relative; height:18px; margin: .1rem 0 .7rem; }
    .sb-runway-track { position:absolute; left:0; right:0; top:7px; height:3px; border-radius:99px; background:rgba(255,255,255,.08); overflow:hidden; }
    .sb-runway-fill { height:100%; background:linear-gradient(90deg, var(--primary), var(--accent)); }
    .sb-ms { position:absolute; top:2px; transform:translateX(-50%); }
    .sb-ms-dot { width:12px; height:12px; border-radius:50%; border:2px solid rgba(255,255,255,.85); box-shadow: 0 0 0 2px rgba(7,10,19,.55); }
    .sb-list-card.card { display:flex; flex-direction:column; flex:1 1 auto; width:100%; }
    .sb-list-head { flex-shrink:0; margin-bottom:.75rem; }
    .sb-list-title { display:flex; align-items:flex-start; gap:.45rem; font-size:.98rem; font-weight:600; letter-spacing:-.02em; line-height:1.3; color:var(--text-main) !important; margin:0; }
    .sb-list-title i { color:var(--accent) !important; margin-top:.15rem; flex-shrink:0; }
    .sb-list-hint { font-size:.78rem; color:var(--text-muted) !important; margin:.3rem 0 .55rem; line-height:1.4; }
    .sb-meter { display:flex; align-items:center; gap:.55rem; }
    .sb-meter-track { flex:1; height:6px; border-radius:99px; background:rgba(255,255,255,.08); overflow:hidden; }
    .sb-meter-track > span { display:block; height:100%; background:linear-gradient(90deg, var(--primary), var(--accent)); }
    .sb-meter-count { font-size:.72rem; color:var(--text-muted) !important; min-width:2.4rem; text-align:right; }
    .sb-stack { display:flex; flex-direction:column; flex:1 1 auto; }
    .sb-stack-body { flex:1 0 auto; }
    .sb-ms-row { display:flex; align-items:flex-start; gap:.5rem; padding:.28rem 0; }
    .sb-ms-row + .sb-ms-row { border-top:1px solid rgba(255,255,255,.05); }
    .sb-ms-row .sb-check { flex:1; min-width:0; align-items:flex-start; }
    .sb-ms-row .sb-check span { overflow-wrap:anywhere; line-height:1.35; }
    .sb-ms-meta { flex-shrink:0; display:flex; align-items:center; gap:.35rem; padding-top:.1rem; white-space:nowrap; }
    .sb-ms-meta .badge { font-size:.62rem; padding:.18rem .4rem; letter-spacing:.02em; }
    .sb-task { display:grid; grid-template-columns: 28px 22px 1fr auto auto; gap:.6rem; align-items:center; padding:.55rem .7rem; border:1px solid var(--sb-line); border-radius:12px; background:rgba(15,23,42,.35); margin-bottom:.45rem; }
    .sb-task.is-over { outline:1px dashed var(--primary); }
    .sb-grip { cursor:grab; color:rgba(255,255,255,.28); }
    .sb-pos { font-size:.7rem; color:var(--text-muted); font-variant-numeric:tabular-nums; }
    .sb-work { display:flex; align-items:center; gap:.6rem; margin-bottom:.45rem; }
    .sb-work-bar { flex:1; height:8px; border-radius:99px; background:rgba(255,255,255,.08); overflow:hidden; }
    .sb-work-bar > span { display:block; height:100%; background:linear-gradient(90deg, var(--primary), var(--accent)); }
    .sb-check { display:flex; align-items:center; gap:.55rem; padding:.2rem 0; margin:0; background:transparent; border:none; cursor:pointer; }
    .sb-check input[type="checkbox"] { width:16px; height:16px; margin:0; flex-shrink:0; cursor:pointer; appearance:none; -webkit-appearance:none; background:var(--bg-input); border:1.5px solid var(--glass-border); border-radius:4px; position:relative; }
    .sb-check input[type="checkbox"]:checked { background:linear-gradient(135deg, var(--primary), var(--accent)); border-color:var(--primary); }
    .sb-check input[type="checkbox"]:checked::after { content:''; position:absolute; left:4px; top:1px; width:4px; height:8px; border:solid white; border-width:0 2px 2px 0; transform:rotate(45deg); }
    .sb-check input[type="checkbox"]:disabled { cursor:default; opacity:.9; }
    .sb-check.is-done span { text-decoration:line-through; color:var(--text-muted); }
    .sb-check span { font-size:.88rem; overflow-wrap:anywhere; }
    .sb-add-wrap { margin-top:auto; flex-shrink:0; padding-top:.85rem; }
    .sb-add { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.4rem; align-items:center; }
    .sb-add--ms { grid-template-columns:minmax(0,1fr) 9.2rem auto; }
    .sb-add .form-control { min-width:0; width:100%; }
    .sb-add .btn { padding:.25rem .7rem; font-size:.8rem; white-space:nowrap; height:calc(1.5em + .5rem + 2px); }
    .sb-ghost { color:rgba(255,255,255,.32) !important; }
    .sb-ghost:hover { color:var(--danger) !important; }
    @media (max-width: 992px) {
        .sb-hero, .sb-grid, .sb-kpis { grid-template-columns:1fr; }
        .sb-goal-icon { margin-inline: auto; }
        .sb-hero .text-end { text-align:center !important; }
    }
</style>
