<style>
    .crm-hero {
        display: grid;
        gap: 0.75rem;
        margin-bottom: 1.1rem;
    }
    @media (min-width: 768px) {
        .crm-hero {
            grid-template-columns: 1.35fr 1fr;
        }
        .crm-hero--four {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 992px) {
        .crm-hero {
            grid-template-columns: 1.35fr 1fr 1fr;
        }
        .crm-hero--four {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    .crm-hero__value-muted {
        font-size: 0.85em;
        font-weight: 500;
        color: var(--text-muted);
    }
    .crm-company {
        margin-bottom: 0.85rem;
        border: 1px solid var(--glass-border);
        border-radius: 0.85rem;
        background: rgba(13, 18, 30, 0.35);
        padding: 0.15rem 0.85rem 0.85rem;
    }
    .crm-company__toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        padding: 0.75rem 0.15rem;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
    }
    .crm-company__toggle:hover .crm-company__toggle-label {
        color: #fff;
    }
    .crm-company__toggle-main {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .crm-company__toggle-label {
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--text-muted);
        font-family: 'JetBrains Mono', monospace;
    }
    .crm-company__toggle-summary {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .crm-company__chevron {
        flex-shrink: 0;
        color: var(--text-muted);
        transition: transform .18s ease;
    }
    .crm-company__chevron.is-open {
        transform: rotate(180deg);
    }
    .crm-company__body {
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        padding-bottom: 0.25rem;
    }
    .crm-hero__panel {
        padding: 0.95rem 1.05rem;
        border: 1px solid var(--glass-border);
        border-radius: 0.85rem;
        background:
            linear-gradient(135deg, rgba(59, 130, 246, 0.12), transparent 42%),
            rgba(13, 18, 30, 0.42);
        min-height: 6.25rem;
        text-align: left;
        color: inherit;
        width: 100%;
        transition: border-color .15s ease, transform .15s ease;
    }
    button.crm-hero__panel {
        cursor: pointer;
    }
    button.crm-hero__panel:hover {
        border-color: rgba(168, 85, 247, 0.45);
        transform: translateY(-1px);
    }
    .crm-hero__panel--alert {
        border-color: rgba(245, 158, 11, 0.45);
        background:
            linear-gradient(135deg, rgba(245, 158, 11, 0.14), transparent 50%),
            rgba(13, 18, 30, 0.42);
    }
    .crm-hero__panel--danger {
        border-color: rgba(239, 68, 68, 0.4);
        background:
            linear-gradient(135deg, rgba(239, 68, 68, 0.12), transparent 50%),
            rgba(13, 18, 30, 0.42);
    }
    .crm-hero__label {
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.3rem;
        font-family: 'JetBrains Mono', monospace;
    }
    .crm-hero__value {
        font-size: 1.05rem;
        font-weight: 600;
        color: var(--text-main);
        line-height: 1.3;
    }
    .crm-hero__meta {
        margin-top: 0.3rem;
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .crm-followups {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        margin-bottom: 1rem;
    }
    .crm-followup {
        display: grid;
        gap: 0.65rem;
        padding: 0.85rem 0.95rem;
        border: 1px solid rgba(245, 158, 11, 0.35);
        border-radius: 0.8rem;
        background: rgba(245, 158, 11, 0.06);
    }
    @media (min-width: 768px) {
        .crm-followup {
            grid-template-columns: 1fr auto;
            align-items: center;
        }
    }
    .crm-followup--overdue {
        border-color: rgba(239, 68, 68, 0.45);
        background: rgba(239, 68, 68, 0.07);
    }
    .crm-followup__when {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.82rem;
    }
    .crm-followup__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .crm-scope {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .crm-scope__btn {
        border: 1px solid var(--glass-border);
        background: rgba(255,255,255,0.03);
        color: var(--text-muted);
        border-radius: 999px;
        padding: 0.28rem 0.75rem;
        font-size: 0.78rem;
        line-height: 1.2;
    }
    .crm-scope__btn.is-active {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, var(--primary), var(--accent));
    }
    .crm-contact-grid {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: 1fr;
    }
    @media (min-width: 768px) {
        .crm-contact-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .crm-contact {
        position: relative;
        padding: 1rem;
        border: 1px solid var(--glass-border);
        border-radius: 0.85rem;
        background: rgba(13, 18, 30, 0.35);
    }
    .crm-contact--primary {
        border-color: rgba(59, 130, 246, 0.45);
        box-shadow: inset 0 0 0 1px rgba(168, 85, 247, 0.12);
    }
    .crm-contact__name {
        font-weight: 600;
        font-size: 1.02rem;
    }
    .crm-contact__role {
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    .crm-contact__row {
        display: flex;
        gap: 0.45rem;
        align-items: center;
        margin-top: 0.4rem;
        font-size: 0.9rem;
        color: var(--text-muted);
    }
    .crm-contact__row i {
        width: 1rem;
        text-align: center;
        opacity: 0.8;
    }
    .crm-timeline {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .crm-timeline__item {
        position: relative;
        padding: 0.9rem 1rem 0.9rem 1.1rem;
        border: 1px solid var(--glass-border);
        border-radius: 0.85rem;
        background: rgba(13, 18, 30, 0.32);
        border-left: 3px solid rgba(59, 130, 246, 0.65);
    }
    .crm-timeline__item--agreed { border-left-color: rgba(34, 197, 94, 0.75); }
    .crm-timeline__item--warning { border-left-color: rgba(245, 158, 11, 0.8); }
    .crm-timeline__item--danger { border-left-color: rgba(239, 68, 68, 0.75); }
    .crm-timeline__item--draft {
        border-style: dashed;
        border-color: rgba(168, 85, 247, 0.45);
        border-left: 3px solid rgba(168, 85, 247, 0.75);
        background: rgba(168, 85, 247, 0.06);
    }
    .crm-timeline__item.is-expanded {
        border-color: rgba(59, 130, 246, 0.4);
        box-shadow: inset 0 0 0 1px rgba(168, 85, 247, 0.08);
    }
    .crm-timeline__summary {
        display: block;
        width: 100%;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }
    .crm-timeline__summary:disabled {
        cursor: default;
    }
    .crm-timeline__summary:not(:disabled):hover .fw-semibold {
        color: #fff;
    }
    .crm-chip--draft {
        border-color: rgba(168, 85, 247, 0.45);
        color: #e9d5ff;
        background: rgba(168, 85, 247, 0.16);
    }
    .crm-timeline__head {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem 0.75rem;
        align-items: center;
        justify-content: space-between;
    }
    .crm-timeline__when {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.82rem;
        color: var(--text-muted);
    }
    .crm-timeline__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.5rem;
    }
    .crm-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        border: 1px solid var(--glass-border);
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.03);
    }
    .crm-chip--due {
        border-color: rgba(245, 158, 11, 0.45);
        color: #fde68a;
        background: rgba(245, 158, 11, 0.12);
    }
    .crm-chip--overdue {
        border-color: rgba(239, 68, 68, 0.45);
        color: #fecaca;
        background: rgba(239, 68, 68, 0.12);
    }
    .crm-form-panel {
        padding: 1rem;
        border: 1px dashed rgba(168, 85, 247, 0.35);
        border-radius: 0.85rem;
        background: rgba(168, 85, 247, 0.05);
        margin-bottom: 1rem;
    }
    .crm-attention {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.72rem;
        color: #fde68a;
    }
    .crm-company__grid {
        display: grid;
        gap: 0.75rem 1rem;
        grid-template-columns: 1fr;
    }
    @media (min-width: 768px) {
        .crm-company__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1200px) {
        .crm-company__grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    .crm-company__field--wide {
        grid-column: 1 / -1;
    }
    .crm-inline-label {
        display: block;
        font-size: 0.7rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
        font-family: 'JetBrains Mono', monospace;
    }
    .crm-inline.form-control,
    .crm-inline.form-select {
        background: transparent !important;
        border-color: transparent !important;
        box-shadow: none !important;
        padding-left: 0.35rem !important;
        padding-right: 0.35rem !important;
        color: var(--text-main) !important;
        min-height: 2.1rem;
    }
    .crm-inline.form-control:hover,
    .crm-inline.form-select:hover {
        border-color: var(--glass-border) !important;
    }
    .crm-inline.form-control:focus,
    .crm-inline.form-select:focus {
        border-color: rgba(168, 85, 247, 0.55) !important;
        background: rgba(13, 18, 30, 0.55) !important;
    }
    .crm-inline.is-invalid {
        border-color: #ef4444 !important;
    }
    .crm-inline--title {
        font-size: 1.15rem !important;
        font-weight: 600 !important;
    }
    .crm-inline--muted {
        color: var(--text-muted) !important;
        -webkit-text-fill-color: var(--text-muted) !important;
    }
    .crm-inline--muted:focus {
        color: var(--text-main) !important;
        -webkit-text-fill-color: var(--text-main) !important;
    }
    .crm-inline-error {
        display: block;
        color: #f87171;
        font-size: 0.72rem;
        margin-top: 0.15rem;
    }
    .crm-inline-readonly {
        min-height: 2.1rem;
        display: flex;
        align-items: center;
        padding: 0.25rem 0.35rem;
        color: var(--text-main);
    }
    .crm-timeline__inline-row {
        display: grid;
        gap: 0.5rem;
        margin-top: 0.55rem;
        grid-template-columns: 1fr;
    }
    @media (min-width: 768px) {
        .crm-timeline__inline-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1100px) {
        .crm-timeline__inline-row {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
</style>
