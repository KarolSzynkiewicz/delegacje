<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Połącz dokumenty">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('system-actions.index') }}" action="back">
                    Wróć
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Źródła i spółki">
                <p class="text-muted small">
                    Zaznacz typy, które biznes rozbił na spółki. Dalej utwórz nowy typ
                    i wskaż spółkę przy każdym źródle. Stare wpisy zostają.
                </p>

                @if($documents->isEmpty() || $companies->isEmpty())
                    <p class="mb-0">Brak typów dokumentów albo spółek.</p>
                @else
                    <form id="merge-form">
                        @csrf
                        <div id="step-pick">
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach($documents as $document)
                                    <label class="form-check border rounded px-3 py-2 mb-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="sources[]"
                                            value="{{ $document->id }}"
                                            data-name="{{ $document->name }}"
                                        >
                                        <span class="form-check-label">
                                            {{ $document->name }}
                                            <span class="text-muted">· {{ $document->employee_documents_count }} wpisów</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="d-flex justify-content-end">
                                <x-ui.button variant="primary" type="button" id="merge-next">Dalej</x-ui.button>
                            </div>
                        </div>

                        <div id="step-map" class="d-none">
                            @include('documents.partials.fields', ['companyScopedDefault' => true])

                            <div id="map-rows" class="d-flex flex-column gap-3 mb-3"></div>

                            <div class="d-flex justify-content-between">
                                <x-ui.button variant="ghost" type="button" id="merge-back">Wstecz</x-ui.button>
                                <x-ui.button variant="primary" type="submit" id="merge-save">Zapisz</x-ui.button>
                            </div>
                        </div>
                    </form>

                    <div id="merge-progress" class="d-none">
                        <div class="progress-ui mb-2" aria-hidden="true">
                            <div class="progress-bar-ui" id="merge-bar"></div>
                        </div>
                        <p class="mb-0" id="merge-status"></p>
                    </div>
                    <p class="text-danger small mb-0 mt-3 d-none" id="merge-error"></p>

                    <template id="company-select-template">
                        <select class="form-select" required>
                            <option value="">— spółka —</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </template>
                @endif
            </x-ui.card>
        </div>
    </div>

    @if($documents->isNotEmpty() && $companies->isNotEmpty())
        <script>
            (function () {
                const form = document.getElementById('merge-form');
                const stepPick = document.getElementById('step-pick');
                const stepMap = document.getElementById('step-map');
                const mapRows = document.getElementById('map-rows');
                const progress = document.getElementById('merge-progress');
                const bar = document.getElementById('merge-bar');
                const status = document.getElementById('merge-status');
                const errorBox = document.getElementById('merge-error');
                const template = document.getElementById('company-select-template');
                const token = form.querySelector('input[name="_token"]').value;
                const planUrl = @json(route('system-actions.documents.merge.plan'));
                const chunkUrl = @json(route('system-actions.documents.merge.chunk'));

                const checkedSources = function () {
                    return Array.from(form.querySelectorAll('input[name="sources[]"]:checked'));
                };

                const showError = function (message) {
                    errorBox.textContent = message || '';
                    errorBox.classList.toggle('d-none', !message);
                };

                document.getElementById('merge-next').addEventListener('click', function () {
                    const selected = checkedSources();
                    showError('');
                    if (selected.length === 0) {
                        showError('Zaznacz co najmniej jeden typ.');
                        return;
                    }
                    mapRows.replaceChildren();
                    selected.forEach(function (input) {
                        const row = document.createElement('div');
                        const label = document.createElement('label');
                        label.className = 'form-label';
                        label.textContent = input.dataset.name;
                        const select = template.content.querySelector('select').cloneNode(true);
                        select.name = 'company[' + input.value + ']';
                        row.append(label, select);
                        mapRows.append(row);
                    });
                    stepPick.classList.add('d-none');
                    stepMap.classList.remove('d-none');
                });

                document.getElementById('merge-back').addEventListener('click', function () {
                    stepMap.classList.add('d-none');
                    stepPick.classList.remove('d-none');
                });

                const paint = function (state) {
                    const total = state.total || 0;
                    const done = state.done || 0;
                    const width = total === 0 ? 100 : Math.round((done / total) * 100);
                    bar.style.width = width + '%';
                    if (!state.finished) {
                        status.textContent = 'Kopiowanie ' + done + ' z ' + total + '…';
                        return;
                    }
                    let text = 'Gotowe. Skopiowano ' + state.copied + ', pominięto ' + state.skipped + ' (już były).';
                    if (state.missing_files) {
                        text += ' Brak pliku przy ' + state.missing_files + ' wpisach.';
                    }
                    if (state.target_marked) {
                        text += ' Typ docelowy oznaczony jako na spółkę.';
                    }
                    status.textContent = text;
                };

                form.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    showError('');
                    const save = document.getElementById('merge-save');
                    save.disabled = true;
                    paint({ total: 1, done: 0, finished: false, copied: 0, skipped: 0 });
                    progress.classList.remove('d-none');

                    try {
                        const planned = await fetch(planUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: new FormData(form),
                        });
                        let state = await planned.json();
                        if (!planned.ok) {
                            const messages = state.errors ? Object.values(state.errors).flat() : [];
                            throw new Error(messages.join(' ') || 'Nie udało się rozpocząć kopiowania.');
                        }
                        paint(state);
                        while (!state.finished) {
                            const chunk = await fetch(chunkUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                },
                                body: JSON.stringify({ token: state.token }),
                            });
                            state = await chunk.json();
                            if (!chunk.ok) {
                                const messages = state.errors ? Object.values(state.errors).flat() : [];
                                throw new Error(messages.join(' ') || 'Przerwane kopiowanie.');
                            }
                            paint(state);
                        }
                    } catch (error) {
                        showError(error.message || 'Przerwane kopiowanie.');
                        save.disabled = false;
                    }
                });
            })();
        </script>
    @endif
</x-app-layout>
