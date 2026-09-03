<style>
    .sb { --sb-line: rgba(255,255,255,.08); }
    .sb-hero { display:grid; grid-template-columns: auto 1fr auto; gap:1.5rem; align-items:center; }
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
    .sb-runway { position:relative; height:64px; margin-top:.5rem; }
    .sb-runway-track { position:absolute; left:0; right:0; top:28px; height:4px; border-radius:99px; background:rgba(255,255,255,.08); overflow:hidden; }
    .sb-runway-fill { height:100%; background:linear-gradient(90deg, var(--primary), var(--accent)); }
    .sb-ms { position:absolute; top:10px; transform:translateX(-50%); text-align:center; width:max-content; max-width:140px; }
    .sb-ms-dot { width:14px; height:14px; border-radius:50%; margin:0 auto 4px; border:2px solid #fff; }
    .sb-task { display:grid; grid-template-columns: 28px 22px 1fr auto auto; gap:.6rem; align-items:center; padding:.55rem .7rem; border:1px solid var(--sb-line); border-radius:12px; background:rgba(15,23,42,.35); margin-bottom:.45rem; }
    .sb-task.is-over { outline:1px dashed var(--primary); }
    .sb-grip { cursor:grab; color:rgba(255,255,255,.28); }
    .sb-pos { font-size:.7rem; color:var(--text-muted); font-variant-numeric:tabular-nums; }
    .sb-work { display:flex; align-items:center; gap:.6rem; margin-bottom:.45rem; }
    .sb-work-bar { flex:1; height:8px; border-radius:99px; background:rgba(255,255,255,.08); overflow:hidden; }
    .sb-work-bar > span { display:block; height:100%; background:linear-gradient(90deg, var(--primary), var(--accent)); }
    @media (max-width: 992px) {
        .sb-hero, .sb-grid, .sb-kpis { grid-template-columns:1fr; }
    }
</style>
