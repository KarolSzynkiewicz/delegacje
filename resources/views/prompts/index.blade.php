@php
    $defaultStart = now()->startOfMonth()->toDateString();
    $defaultEnd = now()->toDateString();
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

        {{-- Modal musi być podpięty pod body: .app-content-wrapper ma backdrop-filter i psuje fixed + klikalność. --}}
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

    @push('scripts')
        <script>
            (function () {
                const root = document.getElementById('prompt-engine-page');
                if (!root) return;

                const modalEl = document.getElementById('promptEngineTasksModal');
                if (modalEl && modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                const exportUrl = root.dataset.exportTasksUrl;
                const defaultStart = root.dataset.defaultStart;
                const defaultEnd = root.dataset.defaultEnd;
                const startInput = document.getElementById('pe_start_date');
                const endInput = document.getElementById('pe_end_date');
                const fetchBtn = document.getElementById('pe-tasks-fetch');
                const copyBtn = document.getElementById('pe-tasks-copy');
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
        </script>
    @endpush
</x-app-layout>
