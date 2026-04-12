@php
    use App\Support\AuditDiff;
@endphp

<style>
    .audit-diff-backdrop {
        position: fixed;
        inset: 0;
        z-index: 10050;
        background: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 1rem;
        overflow-y: auto;
    }
    .audit-diff-panel {
        width: min(960px, calc(100vw - 2rem));
        max-height: min(85vh, 900px);
        margin-top: 2rem;
        margin-bottom: 2rem;
        background: rgba(30, 41, 59, 0.98);
        border: 1px solid var(--glass-border, rgba(148, 163, 184, 0.25));
        border-radius: 12px;
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.55);
        color: var(--text-main, #e2e8f0);
        display: flex;
        flex-direction: column;
    }
    .audit-diff-panel__head {
        flex-shrink: 0;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--glass-border, rgba(148, 163, 184, 0.25));
    }
    .audit-diff-panel__body {
        overflow: auto;
        padding: 0 1rem 1rem;
    }
    .audit-diff-table {
        font-size: 0.8125rem;
        --diff-removed: rgba(248, 113, 113, 0.18);
        --diff-added: rgba(74, 222, 128, 0.16);
        --diff-changed-from: rgba(248, 113, 113, 0.1);
        --diff-changed-to: rgba(74, 222, 128, 0.1);
    }
    .audit-diff-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: rgba(15, 23, 42, 0.95);
        border-bottom: 1px solid var(--glass-border, rgba(148, 163, 184, 0.25)) !important;
        color: var(--text-muted, #94a3b8);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 0.06em;
    }
    .audit-diff-table td {
        vertical-align: top;
        word-break: break-word;
        border-color: rgba(148, 163, 184, 0.15) !important;
    }
    .audit-diff-table .audit-diff-val {
        white-space: pre-wrap;
    }
    .audit-diff-cell--before-rem {
        background: var(--diff-removed) !important;
        box-shadow: inset 3px 0 0 rgba(248, 113, 113, 0.65);
    }
    .audit-diff-cell--after-add {
        background: var(--diff-added) !important;
        box-shadow: inset -3px 0 0 rgba(74, 222, 128, 0.65);
    }
    .audit-diff-cell--before-chg {
        background: var(--diff-changed-from) !important;
        box-shadow: inset 3px 0 0 rgba(248, 113, 113, 0.45);
    }
    .audit-diff-cell--after-chg {
        background: var(--diff-changed-to) !important;
        box-shadow: inset -3px 0 0 rgba(74, 222, 128, 0.45);
    }
</style>

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Logi systemowe" />
    </x-slot>

    <div
        class="container-xxl"
        x-data="auditDiffPanel"
        @keydown.escape.window="openState && close()"
    >
        <p class="text-muted mb-4">
            Rejestr zmian w przypisaniach (projekt, pojazd, zakwaterowanie) oraz zdarzeniach logistycznych:
            kto, kiedy, typ operacji, stan przed i po.
        </p>

        <x-ui.card label="Filtry">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Typ rekordu</label>
                    <select name="auditable_type" class="form-select form-select-sm">
                        <option value="">— wszystkie —</option>
                        @foreach($modelTypes as $class)
                            <option value="{{ $class }}" @selected(request('auditable_type') === $class)>
                                {{ config('audit.model_labels')[$class] ?? class_basename($class) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Operacja</label>
                    <select name="event" class="form-select form-select-sm">
                        <option value="">— wszystkie —</option>
                        @foreach($eventTypes as $ev)
                            <option value="{{ $ev }}" @selected(request('event') === $ev)>
                                {{ config('audit.event_labels')[$ev] ?? $ev }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Użytkownik</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">— wszyscy —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Od</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Do</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-1">
                    <x-ui.button type="submit" variant="primary" class="w-100">Szukaj</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card label="Wpisy" class="mt-4">
            @if($logs->isEmpty())
                <x-ui.empty-state icon="journal-text" message="Brak wpisów dla wybranych filtrów." />
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Czas</th>
                                <th>Użytkownik</th>
                                <th>Typ</th>
                                <th>ID</th>
                                <th>Operacja</th>
                                <th style="min-width: 140px;">Zmiany</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                @php
                                    $auditDiffPayload = [
                                        'meta' => [
                                            'time' => $log->created_at->format('Y-m-d H:i:s'),
                                            'user' => $log->user?->name,
                                            'model' => config('audit.model_labels')[$log->auditable_type] ?? class_basename($log->auditable_type),
                                            'id' => $log->auditable_id,
                                            'event' => $log->event,
                                            'eventLabel' => config('audit.event_labels')[$log->event] ?? $log->event,
                                        ],
                                        'rows' => AuditDiff::rows($log->old_values, $log->new_values, $log->event),
                                    ];
                                @endphp
                                <tr>
                                    <td class="text-nowrap small">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="small">{{ $log->user?->name ?? '—' }}</td>
                                    <td class="small">
                                        {{ config('audit.model_labels')[$log->auditable_type] ?? class_basename($log->auditable_type) }}
                                    </td>
                                    <td class="text-muted small">{{ $log->auditable_id }}</td>
                                    <td>
                                        <x-ui.badge variant="{{ $log->event === 'deleted' ? 'danger' : ($log->event === 'created' ? 'success' : 'primary') }}">
                                            {{ config('audit.event_labels')[$log->event] ?? $log->event }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="small">
                                        @if($log->old_values || $log->new_values)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="showDiff(@js($auditDiffPayload))"
                                            >
                                                <i class="bi bi-layout-sidebar-inset-reverse me-1" aria-hidden="true"></i>
                                                Pokaż zmiany
                                            </button>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <x-ui.pagination :paginator="$logs" />
                </div>
            @endif
        </x-ui.card>

        <template x-teleport="body">
            <div
                x-show="openState"
                x-cloak
                class="audit-diff-backdrop"
                x-transition.opacity
                @click.self="close()"
            >
                <div class="audit-diff-panel" @click.stop>
                    <div class="audit-diff-panel__head">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h2 class="h6 fw-semibold mb-2 d-flex align-items-center gap-2">
                                    <i class="bi bi-code-diff" aria-hidden="true"></i>
                                    Podgląd zmian
                                </h2>
                                <template x-if="payload && payload.meta">
                                    <div class="small text-muted" style="line-height: 1.6;">
                                        <div><span class="text-secondary">Czas:</span> <span x-text="payload.meta.time"></span></div>
                                        <div><span class="text-secondary">Użytkownik:</span> <span x-text="payload.meta.user || '—'"></span></div>
                                        <div><span class="text-secondary">Rekord:</span> <span x-text="payload.meta.model"></span> · ID <span x-text="payload.meta.id"></span></div>
                                        <div><span class="text-secondary">Operacja:</span> <span x-text="payload.meta.eventLabel"></span></div>
                                    </div>
                                </template>
                            </div>
                            <button type="button" class="btn-close btn-close-white" style="filter: invert(1); opacity: 0.7;" aria-label="Zamknij" @click="close()"></button>
                        </div>
                    </div>
                    <div class="audit-diff-panel__body">
                        <template x-if="payload && payload.rows && payload.rows.length">
                            <div class="table-responsive border rounded-2" style="border-color: var(--glass-border, rgba(148, 163, 184, 0.25)) !important;">
                                <table class="table table-sm table-borderless mb-0 audit-diff-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-3 py-2" style="width: 20%;">Pole</th>
                                            <th class="py-2" style="width: 40%;">Przed</th>
                                            <th class="pe-3 py-2" style="width: 40%;">Po</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, idx) in payload.rows" :key="idx">
                                            <tr>
                                                <td class="ps-3 small text-muted fw-semibold" x-text="row.label"></td>
                                                <td
                                                    class="small audit-diff-val"
                                                    :class="{
                                                        'audit-diff-cell--before-rem': row.kind === 'removed',
                                                        'audit-diff-cell--before-chg': row.kind === 'changed',
                                                    }"
                                                    x-text="row.before"
                                                ></td>
                                                <td
                                                    class="pe-3 small audit-diff-val"
                                                    :class="{
                                                        'audit-diff-cell--after-add': row.kind === 'added',
                                                        'audit-diff-cell--after-chg': row.kind === 'changed',
                                                    }"
                                                    x-text="row.after"
                                                ></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template x-if="payload && (!payload.rows || !payload.rows.length)">
                            <p class="text-muted small mb-0">Brak szczegółowych różnic do wyświetlenia.</p>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
