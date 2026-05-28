@php
    $defaultStart = now()->startOfMonth()->toDateString();
    $defaultEnd = now()->toDateString();

    $assignmentEmployees = \App\Models\Employee::orderBy('last_name')->orderBy('first_name')
        ->get(['id', 'first_name', 'last_name'])
        ->map(fn ($e) => ['id' => $e->id, 'full_name' => $e->full_name])
        ->values()
        ->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Prompt engine" />
    </x-slot>

    <p class="text-muted small mb-4">
        Eksport surowych danych (JSON) do analizy zewnętrznym modelem — zadania, komentarze, podzadania i wzmianki <code>@</code> w treści.
    </p>

    <div
        id="prompt-engine-page"
        class="prompt-engine-page"
        data-export-tasks-url="{{ route('prompts.export.tasks') }}"
        data-export-assignments-url="{{ route('prompts.export.assignments') }}"
        data-default-start="{{ $defaultStart }}"
        data-default-end="{{ $defaultEnd }}"
    >
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
            <div class="col d-flex">
                <x-ui.card variant="hover" class="w-100 d-flex flex-column">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <span class="rounded-2 p-2 bg-primary bg-opacity-10 text-primary"><i class="bi bi-braces fs-5"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold">Zadania (raw JSON)</div>
                            <div class="text-muted small">Komentarze, @wzmianki, podzadania, status — dla zakresu dat.</div>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <x-ui.button
                            variant="primary"
                            class="w-100"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#promptEngineTasksModal"
                        >
                            Wybierz zakres i pobierz
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <div class="col d-flex">
                <x-ui.card variant="hover" class="w-100 d-flex flex-column">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <span class="rounded-2 p-2 bg-success bg-opacity-10 text-success"><i class="bi bi-people fs-5"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold">Przypisania pracowników</div>
                            <div class="text-muted small">Projekty, auta, domy, rotacje, wyjazdy i zjazdy — dla wybranych osób i okresu.</div>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <x-ui.button
                            variant="success"
                            class="w-100"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#promptEngineAssignmentsModal"
                        >
                            Wybierz osoby i pobierz
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>

            <div class="col d-flex">
                <x-ui.card class="w-100 d-flex flex-column opacity-50">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <span class="rounded-2 p-2 bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-hourglass-split fs-5"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold">Więcej eksportów</div>
                            <div class="text-muted small">Kolejne kafelki prompt engine — w przygotowaniu.</div>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <x-ui.badge variant="info">Wkrótce</x-ui.badge>
                    </div>
                </x-ui.card>
            </div>

            <div class="col d-flex">
                <x-ui.card class="w-100 d-flex flex-column opacity-50">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <span class="rounded-2 p-2 bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-hourglass-split fs-5"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold">Więcej eksportów</div>
                            <div class="text-muted small">Kolejne kafelki prompt engine — w przygotowaniu.</div>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <x-ui.badge variant="info">Wkrótce</x-ui.badge>
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- Assignments modal --}}
        <div
            class="modal fade departure-planner-teleport-modal"
            id="promptEngineAssignmentsModal"
            tabindex="-1"
            aria-labelledby="promptEngineAssignmentsModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: var(--bs-body-color, #e2e8f0);">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="promptEngineAssignmentsModalLabel">
                            <i class="bi bi-people me-2 text-success"></i>Eksport przypisań (JSON)
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        <div id="prompt-engine-assignments-error" class="alert alert-danger py-2 small d-none" role="alert"></div>

                        {{-- Date range --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <x-ui.input type="date" name="pea_start_date" id="pea_start_date" label="Data od" :value="$defaultStart" required />
                            </div>
                            <div class="col-md-6">
                                <x-ui.input type="date" name="pea_end_date" id="pea_end_date" label="Data do" :value="$defaultEnd" required />
                            </div>
                        </div>

                        {{-- Employee picker --}}
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Pracownicy</label>
                            <div class="d-flex gap-2 mb-2">
                                <input
                                    id="pea-emp-search"
                                    type="search"
                                    class="form-control form-control-sm"
                                    placeholder="Szukaj pracownika…"
                                    autocomplete="off"
                                >
                                <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="pea-select-all">Zaznacz wszystkich</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="pea-deselect-all">Odznacz</button>
                            </div>
                            <div
                                id="pea-emp-list"
                                class="border rounded-2 p-2"
                                style="max-height:200px;overflow-y:auto;background:rgba(255,255,255,0.04);"
                            >
                                @foreach($assignmentEmployees as $emp)
                                    <div
                                        class="pea-emp-row d-flex align-items-center gap-2 px-2 py-1 rounded-2"
                                        data-name="{{ mb_strtolower($emp['full_name']) }}"
                                        style="cursor:pointer;"
                                        onclick="this.querySelector('input').click()"
                                    >
                                        <input
                                            type="checkbox"
                                            class="form-check-input pea-emp-cb flex-shrink-0"
                                            value="{{ $emp['id'] }}"
                                            style="pointer-events:none;"
                                        >
                                        <span class="small">{{ $emp['full_name'] }}</span>
                                    </div>
                                @endforeach
                                @if(empty($assignmentEmployees))
                                    <p class="small text-muted mb-0 px-2">Brak pracowników w systemie.</p>
                                @endif
                            </div>
                            <div class="mt-1">
                                <span id="pea-selected-count" class="small text-muted">Wszyscy pracownicy (brak filtra)</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <x-ui.button variant="success" type="button" id="pea-fetch">Generuj JSON</x-ui.button>
                            <x-ui.button variant="ghost" type="button" id="pea-copy" disabled>Skopiuj do schowka</x-ui.button>
                        </div>
                        <div id="pea-loading" class="text-muted small d-none mb-2">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Ładowanie…
                        </div>
                        <label class="form-label small text-muted" for="pea-json">Wynik</label>
                        <textarea
                            id="pea-json"
                            class="form-control font-monospace small"
                            rows="12"
                            readonly
                            placeholder="Kliknij „Generuj JSON"…"
                            style="min-height:240px;"
                        ></textarea>
                    </div>
                    <div class="modal-footer border-secondary">
                        <x-ui.button variant="ghost" type="button" data-bs-dismiss="modal">Zamknij</x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tasks modal musi być podpięty pod body: .app-content-wrapper ma backdrop-filter i psuje fixed + klikalność. --}}
        <div
            class="modal fade departure-planner-teleport-modal"
            id="promptEngineTasksModal"
            tabindex="-1"
            aria-labelledby="promptEngineTasksModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: var(--bs-body-color, #e2e8f0);">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="promptEngineTasksModalLabel">Eksport zadań (JSON)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        <div id="prompt-engine-tasks-error" class="alert alert-danger py-2 small d-none" role="alert"></div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <x-ui.input type="date" name="pe_start_date" id="pe_start_date" label="Data od" :value="$defaultStart" required />
                            </div>
                            <div class="col-md-6">
                                <x-ui.input type="date" name="pe_end_date" id="pe_end_date" label="Data do" :value="$defaultEnd" required />
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <x-ui.button variant="primary" type="button" id="pe-tasks-fetch">Generuj JSON</x-ui.button>
                            <x-ui.button variant="ghost" type="button" id="pe-tasks-copy" disabled>Skopiuj do schowka</x-ui.button>
                            <button type="button" class="btn btn-outline-info" id="pe-tasks-analyze" disabled>
                                <i class="bi bi-bar-chart-line me-1"></i>Zobacz wykresy
                            </button>
                        </div>
                        <div id="pe-tasks-loading" class="text-muted small d-none mb-2">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Ładowanie…
                        </div>
                        <label class="form-label small text-muted" for="pe-tasks-json">Wynik</label>
                        <textarea
                            id="pe-tasks-json"
                            class="form-control font-monospace small"
                            rows="14"
                            readonly
                            placeholder="Kliknij „Generuj JSON”…"
                            style="min-height: 280px;"
                        ></textarea>
                    </div>
                    <div class="modal-footer border-secondary">
                        <x-ui.button variant="ghost" type="button" data-bs-dismiss="modal">Zamknij</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tasks analytics modal --}}
    <div
        class="modal fade departure-planner-teleport-modal"
        id="promptEngineTasksAnalyticsModal"
        tabindex="-1"
        aria-labelledby="pe-analytics-title"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: var(--bs-body-color, #e2e8f0);">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="pe-analytics-title">
                        <i class="bi bi-bar-chart-line me-2 text-info"></i>Analytics — Eksport zadań
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body" id="pe-analytics-body" style="padding: 1.25rem;">
                    <div class="text-center text-muted py-5">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Ładowanie…
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Zamknij</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // ── Assignments modal ────────────────────────────────────────────────────
            (function () {
                const root = document.getElementById('prompt-engine-page');
                if (!root) return;

                const modalEl = document.getElementById('promptEngineAssignmentsModal');
                if (modalEl && modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                const exportUrl = root.dataset.exportAssignmentsUrl;
                const defaultStart = root.dataset.defaultStart;
                const defaultEnd = root.dataset.defaultEnd;

                const startInput = document.getElementById('pea_start_date');
                const endInput = document.getElementById('pea_end_date');
                const searchInput = document.getElementById('pea-emp-search');
                const empList = document.getElementById('pea-emp-list');
                const selectAllBtn = document.getElementById('pea-select-all');
                const deselectAllBtn = document.getElementById('pea-deselect-all');
                const selectedCountEl = document.getElementById('pea-selected-count');
                const fetchBtn = document.getElementById('pea-fetch');
                const copyBtn = document.getElementById('pea-copy');
                const textarea = document.getElementById('pea-json');
                const loadingEl = document.getElementById('pea-loading');
                const errorEl = document.getElementById('prompt-engine-assignments-error');

                function allRows() {
                    return Array.from(empList?.querySelectorAll('.pea-emp-row') ?? []);
                }

                function updateSelectedCount() {
                    const checked = empList?.querySelectorAll('.pea-emp-cb:checked').length ?? 0;
                    const total = empList?.querySelectorAll('.pea-emp-cb').length ?? 0;
                    if (!selectedCountEl) return;
                    if (checked === 0) {
                        selectedCountEl.textContent = 'Wszyscy pracownicy (brak filtra)';
                    } else {
                        selectedCountEl.textContent = `Wybrano: ${checked} z ${total}`;
                    }
                }

                function filterEmployees() {
                    const q = (searchInput?.value ?? '').toLowerCase().trim();
                    allRows().forEach((row) => {
                        const name = row.dataset.name ?? '';
                        row.style.display = (!q || name.includes(q)) ? '' : 'none';
                    });
                }

                searchInput?.addEventListener('input', filterEmployees);

                selectAllBtn?.addEventListener('click', () => {
                    allRows().forEach((row) => {
                        if (row.style.display !== 'none') {
                            const cb = row.querySelector('.pea-emp-cb');
                            if (cb) cb.checked = true;
                        }
                    });
                    updateSelectedCount();
                });

                deselectAllBtn?.addEventListener('click', () => {
                    empList?.querySelectorAll('.pea-emp-cb').forEach((cb) => { cb.checked = false; });
                    updateSelectedCount();
                });

                empList?.addEventListener('change', updateSelectedCount);

                modalEl?.addEventListener('show.bs.modal', () => {
                    if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
                    if (startInput && !startInput.value) startInput.value = defaultStart;
                    if (endInput && !endInput.value) endInput.value = defaultEnd;
                    updateSelectedCount();
                });

                modalEl?.addEventListener('hidden.bs.modal', () => {
                    loadingEl?.classList.add('d-none');
                });

                function showError(msg) {
                    if (!errorEl) return;
                    errorEl.textContent = msg || 'Wystąpił błąd.';
                    errorEl.classList.remove('d-none');
                }

                function clearError() {
                    if (!errorEl) return;
                    errorEl.textContent = '';
                    errorEl.classList.add('d-none');
                }

                fetchBtn?.addEventListener('click', async () => {
                    clearError();
                    const start = startInput?.value;
                    const end = endInput?.value;
                    if (!start || !end) { showError('Uzupełnij daty od i do.'); return; }

                    loadingEl?.classList.remove('d-none');
                    fetchBtn.disabled = true;
                    copyBtn.disabled = true;
                    textarea.value = '';

                    const url = new URL(exportUrl, window.location.origin);
                    url.searchParams.set('start_date', start);
                    url.searchParams.set('end_date', end);

                    const checkedIds = Array.from(empList?.querySelectorAll('.pea-emp-cb:checked') ?? [])
                        .map((cb) => cb.value);
                    checkedIds.forEach((id) => url.searchParams.append('employee_ids[]', id));

                    try {
                        const res = await fetch(url.toString(), {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const text = await res.text();
                        if (!res.ok) {
                            let detail = text;
                            try {
                                const j = JSON.parse(text);
                                if (j.errors && typeof j.errors === 'object') {
                                    detail = Object.values(j.errors).flat()[0] || j.message || text;
                                } else {
                                    detail = j.message || j.error || text;
                                }
                            } catch (_) {}
                            showError('Serwer zwrócił błąd (' + res.status + '): ' + detail);
                            return;
                        }
                        let parsed;
                        try { parsed = JSON.parse(text); } catch (e) {
                            showError('Odpowiedź nie jest poprawnym JSON.');
                            textarea.value = text;
                            return;
                        }
                        textarea.value = JSON.stringify(parsed, null, 2);
                        copyBtn.disabled = false;
                    } catch (e) {
                        showError(e?.message || 'Nie udało się pobrać danych.');
                    } finally {
                        loadingEl?.classList.add('d-none');
                        fetchBtn.disabled = false;
                    }
                });

                copyBtn?.addEventListener('click', async () => {
                    const t = textarea.value;
                    if (!t) return;
                    try {
                        await navigator.clipboard.writeText(t);
                    } catch (_) {
                        textarea.focus(); textarea.select();
                        try { document.execCommand('copy'); } catch (__) {}
                    }
                });
            })();

            // ── Tasks modal ──────────────────────────────────────────────────────────
            (function () {
                const root = document.getElementById('prompt-engine-page');
                if (!root) return;

                const modalEl = document.getElementById('promptEngineTasksModal');
                if (modalEl && modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                const analyticsModalEl = document.getElementById('promptEngineTasksAnalyticsModal');
                if (analyticsModalEl && analyticsModalEl.parentElement !== document.body) {
                    document.body.appendChild(analyticsModalEl);
                }

                const exportUrl = root.dataset.exportTasksUrl;
                const defaultStart = root.dataset.defaultStart;
                const defaultEnd = root.dataset.defaultEnd;
                const startInput = document.getElementById('pe_start_date');
                const endInput = document.getElementById('pe_end_date');
                const fetchBtn = document.getElementById('pe-tasks-fetch');
                const copyBtn = document.getElementById('pe-tasks-copy');
                const analyzeBtn = document.getElementById('pe-tasks-analyze');
                const textarea = document.getElementById('pe-tasks-json');
                const loadingEl = document.getElementById('pe-tasks-loading');
                const errorEl = document.getElementById('prompt-engine-tasks-error');

                function showError(msg) {
                    errorEl.textContent = msg || 'Wystąpił błąd.';
                    errorEl.classList.remove('d-none');
                }

                function clearError() {
                    errorEl.textContent = '';
                    errorEl.classList.add('d-none');
                }

                modalEl?.addEventListener('show.bs.modal', () => {
                    clearError();
                    if (startInput && !startInput.value) startInput.value = defaultStart;
                    if (endInput && !endInput.value) endInput.value = defaultEnd;
                });

                modalEl?.addEventListener('hidden.bs.modal', () => {
                    loadingEl?.classList.add('d-none');
                });

                fetchBtn?.addEventListener('click', async () => {
                    clearError();
                    const start = startInput?.value;
                    const end = endInput?.value;
                    if (!start || !end) {
                        showError('Uzupełnij daty od i do.');
                        return;
                    }
                    loadingEl?.classList.remove('d-none');
                    fetchBtn.disabled = true;
                    copyBtn.disabled = true;
                    textarea.value = '';

                    const url = new URL(exportUrl, window.location.origin);
                    url.searchParams.set('start_date', start);
                    url.searchParams.set('end_date', end);

                    try {
                        const res = await fetch(url.toString(), {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const text = await res.text();
                        if (!res.ok) {
                            let detail = text;
                            try {
                                const j = JSON.parse(text);
                                if (j.errors && typeof j.errors === 'object') {
                                    const first = Object.values(j.errors).flat()[0];
                                    detail = first || j.message || text;
                                } else {
                                    detail = j.message || j.error || text;
                                }
                            } catch (_) {}
                            showError('Serwer zwrócił błąd (' + res.status + '): ' + detail);
                            return;
                        }
                        let parsed;
                        try {
                            parsed = JSON.parse(text);
                        } catch (e) {
                            showError('Odpowiedź nie jest poprawnym JSON.');
                            textarea.value = text;
                            return;
                        }
                        textarea.value = JSON.stringify(parsed, null, 2);
                        copyBtn.disabled = false;
                        if (analyzeBtn) analyzeBtn.disabled = false;
                    } catch (e) {
                        showError(e?.message || 'Nie udało się pobrać danych.');
                    } finally {
                        loadingEl?.classList.add('d-none');
                        fetchBtn.disabled = false;
                    }
                });

                copyBtn?.addEventListener('click', async () => {
                    const t = textarea.value;
                    if (!t) return;
                    try {
                        await navigator.clipboard.writeText(t);
                    } catch (_) {
                        textarea.focus();
                        textarea.select();
                        try {
                            document.execCommand('copy');
                        } catch (__) {}
                    }
                });
            })();
            // ── Analytics modal ──────────────────────────────────────────────────────
            (function () {
                const analyzeBtn = document.getElementById('pe-tasks-analyze');
                const textarea = document.getElementById('pe-tasks-json');
                const analyticsModalEl = document.getElementById('promptEngineTasksAnalyticsModal');
                if (!analyzeBtn || !analyticsModalEl) return;

                let activeCharts = [];

                function destroyCharts() {
                    activeCharts.forEach(c => { try { c.destroy(); } catch (_) {} });
                    activeCharts = [];
                }

                function loadChartJs() {
                    return new Promise(resolve => {
                        if (window.Chart) { resolve(); return; }
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                        s.onload = resolve;
                        s.onerror = resolve;
                        document.head.appendChild(s);
                    });
                }

                const PALETTE = [
                    '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
                    '#06b6d4','#f97316','#84cc16','#ec4899','#14b8a6',
                    '#a855f7','#64748b',
                ];
                const STATUS_COLORS = {
                    completed: '#10b981', pending: '#8b5cf6',
                    in_progress: '#f59e0b', cancelled: '#ef4444',
                };
                const STATUS_LABELS = {
                    completed: 'Zakończone', pending: 'Oczekujące',
                    in_progress: 'W trakcie', cancelled: 'Anulowane',
                };

                function escHtml(s) {
                    if (!s) return '';
                    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                }

                function pct(val, total) {
                    return total ? Math.round(val / total * 100) + '%' : '';
                }

                function mkColor(i) { return PALETTE[i % PALETTE.length]; }

                function computeStats(json) {
                    const tasks = json.tasks || [];
                    const now = Date.now();

                    const byStatus = { completed: 0, pending: 0, in_progress: 0, cancelled: 0 };
                    tasks.forEach(t => { const s = t.status || 'pending'; if (s in byStatus) byStatus[s]++; });

                    const allComments = tasks.flatMap(t => t.comments || []);
                    const allSubtasks = tasks.flatMap(t => t.subtasks || []);

                    const byAssignee = {}, assigneeComments = {}, assigneeCompleted = {};
                    tasks.forEach(t => {
                        const n = t.assigned_to?.name || '—';
                        byAssignee[n] = (byAssignee[n] || 0) + 1;
                        if (t.status === 'completed') assigneeCompleted[n] = (assigneeCompleted[n] || 0) + 1;
                        assigneeComments[n] = (assigneeComments[n] || 0) + (t.comments || []).length;
                    });

                    const byCategory = {}, categoryCompleted = {};
                    tasks.forEach(t => {
                        const c = t.category || 'Bez kategorii';
                        byCategory[c] = (byCategory[c] || 0) + 1;
                        if (t.status === 'completed') categoryCompleted[c] = (categoryCompleted[c] || 0) + 1;
                    });

                    const topCommented = tasks
                        .map(t => ({ id: t.id, name: t.name, count: (t.comments || []).length }))
                        .filter(t => t.count > 0)
                        .sort((a, b) => b.count - a.count)
                        .slice(0, 8);

                    const stale = tasks
                        .filter(t => t.status !== 'completed' && t.status !== 'cancelled')
                        .map(t => {
                            const d = Math.floor((now - new Date(t.updated_at).getTime()) / 86400000);
                            return { ...t, daysSince: d };
                        })
                        .filter(t => t.daysSince >= 7)
                        .sort((a, b) => b.daysSince - a.daysSince)
                        .slice(0, 12);

                    // Day of week Mon-Sun
                    const weekdays = [0,0,0,0,0,0,0];
                    tasks.forEach(t => {
                        if (!t.created_at) return;
                        let d = new Date(t.created_at).getDay();
                        weekdays[d === 0 ? 6 : d - 1]++;
                    });

                    // Hour buckets 4h
                    const hourBuckets = [0,0,0,0,0,0];
                    const hourLabels = ['0–4','4–8','8–12','12–16','16–20','20–24'];
                    tasks.forEach(t => {
                        if (!t.created_at) return;
                        hourBuckets[Math.floor(new Date(t.created_at).getHours() / 4)]++;
                    });

                    const mentionsReceived = {};
                    allComments.forEach(c => {
                        (c.mentions || []).forEach(m => {
                            const n = m.resolved_user?.name;
                            if (n) mentionsReceived[n] = (mentionsReceived[n] || 0) + 1;
                        });
                    });

                    const commentsByAuthor = {};
                    allComments.forEach(c => {
                        const n = c.author?.name || '—';
                        commentsByAuthor[n] = (commentsByAuthor[n] || 0) + 1;
                    });

                    const lifetimeByPerson = {};
                    tasks.forEach(t => {
                        if (!t.completed_at || !t.created_at || !t.assigned_to?.name) return;
                        const days = (new Date(t.completed_at) - new Date(t.created_at)) / 86400000;
                        if (days < 0) return;
                        (lifetimeByPerson[t.assigned_to.name] = lifetimeByPerson[t.assigned_to.name] || []).push(days);
                    });
                    const lifetimeStats = Object.entries(lifetimeByPerson)
                        .map(([name, days]) => ({
                            name,
                            avg: Math.round(days.reduce((a, b) => a + b, 0) / days.length * 10) / 10,
                            max: Math.round(Math.max(...days) * 10) / 10,
                            count: days.length
                        }))
                        .sort((a, b) => a.avg - b.avg);

                    const editedByAuthor = {};
                    allComments.forEach(c => {
                        if (c.updated_at && c.created_at && c.updated_at !== c.created_at) {
                            const n = c.author?.name || '—';
                            editedByAuthor[n] = (editedByAuthor[n] || 0) + 1;
                        }
                    });

                    // Cross-comment matrix
                    const crossPeopleSet = new Set();
                    const crossData = {};
                    tasks.forEach(t => {
                        const owner = t.assigned_to?.name || '—';
                        crossPeopleSet.add(owner);
                        (t.comments || []).forEach(c => {
                            const author = c.author?.name || '—';
                            crossPeopleSet.add(author);
                            if (!crossData[author]) crossData[author] = {};
                            crossData[author][owner] = (crossData[author][owner] || 0) + 1;
                        });
                    });

                    // Delegation matrix
                    const delegData = {};
                    const delegPeopleSet = new Set();
                    tasks.forEach(t => {
                        const creator = t.created_by?.name || '—';
                        const assignee = t.assigned_to?.name || '—';
                        delegPeopleSet.add(creator);
                        delegPeopleSet.add(assignee);
                        if (!delegData[creator]) delegData[creator] = {};
                        delegData[creator][assignee] = (delegData[creator][assignee] || 0) + 1;
                    });

                    const crossPeople = [...crossPeopleSet].sort();
                    const delegPeople = [...delegPeopleSet].sort();

                    return {
                        tasks, total: tasks.length, byStatus,
                        totalComments: allComments.length, totalSubtasks: allSubtasks.length,
                        byAssignee, assigneeComments, assigneeCompleted,
                        byCategory, categoryCompleted,
                        topCommented, stale,
                        weekdays, hourBuckets, hourLabels,
                        mentionsReceived, commentsByAuthor,
                        lifetimeStats, editedByAuthor,
                        crossPeople, crossData,
                        delegPeople, delegData,
                    };
                }

                function mkChart(id, config) {
                    const el = document.getElementById(id);
                    if (!el || !window.Chart) return null;
                    const ch = new Chart(el.getContext('2d'), config);
                    activeCharts.push(ch);
                    return ch;
                }

                function renderAnalytics(json) {
                    destroyCharts();
                    const body = document.getElementById('pe-analytics-body');
                    if (!body) return;
                    const stats = computeStats(json);
                    const period = json.meta?.period;

                    const titleEl = document.getElementById('pe-analytics-title');
                    if (titleEl && period) {
                        titleEl.innerHTML = `<i class="bi bi-bar-chart-line me-2 text-info"></i>Analytics · ${escHtml(period.start_date)} — ${escHtml(period.end_date)} · ${stats.total} zadań`;
                    }

                    body.innerHTML = '';

                    if (window.Chart) {
                        Chart.defaults.color = 'rgba(255,255,255,0.45)';
                        Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
                        Chart.defaults.font.family = "'Inter', sans-serif";
                        Chart.defaults.font.size = 11;
                    }

                    const card = (inner, cls = '') =>
                        `<div class="rounded-2 p-3 ${cls}" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1)">${inner}</div>`;

                    const sectionHead = (label, sub = '') =>
                        `<div class="small fw-semibold text-uppercase mb-1" style="letter-spacing:.06em;font-size:10px;color:rgba(255,255,255,0.5)">${label}</div>` +
                        (sub ? `<div style="font-size:9px;color:rgba(255,255,255,0.3);margin-bottom:10px">${sub}</div>` : '<div style="margin-bottom:10px"></div>');

                    const chartBox = (id, h = 200) =>
                        `<div style="height:${h}px;position:relative"><canvas id="${id}"></canvas></div>`;

                    // ── KPIs ─────────────────────────────────────────────────────────────
                    const kpis = [
                        { label: 'Wszystkich', v: stats.total, color: '#3b82f6' },
                        { label: 'Zakończone', v: stats.byStatus.completed, sub: pct(stats.byStatus.completed, stats.total), color: STATUS_COLORS.completed },
                        { label: 'Oczekujące', v: stats.byStatus.pending, sub: pct(stats.byStatus.pending, stats.total), color: STATUS_COLORS.pending },
                        { label: 'W trakcie', v: stats.byStatus.in_progress, sub: pct(stats.byStatus.in_progress, stats.total), color: STATUS_COLORS.in_progress },
                        { label: 'Anulowane', v: stats.byStatus.cancelled, sub: pct(stats.byStatus.cancelled, stats.total), color: STATUS_COLORS.cancelled },
                        { label: 'Komentarzy', v: stats.totalComments, sub: stats.total ? (stats.totalComments/stats.total).toFixed(1)+' / zad.' : '', color: '#e879f9' },
                        { label: 'Podzadań', v: stats.totalSubtasks, sub: stats.total ? (stats.totalSubtasks/stats.total).toFixed(1)+' / zad.' : '', color: '#38bdf8' },
                    ];
                    const kpiRow = document.createElement('div');
                    kpiRow.className = 'd-flex flex-wrap gap-2 mb-3';
                    kpiRow.innerHTML = kpis.map(k => `
                        <div class="flex-grow-1 rounded-2 p-3" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);min-width:80px;max-width:160px">
                            <div style="font-size:24px;font-weight:700;color:${k.color};line-height:1.1">${k.v}</div>
                            <div style="font-size:10px;color:rgba(255,255,255,0.45);margin-top:4px;text-transform:uppercase;letter-spacing:.04em">${k.label}</div>
                            ${k.sub ? `<div style="font-size:9px;color:rgba(255,255,255,0.3);margin-top:2px">${k.sub}</div>` : ''}
                        </div>`).join('');
                    body.appendChild(kpiRow);

                    // ── Status donut + Top commented ─────────────────────────────────────
                    const row1 = document.createElement('div');
                    row1.className = 'row g-3 mb-3';
                    const topCommentedHtml = stats.topCommented.length === 0
                        ? '<div class="small text-muted">Brak komentarzy w zbiorze.</div>'
                        : (() => {
                            const max = stats.topCommented[0]?.count || 1;
                            return stats.topCommented.map((t, i) => `
                                <div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid rgba(255,255,255,0.05)">
                                    <span style="font-size:10px;color:rgba(255,255,255,0.3);min-width:18px">#${i+1}</span>
                                    <span class="flex-grow-1 small text-truncate" style="font-size:11px" title="#${t.id} ${escHtml(t.name)}">#${t.id} ${escHtml(t.name)}</span>
                                    <div style="width:50px;height:4px;background:rgba(255,255,255,0.07);border-radius:2px;overflow:hidden;flex-shrink:0">
                                        <div style="width:${Math.round(t.count/max*100)}%;height:100%;background:#8b5cf6;border-radius:2px"></div>
                                    </div>
                                    <span style="font-size:11px;font-weight:600;color:#8b5cf6;min-width:20px;text-align:right">${t.count}</span>
                                </div>`).join('');
                          })();
                    const legendHtml = Object.entries(STATUS_COLORS).map(([s, c]) =>
                        `<div class="d-flex align-items-center gap-1" style="font-size:10px;color:rgba(255,255,255,0.45)">
                            <span style="width:8px;height:8px;background:${c};border-radius:2px;display:inline-block;flex-shrink:0"></span>
                            ${STATUS_LABELS[s]} ${stats.byStatus[s]}
                        </div>`).join('');
                    row1.innerHTML = `
                        <div class="col-md-5">${card(`
                            ${sectionHead('Rozkład statusów')}
                            ${chartBox('pa-donut', 180)}
                            <div class="d-flex flex-wrap gap-2 mt-2">${legendHtml}</div>
                        `, 'h-100')}</div>
                        <div class="col-md-7">${card(`
                            ${sectionHead('Najaktywniejsze zadania', 'wg liczby komentarzy')}
                            <div id="pa-top-commented">${topCommentedHtml}</div>
                        `, 'h-100')}</div>`;
                    body.appendChild(row1);

                    if (window.Chart && stats.total > 0) {
                        mkChart('pa-donut', {
                            type: 'doughnut',
                            data: {
                                labels: Object.values(STATUS_LABELS),
                                datasets: [{
                                    data: Object.keys(STATUS_COLORS).map(s => stats.byStatus[s]),
                                    backgroundColor: Object.values(STATUS_COLORS).map(c => c + '30'),
                                    borderColor: Object.values(STATUS_COLORS),
                                    borderWidth: 2,
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '68%',
                                plugins: { legend: { display: false } }
                            }
                        });
                    }

                    // ── Stale tasks ──────────────────────────────────────────────────────
                    if (stats.stale.length > 0) {
                        const staleEl = document.createElement('div');
                        staleEl.className = 'rounded-2 p-3 mb-3';
                        staleEl.style.cssText = 'background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1)';
                        staleEl.innerHTML = sectionHead(`Zadania bez aktywności ≥ 7 dni (${stats.stale.length})`, 'nieukończone · posortowane wg ostatniej aktywności') +
                            stats.stale.map(t => {
                                const col = t.daysSince > 20 ? '#ef4444' : t.daysSince > 12 ? '#f59e0b' : '#10b981';
                                const sc = STATUS_COLORS[t.status] || '#888';
                                return `<div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid rgba(255,255,255,0.05)">
                                    <span style="font-size:9px;padding:2px 6px;border-radius:3px;background:${sc}20;color:${sc};border:1px solid ${sc}40;white-space:nowrap">${STATUS_LABELS[t.status] || t.status}</span>
                                    <span class="flex-grow-1 small text-truncate" style="font-size:11px" title="#${t.id} ${escHtml(t.name)}">#${t.id} ${escHtml(t.name)}</span>
                                    <span style="font-size:10px;color:rgba(255,255,255,0.4)">${escHtml(t.assigned_to?.name || '—')}</span>
                                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:${col}15;color:${col};border:1px solid ${col}30;white-space:nowrap">${t.daysSince}d temu</span>
                                </div>`;
                            }).join('');
                        body.appendChild(staleEl);
                    }

                    // ── By assignee + By category ────────────────────────────────────────
                    const row2 = document.createElement('div');
                    row2.className = 'row g-3 mb-3';
                    row2.innerHTML = `
                        <div class="col-md-6">${card(`
                            ${sectionHead('Zadania wg wykonawcy', 'przypisane · zakończone · komentarze')}
                            ${chartBox('pa-assignee', 240)}
                        `, 'h-100')}</div>
                        <div class="col-md-6">${card(`
                            ${sectionHead('Zadania wg kategorii', 'wszystkie · zakończone')}
                            ${chartBox('pa-category', 240)}
                        `, 'h-100')}</div>`;
                    body.appendChild(row2);

                    const sortedAssignees = Object.entries(stats.byAssignee).sort((a, b) => b[1] - a[1]).slice(0, 10);
                    mkChart('pa-assignee', {
                        type: 'bar',
                        data: {
                            labels: sortedAssignees.map(([n]) => n),
                            datasets: [
                                { label: 'Przypisane', data: sortedAssignees.map(([n]) => stats.byAssignee[n] || 0), backgroundColor: '#3b82f625', borderColor: '#3b82f6', borderWidth: 1.5, borderRadius: 3 },
                                { label: 'Zakończone', data: sortedAssignees.map(([n]) => stats.assigneeCompleted[n] || 0), backgroundColor: '#10b98125', borderColor: '#10b981', borderWidth: 1.5, borderRadius: 3 },
                                { label: 'Komentarze', data: sortedAssignees.map(([n]) => stats.assigneeComments[n] || 0), backgroundColor: '#8b5cf625', borderColor: '#8b5cf6', borderWidth: 1.5, borderRadius: 3 },
                            ]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 8, font: { size: 10 } } } },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' } },
                                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
                            }
                        }
                    });

                    const sortedCats = Object.entries(stats.byCategory).sort((a, b) => b[1] - a[1]).slice(0, 10);
                    mkChart('pa-category', {
                        type: 'bar',
                        data: {
                            labels: sortedCats.map(([n]) => n.length > 22 ? n.substring(0, 22) + '…' : n),
                            datasets: [
                                { label: 'Wszystkie', data: sortedCats.map(([n]) => stats.byCategory[n] || 0), backgroundColor: '#f59e0b25', borderColor: '#f59e0b', borderWidth: 1.5, borderRadius: 3 },
                                { label: 'Zakończone', data: sortedCats.map(([n]) => stats.categoryCompleted[n] || 0), backgroundColor: '#10b98125', borderColor: '#10b981', borderWidth: 1.5, borderRadius: 3 },
                            ]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 8, font: { size: 10 } } } },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' } },
                                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
                            }
                        }
                    });

                    // ── Temporal + Comments by author ────────────────────────────────────
                    const row3 = document.createElement('div');
                    row3.className = 'row g-3 mb-3';
                    const sortedAuthors = Object.entries(stats.commentsByAuthor).sort((a, b) => b[1] - a[1]).slice(0, 8);
                    row3.innerHTML = `
                        <div class="col-md-4">${card(`
                            ${sectionHead('Tworzenie zadań — dzień tygodnia', 'created_at')}
                            ${chartBox('pa-weekday', 160)}
                        `, 'h-100')}</div>
                        <div class="col-md-4">${card(`
                            ${sectionHead('Tworzenie zadań — pora dnia', 'created_at · buckety 4h')}
                            ${chartBox('pa-hour', 160)}
                        `, 'h-100')}</div>
                        <div class="col-md-4">${card(`
                            ${sectionHead('Komentarze wg autora')}
                            ${chartBox('pa-comments-author', 200)}
                        `, 'h-100')}</div>`;
                    body.appendChild(row3);

                    mkChart('pa-weekday', {
                        type: 'bar',
                        data: {
                            labels: ['Pon','Wt','Śr','Czw','Pt','Sob','Niedz'],
                            datasets: [{
                                data: stats.weekdays,
                                backgroundColor: stats.weekdays.map((_, i) => i >= 5 ? '#f59e0b44' : '#3b82f625'),
                                borderColor: stats.weekdays.map((_, i) => i >= 5 ? '#f59e0b' : '#3b82f6'),
                                borderWidth: 1.5, borderRadius: 3,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { font: { size: 9 } } }
                            }
                        }
                    });

                    mkChart('pa-hour', {
                        type: 'bar',
                        data: {
                            labels: stats.hourLabels,
                            datasets: [{
                                data: stats.hourBuckets,
                                backgroundColor: '#06b6d425', borderColor: '#06b6d4', borderWidth: 1.5, borderRadius: 3,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { font: { size: 9 } } }
                            }
                        }
                    });

                    if (sortedAuthors.length > 0) {
                        mkChart('pa-comments-author', {
                            type: 'pie',
                            data: {
                                labels: sortedAuthors.map(([n]) => n),
                                datasets: [{
                                    data: sortedAuthors.map(([, v]) => v),
                                    backgroundColor: sortedAuthors.map((_, i) => mkColor(i) + '55'),
                                    borderColor: sortedAuthors.map((_, i) => mkColor(i)),
                                    borderWidth: 1.5,
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { position: 'right', labels: { boxWidth: 8, padding: 8, font: { size: 10 } } } }
                            }
                        });
                    }

                    // ── Task lifetime + Mentions received ────────────────────────────────
                    const row4 = document.createElement('div');
                    row4.className = 'row g-3 mb-3';
                    const sortedMentions = Object.entries(stats.mentionsReceived).sort((a, b) => b[1] - a[1]);
                    const maxMent = sortedMentions[0]?.[1] || 1;
                    const totalMent = sortedMentions.reduce((s, [, v]) => s + v, 0);
                    const mentListHtml = sortedMentions.length === 0
                        ? '<div class="small text-muted">Brak wzmiankowań @user w komentarzach.</div>'
                        : sortedMentions.map(([name, cnt], i) => `
                            <div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid rgba(255,255,255,0.05)">
                                <span style="font-size:10px;font-weight:600;min-width:80px;color:${mkColor(i)}">${escHtml(name)}</span>
                                <div style="flex:1;height:5px;background:rgba(255,255,255,0.06);border-radius:2px;overflow:hidden">
                                    <div style="width:${Math.round(cnt/maxMent*100)}%;height:100%;background:${mkColor(i)};border-radius:2px"></div>
                                </div>
                                <span style="font-size:11px;font-weight:600;min-width:28px;text-align:right;color:${mkColor(i)}">${cnt}×</span>
                                <span style="font-size:10px;color:rgba(255,255,255,0.3);min-width:28px;text-align:right">${pct(cnt, totalMent)}</span>
                            </div>`).join('');
                    row4.innerHTML = `
                        <div class="col-md-7">${card(`
                            ${sectionHead('Czas życia zadania wg wykonawcy (dni)', 'created_at → completed_at · tylko zakończone')}
                            ${stats.lifetimeStats.length > 0 ? chartBox('pa-lifetime', 200) : '<div class="small text-muted py-3">Brak zakończonych zadań z datą w zbiorze.</div>'}
                        `, 'h-100')}</div>
                        <div class="col-md-5">${card(`
                            ${sectionHead('Wzmiankowania — kto jest oznaczany?', '@mentions w komentarzach (resolved_user)')}
                            <div id="pa-mentions-list">${mentListHtml}</div>
                        `, 'h-100')}</div>`;
                    body.appendChild(row4);

                    if (stats.lifetimeStats.length > 0) {
                        mkChart('pa-lifetime', {
                            type: 'bar',
                            data: {
                                labels: stats.lifetimeStats.map(l => l.name),
                                datasets: [
                                    { label: 'Średnia (dni)', data: stats.lifetimeStats.map(l => l.avg), backgroundColor: '#10b98125', borderColor: '#10b981', borderWidth: 1.5, borderRadius: 3 },
                                    { label: 'Maks. (dni)', data: stats.lifetimeStats.map(l => l.max), backgroundColor: '#ef444425', borderColor: '#ef4444', borderWidth: 1, borderRadius: 3 },
                                ]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 10, font: { size: 10 } } } },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { font: { size: 9 } }, title: { display: true, text: 'dni', color: 'rgba(255,255,255,0.3)', font: { size: 9 } } }
                                }
                            }
                        });
                    }

                    // ── Cross-comment matrix ─────────────────────────────────────────────
                    const activeCross = stats.crossPeople.filter(p => {
                        const sent = stats.crossPeople.reduce((s, o) => s + (stats.crossData[p]?.[o] || 0), 0);
                        const recv = stats.crossPeople.reduce((s, c) => s + (stats.crossData[c]?.[p] || 0), 0);
                        return sent > 0 || recv > 0;
                    });
                    if (activeCross.length > 0) {
                        const crossMax = Math.max(...activeCross.flatMap(r => activeCross.map(c => stats.crossData[r]?.[c] || 0)), 1);
                        const crossEl = document.createElement('div');
                        crossEl.className = 'rounded-2 p-3 mb-3';
                        crossEl.style.cssText = 'background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1)';
                        crossEl.innerHTML = sectionHead('Cross-comment matrix — kto komentuje czyje zadania', 'wiersze = autor komentarza · kolumny = właściciel zadania · wartość = liczba komentarzy') +
                            `<div style="overflow-x:auto">
                                <table style="border-collapse:collapse;font-size:10px;font-family:'Inter',monospace;white-space:nowrap">
                                    <thead><tr>
                                        <th style="padding:4px 10px 4px 0;color:rgba(255,255,255,0.3);font-weight:400;text-align:left;border-bottom:1px solid rgba(255,255,255,0.1)">↓ kom / → zadanie</th>
                                        ${activeCross.map(p => `<th style="padding:4px 8px;color:rgba(255,255,255,0.5);font-weight:500;text-align:center;border-bottom:1px solid rgba(255,255,255,0.1)">${escHtml(p)}</th>`).join('')}
                                    </tr></thead>
                                    <tbody>${activeCross.map(row => {
                                        const rowTotal = activeCross.reduce((s, c) => s + (stats.crossData[row]?.[c] || 0), 0);
                                        if (!rowTotal) return '';
                                        return `<tr><td style="padding:4px 10px 4px 0;color:rgba(255,255,255,0.65);font-weight:600;border-bottom:1px solid rgba(255,255,255,0.05)">${escHtml(row)}</td>` +
                                            activeCross.map(col => {
                                                const val = stats.crossData[row]?.[col] || 0;
                                                const self = row === col;
                                                const int = val ? Math.max(0.06, val / crossMax) : 0;
                                                const bg = self ? 'rgba(255,255,255,0.03)' : `rgba(59,130,246,${int * 0.45})`;
                                                const tc = self ? 'rgba(255,255,255,0.15)' : val > crossMax * 0.6 ? '#3b82f6' : val > 0 ? 'rgba(255,255,255,0.7)' : 'rgba(255,255,255,0.15)';
                                                return `<td style="padding:4px 8px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.05);background:${bg};color:${tc};font-weight:${val > crossMax*0.5 ? '700' : '400'}">${val || '·'}</td>`;
                                            }).join('') + '</tr>';
                                    }).join('')}</tbody>
                                </table>
                            </div>`;
                        body.appendChild(crossEl);
                    }

                    // ── Delegation matrix + Edited comments ──────────────────────────────
                    const activeDelegPeople = stats.delegPeople.filter(p => {
                        const sent = stats.delegPeople.reduce((s, a) => s + (stats.delegData[p]?.[a] || 0), 0);
                        return sent > 0;
                    });
                    if (activeDelegPeople.length > 0) {
                        const delegMax = Math.max(...activeDelegPeople.flatMap(r => stats.delegPeople.map(c => stats.delegData[r]?.[c] || 0)), 1);
                        const editedEntries = Object.entries(stats.editedByAuthor).sort((a, b) => b[1] - a[1]);
                        const maxEdited = editedEntries[0]?.[1] || 1;
                        const editedHtml = editedEntries.length === 0
                            ? '<div class="small text-muted">Brak edytowanych komentarzy.</div>'
                            : editedEntries.map(([name, edited], i) => {
                                const total = stats.commentsByAuthor[name] || edited;
                                const ep = Math.round(edited / total * 100);
                                return `<div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid rgba(255,255,255,0.05)">
                                    <span style="font-size:10px;font-weight:600;min-width:80px;color:${mkColor(i)}">${escHtml(name)}</span>
                                    <div style="flex:1;height:4px;background:rgba(255,255,255,0.07);border-radius:2px;overflow:hidden">
                                        <div style="width:${ep}%;height:100%;background:#ef4444;border-radius:2px"></div>
                                    </div>
                                    <span style="font-size:10px;color:#ef4444;min-width:20px;text-align:right">${edited}</span>
                                    <span style="font-size:10px;color:rgba(255,255,255,0.3);min-width:30px;text-align:right">${ep}%</span>
                                </div>`;
                              }).join('');
                        const row5 = document.createElement('div');
                        row5.className = 'row g-3 mb-3';
                        row5.innerHTML = `
                            <div class="col-md-7">${card(`
                                ${sectionHead('Macierz delegacji — kto tworzy zadania dla kogo', 'created_by → assigned_to · wartość = liczba zadań')}
                                <div style="overflow-x:auto">
                                    <table style="border-collapse:collapse;font-size:10px;font-family:'Inter',monospace;white-space:nowrap">
                                        <thead><tr>
                                            <th style="padding:4px 10px 4px 0;color:rgba(255,255,255,0.3);font-weight:400;text-align:left;border-bottom:1px solid rgba(255,255,255,0.1)">↓ twórca / → wykonawca</th>
                                            ${stats.delegPeople.map(p => `<th style="padding:4px 8px;color:rgba(255,255,255,0.5);font-weight:500;text-align:center;border-bottom:1px solid rgba(255,255,255,0.1)">${escHtml(p)}</th>`).join('')}
                                        </tr></thead>
                                        <tbody>${activeDelegPeople.map(row => {
                                            return `<tr><td style="padding:4px 10px 4px 0;color:rgba(255,255,255,0.65);font-weight:600;border-bottom:1px solid rgba(255,255,255,0.05)">${escHtml(row)}</td>` +
                                                stats.delegPeople.map(col => {
                                                    const val = stats.delegData[row]?.[col] || 0;
                                                    const self = row === col;
                                                    const int = val ? Math.max(0.07, val / delegMax) : 0;
                                                    const bg = self ? 'rgba(245,158,11,0.1)' : `rgba(245,158,11,${int * 0.35})`;
                                                    const tc = self ? '#f59e0b' : val > delegMax * 0.6 ? '#f59e0b' : val > 0 ? 'rgba(255,255,255,0.7)' : 'rgba(255,255,255,0.15)';
                                                    return `<td style="padding:4px 8px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.05);background:${bg};color:${tc};font-weight:${val > delegMax*0.5 ? '700' : '400'}">${val || '·'}</td>`;
                                                }).join('') + '</tr>';
                                        }).join('')}</tbody>
                                    </table>
                                </div>
                            `, 'h-100')}</div>
                            <div class="col-md-5">${card(`
                                ${sectionHead('Komentarze edytowane wg autora', 'updated_at ≠ created_at na komentarzu')}
                                ${editedHtml}
                            `, 'h-100')}</div>`;
                        body.appendChild(row5);
                    }
                }

                function getBs() {
                    return window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
                }

                function openAnalyticsModal(parsed) {
                    const bs = getBs();
                    if (!bs || !bs.Modal) {
                        console.error('[analytics] bootstrap.Modal niedostępne na window');
                        alert('Błąd: Bootstrap JS nie jest załadowany. Odśwież stronę (Ctrl+F5).');
                        return;
                    }

                    const analyticsBody = document.getElementById('pe-analytics-body');
                    if (analyticsBody) {
                        analyticsBody.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Ładowanie wykresów…</div>';
                    }

                    let modal;
                    try {
                        modal = bs.Modal.getOrCreateInstance(analyticsModalEl);
                    } catch (e) {
                        console.error('[analytics] Modal.getOrCreateInstance failed', e);
                        return;
                    }

                    const onShown = () => {
                        loadChartJs().then(() => {
                            try { renderAnalytics(parsed); }
                            catch (e) {
                                console.error('[analytics] renderAnalytics failed', e);
                                if (analyticsBody) {
                                    analyticsBody.innerHTML = '<div class="alert alert-danger">Błąd renderowania wykresów: ' + (e?.message || e) + '</div>';
                                }
                            }
                        });
                    };
                    analyticsModalEl.addEventListener('shown.bs.modal', onShown, { once: true });

                    try {
                        modal.show();
                    } catch (e) {
                        console.error('[analytics] modal.show() failed', e);
                    }
                }

                analyzeBtn.addEventListener('click', () => {
                    const jsonText = textarea?.value;
                    if (!jsonText) {
                        console.warn('[analytics] brak JSON w textarea');
                        return;
                    }
                    let parsed;
                    try { parsed = JSON.parse(jsonText); }
                    catch (e) {
                        console.error('[analytics] JSON.parse failed', e);
                        alert('Niepoprawny JSON w polu wyniku.');
                        return;
                    }

                    const bs = getBs();
                    const tasksModalEl = document.getElementById('promptEngineTasksModal');
                    const tasksModalInst = bs && tasksModalEl ? bs.Modal.getInstance(tasksModalEl) : null;

                    // Tasks modal is open → close it first, then open analytics in the hidden callback.
                    if (tasksModalInst && tasksModalEl.classList.contains('show')) {
                        const onHidden = () => {
                            tasksModalEl.removeEventListener('hidden.bs.modal', onHidden);
                            // Defer a tick to let Bootstrap clean up its modal-open lock on <body>.
                            setTimeout(() => openAnalyticsModal(parsed), 50);
                        };
                        tasksModalEl.addEventListener('hidden.bs.modal', onHidden);
                        tasksModalInst.hide();
                        // Failsafe: if hidden event doesn't fire within 600ms, open anyway.
                        setTimeout(() => {
                            if (!analyticsModalEl.classList.contains('show')) {
                                tasksModalEl.removeEventListener('hidden.bs.modal', onHidden);
                                openAnalyticsModal(parsed);
                            }
                        }, 600);
                    } else {
                        openAnalyticsModal(parsed);
                    }
                });

                analyticsModalEl.addEventListener('hidden.bs.modal', destroyCharts);
            })();
        </script>
    @endpush
</x-app-layout>
