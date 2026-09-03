<style>
    /* Mobile-first: bez 100vw (unikamy poziomego scrolla całej strony), tabela przewijana wewnątrz karty */
    .time-logs-monthly-grid-root {
        width: 100%;
        max-width: 100%;
        margin-left: 0;
        margin-right: 0;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        box-sizing: border-box;
    }
    .time-logs-monthly-grid-root .monthly-grid-table-card {
        width: 100%;
        max-width: 100%;
    }
    .time-logs-monthly-grid-root .monthly-grid-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        scrollbar-gutter: stable;
    }
    .time-logs-monthly-grid-root #timeLogsGrid {
        border-collapse: collapse;
        table-layout: auto;
        width: max-content;
        min-width: 100%;
    }
    .time-logs-monthly-grid-root #timeLogsGrid colgroup col {
        width: auto;
    }
    .time-logs-monthly-grid-root #timeLogsGrid th:first-child,
    .time-logs-monthly-grid-root #timeLogsGrid td:first-child {
        width: min(38vw, 152px);
        min-width: min(38vw, 152px);
        max-width: min(38vw, 152px);
        box-sizing: border-box;
        overflow: hidden;
        font-size: 0.8rem;
    }
    .time-logs-monthly-grid-root #timeLogsGrid thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 12;
        background: var(--bg-card) !important;
        box-shadow: 6px 0 12px -6px rgba(0, 0, 0, 0.45);
    }
    .time-logs-monthly-grid-root #timeLogsGrid tbody td:first-child {
        position: sticky;
        left: 0;
        z-index: 6;
        background: var(--bg-card) !important;
        box-shadow: 6px 0 12px -6px rgba(0, 0, 0, 0.35);
    }
    .time-logs-monthly-grid-root #timeLogsGrid .project-header-row td:first-child {
        background: rgba(59, 130, 246, 0.1) !important;
        z-index: 7;
        word-break: break-word;
    }
    .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child),
    .time-logs-monthly-grid-root #timeLogsGrid tbody td:not(:first-child) {
        min-width: 46px;
        width: 46px;
        max-width: 46px;
        padding: 2px 3px !important;
        vertical-align: middle;
    }
    .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child) {
        font-size: 0.65rem;
        line-height: 1.15;
        padding-top: 5px !important;
        padding-bottom: 5px !important;
    }
    .time-logs-monthly-grid-root #timeLogsGrid .monthly-grid-day-dow {
        font-size: 0.55rem;
        opacity: 0.85;
    }
    .time-logs-monthly-grid-root #timeLogsGrid input.time-input,
    .time-logs-monthly-grid-root #timeLogsGrid input.disabled-input {
        padding: 4px 2px !important;
        font-size: 0.8rem;
        min-width: 0 !important;
        min-height: 40px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .time-logs-monthly-grid-root #timeLogsGrid tbody td.ps-4 {
        padding-left: 0.5rem !important;
    }
    .time-logs-monthly-grid-root .monthly-grid-project-select {
        max-width: 100%;
    }

    @media (min-width: 768px) {
        .time-logs-monthly-grid-root:not(.time-logs-monthly-grid-root--contained) {
            width: 100vw;
            max-width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .time-logs-monthly-grid-root .monthly-grid-table-card {
            max-width: none;
        }
        .time-logs-monthly-grid-root #timeLogsGrid {
            table-layout: fixed;
            width: 100%;
            min-width: 0;
        }
        .time-logs-monthly-grid-root #timeLogsGrid colgroup col:first-child {
            width: 260px;
        }
        .time-logs-monthly-grid-root #timeLogsGrid th:first-child,
        .time-logs-monthly-grid-root #timeLogsGrid td:first-child {
            width: 260px;
            min-width: 260px;
            max-width: 260px;
            font-size: inherit;
        }
        .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child),
        .time-logs-monthly-grid-root #timeLogsGrid tbody td:not(:first-child) {
            min-width: 0;
            width: auto;
            max-width: none;
            padding: 3px 4px !important;
        }
        .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child) {
            font-size: 0.7rem;
            line-height: 1.2;
            padding-top: 6px !important;
            padding-bottom: 6px !important;
        }
        .time-logs-monthly-grid-root #timeLogsGrid .monthly-grid-day-dow {
            font-size: 0.6rem;
        }
        .time-logs-monthly-grid-root #timeLogsGrid input.time-input,
        .time-logs-monthly-grid-root #timeLogsGrid input.disabled-input {
            padding: 3px 4px !important;
            font-size: 0.75rem;
            min-height: 0;
        }
        .time-logs-monthly-grid-root #timeLogsGrid tbody td.ps-4 {
            padding-left: 1.5rem !important;
        }
        .time-logs-monthly-grid-root .monthly-grid-project-select {
            min-width: 280px;
            max-width: min(420px, 100%);
        }
    }
</style>
