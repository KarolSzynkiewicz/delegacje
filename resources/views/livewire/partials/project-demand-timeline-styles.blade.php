<style>
    .pdt .atl-marker__label { white-space: nowrap; }
    .pdt-marker--project-start { border-left: 2px solid #34d399; }
    .pdt-marker--project-end { border-left: 2px solid #f472b6; }
    .pdt-marker--today { border-left: 2px dashed #fbbf24; }
    .pdt .atl-bar { cursor: grab; }
    .pdt .atl-bar:active { cursor: grabbing; }
    .pdt .atl-lane[data-can-create="1"] .atl-track { cursor: crosshair; }

    .pdt-scale .atl-scale-track { height: 2.35rem; }
    .pdt-tick {
        top: 0.2rem;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.05rem;
        padding-left: 0.35rem;
        box-sizing: border-box;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--text-muted);
        line-height: 1.05;
    }
    .pdt-tick.is-pad { opacity: 0.45; }
    .pdt-tick.is-project { color: var(--text-main); }
    .pdt-tick.is-current {
        color: #fbbf24;
        border-left-color: rgba(251, 191, 36, 0.55);
    }
    .pdt-tick__week {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .pdt-tick__day {
        font-size: 0.58rem;
        opacity: 0.72;
        letter-spacing: 0.02em;
    }

    .pdt-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }
    .pdt-grid__line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 0;
        border-left: 1px solid rgba(255, 255, 255, 0.06);
    }
    .pdt-grid__line.is-project { border-left-color: rgba(148, 163, 184, 0.14); }
    .pdt-grid__line.is-current { border-left: 1px dashed rgba(251, 191, 36, 0.45); }

    .pdt-lane {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    .pdt-lane--active {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.05), transparent 18%);
    }
    .pdt-lane--active .atl-label {
        color: var(--text-main);
        font-weight: 700;
    }
    .pdt-lane--active .atl-track {
        background:
            repeating-linear-gradient(
                90deg,
                transparent 0,
                transparent calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.035) calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.035) var(--atl-day)
            );
    }
    .pdt-lane--empty { opacity: 0.55; }
    .pdt-lane--empty .atl-label { font-weight: 550; }
    .pdt-lane--empty .atl-track {
        background: rgba(255, 255, 255, 0.015);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    }
    .pdt .atl-bar--project {
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.12),
            0 6px 16px rgba(59, 130, 246, 0.18);
    }

    .pdt-agg {
        margin-top: 0.45rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 0.45rem;
        background: linear-gradient(90deg, rgba(168, 85, 247, 0.06), transparent 22%);
    }
    .pdt-agg .atl-label {
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 0.15rem;
        line-height: 1.1;
        color: var(--text-main);
        font-weight: 700;
    }
    .pdt-agg__max {
        font-size: 0.58rem;
        letter-spacing: 0.04em;
        text-transform: none;
        color: rgba(148, 163, 184, 0.85);
    }
    .pdt-agg__track {
        height: 4.6rem !important;
        cursor: default !important;
        background:
            repeating-linear-gradient(
                90deg,
                transparent 0,
                transparent calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.04) calc(var(--atl-day) - 1px),
                rgba(255, 255, 255, 0.04) var(--atl-day)
            );
    }
    .pdt-agg__band {
        position: absolute;
        top: 0.2rem;
        bottom: 0.2rem;
        border-radius: 8px;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12), rgba(168, 85, 247, 0.1));
        box-shadow: inset 0 0 0 1px rgba(168, 85, 247, 0.28);
        pointer-events: none;
        z-index: 0;
    }
    .pdt-agg__col {
        position: absolute;
        bottom: 0.35rem;
        top: 0.45rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 0.15rem;
        pointer-events: none;
        z-index: 1;
    }
    .pdt-agg__bar {
        width: calc(100% - 12px);
        min-height: 3px;
        border-radius: 5px 5px 2px 2px;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.95), rgba(168, 85, 247, 0.75));
        box-shadow: 0 0 12px rgba(59, 130, 246, 0.18);
    }
    .pdt-agg__n {
        font-size: 0.62rem;
        color: var(--text-main);
        line-height: 1;
    }
    .pdt-bound {
        position: absolute;
        top: 0.15rem;
        bottom: 0.15rem;
        width: 18px;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        transform: translateX(-50%);
        z-index: 5;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.15rem;
        pointer-events: auto;
    }
    .pdt-bound.is-draggable { cursor: ew-resize; touch-action: none; }
    .pdt-bound__grip {
        flex: 1 1 auto;
        width: 3px;
        border-radius: 3px;
        background: #34d399;
        box-shadow: 0 0 0 1px rgba(7, 10, 19, 0.55);
    }
    .pdt-bound--end .pdt-bound__grip { background: #f472b6; }
    .pdt-bound__label {
        font-size: 0.55rem;
        letter-spacing: 0.06em;
        color: var(--text-main);
        background: rgba(13, 18, 30, 0.92);
        border: 1px solid var(--glass-border);
        border-radius: 4px;
        padding: 0.05rem 0.25rem;
        white-space: nowrap;
        pointer-events: none;
    }
    .pdt-bound-menu { min-width: 260px; max-width: 320px; }

    .pdt--client .atl-bar {
        cursor: default;
    }
    .pdt--client .atl-label {
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 0.12rem;
        line-height: 1.15;
    }
    .cpt-lane-link {
        color: inherit;
        text-decoration: none;
        font-weight: 700;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cpt-lane-link:hover {
        color: #fff;
        text-decoration: underline;
        text-underline-offset: 0.15em;
    }
    .cpt-lane-peak {
        font-size: 0.58rem;
        letter-spacing: 0.04em;
        color: rgba(148, 163, 184, 0.85);
        text-transform: none;
    }
</style>
