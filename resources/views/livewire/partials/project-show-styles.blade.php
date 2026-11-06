<style>
    .pj-shell { display: flex; flex-direction: column; gap: 1rem; }

    .pj-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--glass-border);
        border-radius: 1rem;
        padding: 1.15rem 1.25rem 1.25rem;
        background:
            radial-gradient(120% 90% at 0% 0%, rgba(59, 130, 246, 0.22), transparent 55%),
            radial-gradient(90% 80% at 100% 0%, rgba(168, 85, 247, 0.18), transparent 50%),
            rgba(13, 18, 30, 0.48);
    }
    .pj-hero__top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.85rem 1.25rem;
        margin-bottom: 1rem;
    }
    .pj-hero__kicker {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.68rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }
    .pj-hero__title {
        font-size: clamp(1.15rem, 2.2vw, 1.55rem);
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.25;
        margin: 0;
    }
    .pj-hero__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.65rem;
    }
    .pj-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        border: 1px solid var(--glass-border);
        background: rgba(255, 255, 255, 0.03);
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .pj-chip--strong {
        color: var(--text-main);
        border-color: rgba(168, 85, 247, 0.35);
        background: rgba(168, 85, 247, 0.1);
    }
    .pj-chip a { color: inherit; text-decoration: none; }
    .pj-chip a:hover { color: #fff; }

    .pj-hero__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.65rem;
        min-width: min(100%, 22rem);
    }
    .pj-stat {
        padding: 0.65rem 0.75rem;
        border-radius: 0.7rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(7, 10, 19, 0.35);
    }
    .pj-stat__label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.62rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.2rem;
    }
    .pj-stat__value {
        font-size: 0.95rem;
        font-weight: 650;
        color: var(--text-main);
        font-variant-numeric: tabular-nums;
    }

    .pj-progress {
        margin-top: 0.35rem;
    }
    .pj-progress__meta {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.45rem;
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .pj-progress__meta strong {
        color: var(--text-main);
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
    }
    .pj-progress__track {
        height: 0.55rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }
    .pj-progress__fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--primary), var(--accent));
        box-shadow: 0 0 18px rgba(59, 130, 246, 0.35);
        transition: width .35s ease;
    }
    .pj-progress__fill--open {
        width: 100%;
        background: linear-gradient(90deg, rgba(59,130,246,.55), rgba(168,85,247,.55));
        background-size: 200% 100%;
        animation: pj-progress-pulse 2.4s linear infinite;
    }
    @keyframes pj-progress-pulse {
        0% { background-position: 0% 0; }
        100% { background-position: 200% 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pj-progress__fill--open { animation: none; }
    }

    .pj-grid {
        display: grid;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .pj-grid--main {
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
            align-items: start;
        }
        .pj-grid--history {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
        }
        .pj-grid--history > .pj-panel:only-child {
            grid-column: 1 / -1;
        }
    }

    .pj-panel {
        border: 1px solid var(--glass-border);
        border-radius: 0.95rem;
        background:
            linear-gradient(160deg, rgba(59, 130, 246, 0.08), transparent 40%),
            rgba(13, 18, 30, 0.42);
        padding: 1rem 1.1rem 1.15rem;
        height: 100%;
    }
    .pj-panel__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .pj-panel__title {
        margin: 0;
        font-size: 0.72rem;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--text-muted);
        font-family: 'JetBrains Mono', monospace;
    }
    .pj-panel__hint {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .pj-kv {
        display: grid;
        gap: 0.7rem 1rem;
    }
    @media (min-width: 576px) {
        .pj-kv { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .pj-kv__item--wide { grid-column: 1 / -1; }
    .pj-kv__label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.62rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.2rem;
    }
    .pj-kv__value {
        font-size: 0.95rem;
        color: var(--text-main);
        font-weight: 550;
        line-height: 1.35;
    }
    .pj-kv__value a { color: inherit; text-decoration: none; border-bottom: 1px solid rgba(168,85,247,.35); }
    .pj-kv__value a:hover { color: #fff; border-bottom-color: var(--accent); }
    .pj-kv__sub { margin-top: 0.15rem; font-size: 0.8rem; color: var(--text-muted); font-weight: 400; }
    .pj-prose {
        white-space: pre-wrap;
        font-weight: 400;
        color: var(--text-main);
        line-height: 1.5;
    }
    .pj-prose--muted { color: var(--text-muted); }

    .pj-ustalenia {
        margin-top: 1rem;
        padding: 0.85rem 0.95rem;
        border-radius: 0.75rem;
        border: 1px dashed rgba(168, 85, 247, 0.35);
        background: rgba(168, 85, 247, 0.06);
    }
    .pj-ustalenia__label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.62rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #d8b4fe;
        margin-bottom: 0.4rem;
    }

    /* Milestones — denser checklist */
    .pm-list { display: flex; flex-direction: column; gap: 0.4rem; }
    .pm-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.7rem;
        border: 1px solid var(--glass-border);
        border-radius: 0.65rem;
        background: rgba(7, 10, 19, 0.35);
        transition: border-color .15s ease, background .15s ease;
    }
    .pm-item:hover { border-color: rgba(168, 85, 247, 0.35); }
    .pm-check {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin: 0;
        min-width: 0;
        cursor: pointer;
    }
    .pm-check .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
        margin: 0;
        border-radius: 0.3rem;
        border-color: rgba(168, 85, 247, 0.55);
        background-color: transparent;
        cursor: pointer;
    }
    .pm-check .form-check-input:checked {
        background-color: var(--accent);
        border-color: var(--accent);
    }
    .pm-title { font-size: 0.9rem; color: var(--text-main); }
    .pm-title--done {
        text-decoration: line-through;
        color: var(--text-muted);
    }
    .pm-empty {
        padding: 1.25rem 0.5rem;
        text-align: center;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .pm-empty i { display: block; font-size: 1.4rem; margin-bottom: 0.35rem; opacity: .7; }

    /* Demand axis polish */
    .pda__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem 1rem;
        margin-bottom: 0.85rem;
    }
    .pda__tracks { display: flex; flex-direction: column; gap: 0.7rem; }
    .pda__track {
        display: grid;
        grid-template-columns: minmax(7.5rem, 10.5rem) 1fr;
        gap: 0.75rem;
        align-items: center;
    }
    .pda__label { min-width: 0; }
    .pda__role {
        font-size: 0.86rem;
        font-weight: 650;
        color: var(--text-main);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pda__total {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-family: 'JetBrains Mono', monospace;
    }
    .pda__lane {
        position: relative;
        height: 2.15rem;
        border-radius: 0.6rem;
        background:
            repeating-linear-gradient(
                90deg,
                rgba(255,255,255,0.045) 0,
                rgba(255,255,255,0.045) 1px,
                transparent 1px,
                transparent 12.5%
            ),
            linear-gradient(180deg, rgba(255,255,255,0.03), transparent),
            rgba(7, 10, 19, 0.55);
        border: 1px solid var(--glass-border);
        overflow: hidden;
    }
    .pda__bar {
        position: absolute;
        top: 0.3rem;
        bottom: 0.3rem;
        border-radius: 0.45rem;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.92), rgba(168, 85, 247, 0.82));
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        box-shadow:
            0 0 0 1px rgba(255,255,255,0.1) inset,
            0 6px 16px rgba(59, 130, 246, 0.22);
    }
    .pda__bar-count {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.74rem;
        font-weight: 700;
        color: #fff;
        line-height: 1;
        text-shadow: 0 1px 2px rgba(0,0,0,.35);
    }
    .pda-empty {
        padding: 1.5rem 1rem;
        border-radius: 0.75rem;
        border: 1px dashed rgba(255,255,255,0.12);
        text-align: center;
        color: var(--text-muted);
        background:
            repeating-linear-gradient(
                90deg,
                rgba(255,255,255,0.03) 0,
                rgba(255,255,255,0.03) 1px,
                transparent 1px,
                transparent 12.5%
            ),
            rgba(7, 10, 19, 0.35);
    }
    .pda-empty__ghost {
        display: grid;
        gap: 0.45rem;
        max-width: 28rem;
        margin: 0 auto 0.85rem;
        opacity: 0.45;
    }
    .pda-empty__row {
        display: grid;
        grid-template-columns: 5rem 1fr;
        gap: 0.5rem;
        align-items: center;
    }
    .pda-empty__lab {
        height: 0.45rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.12);
    }
    .pda-empty__lane {
        height: 1.1rem;
        border-radius: 0.4rem;
        background: rgba(255,255,255,0.05);
        position: relative;
        overflow: hidden;
    }
    .pda-empty__bar {
        position: absolute;
        top: 0.2rem;
        bottom: 0.2rem;
        left: 18%;
        width: 42%;
        border-radius: 0.3rem;
        background: linear-gradient(135deg, rgba(59,130,246,.45), rgba(168,85,247,.4));
    }

    .pj-rates {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin-bottom: 0.85rem;
    }
    .pj-rate {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.45rem 0.55rem;
        border-radius: 0.5rem;
        background: rgba(7, 10, 19, 0.3);
        border: 1px solid rgba(255,255,255,0.05);
        font-size: 0.85rem;
    }
    .pj-rate__amt { font-weight: 650; font-variant-numeric: tabular-nums; }
    .pj-rate__when { color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; }
    .pj-rate--open {
        border-color: rgba(59, 130, 246, 0.35);
        background: rgba(59, 130, 246, 0.1);
    }
    .pj-history-table { font-size: 0.85rem; }
    .pj-history-table th {
        color: var(--text-muted);
        font-size: 0.72rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 650;
        border-bottom-color: rgba(255,255,255,0.08);
    }

    .pj-side-stack { display: flex; flex-direction: column; gap: 1rem; }

    @media (max-width: 767.98px) {
        .pj-hero__stats { grid-template-columns: 1fr; min-width: 0; width: 100%; }
        .pda__track { grid-template-columns: 1fr; gap: 0.3rem; }
    }
</style>
