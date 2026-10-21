<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj wiele kosztów">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('cost-registry.index') }}" action="back">
                    Rejestr kosztów
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @php
        $oldRows = old('entries');
        $initialRows = is_array($oldRows) ? array_values($oldRows) : [];
        $errorKeys = $errors->keys();
    @endphp

    <x-ui.card label="Szybkie wprowadzanie">
        <form
            method="POST"
            action="{{ route('cost-registry.store-many') }}"
            x-data="crBulkGrid({
                initialRows: @js($initialRows),
                errorKeys: @js($errorKeys),
                defaults: {
                    accounting_date: @js(now()->format('Y-m-d')),
                    currency: 'PLN',
                    company_id: @js($defaultCompanyId ? (string) $defaultCompanyId : ''),
                    period: @js($defaultPeriod),
                },
            })"
            @keydown.enter="onEnter($event)"
        >
            @csrf

            @if ($errors->any())
                <x-alert type="danger" dismissible icon="exclamation-triangle">
                    Popraw zaznaczone pola — {{ $errors->count() }} {{ $errors->count() === 1 ? 'błąd' : ($errors->count() < 5 ? 'błędy' : 'błędów') }}.
                    <ul class="mb-0 mt-1 small">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <p class="text-muted small mb-3">
                Każdy wiersz trafia do rejestru jako <strong>koszt ogólnofirmowy</strong> z przypisaną spółką i miesiącem rozliczeniowym.
                Koszty projektowe, transportu i serwisu dodawaj na ich kartach — pojawią się w rejestrze automatycznie.
            </p>

            {{-- Domyślne wartości dla nowych wierszy --}}
            <div class="cr-bulk-defaults mb-3">
                <span class="cr-bulk-defaults__label">Domyślne dla nowych wierszy</span>
                <input type="date" class="form-control form-control-sm" x-model="defaults.accounting_date" aria-label="Domyślna data">
                <select class="form-select form-select-sm" x-model="defaults.company_id" aria-label="Domyślna spółka">
                    <option value="">— bez spółki —</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" x-model="defaults.period" aria-label="Domyślny miesiąc rozliczeniowy">
                    @foreach($periodOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm cr-bulk-defaults__currency" x-model="defaults.currency" aria-label="Domyślna waluta">
                    @foreach($currencies as $cur)
                        <option value="{{ $cur }}">{{ $cur }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="applyDefaultsToAll" title="Nadpisz datę, spółkę, miesiąc i walutę we wszystkich wierszach">
                    <i class="bi bi-arrow-down-square me-1"></i>Zastosuj do wszystkich
                </button>
            </div>

            {{-- Siatka --}}
            <div class="table-responsive">
                <table class="table align-middle mb-2 cr-bulk-table">
                    <thead>
                        <tr>
                            <th class="text-muted cr-col--idx">#</th>
                            <th class="cr-col--date">Data <span class="text-danger">*</span></th>
                            <th class="cr-col--name">Nazwa <span class="text-danger">*</span></th>
                            <th class="text-end cr-col--amount">Kwota <span class="text-danger">*</span></th>
                            <th class="cr-col--currency">Waluta</th>
                            <th class="cr-col--company">Spółka</th>
                            <th class="cr-col--period">Miesiąc rozl. <span class="text-danger">*</span></th>
                            <th class="cr-col--category">Kategoria</th>
                            <th class="cr-col--comment">Komentarz</th>
                            <th class="text-center cr-col--flag" title="Nieistotny finansowo">Nieist.</th>
                            <th class="cr-col--remove"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr>
                                <td class="text-muted small font-mono" x-text="index + 1"></td>
                                <td>
                                    <input type="date" :name="`entries[${index}][accounting_date]`" x-model="row.accounting_date"
                                           class="form-control form-control-sm" :class="invalid(index, 'accounting_date')" required>
                                </td>
                                <td>
                                    <input type="text" :name="`entries[${index}][name]`" x-model="row.name" data-field="name"
                                           class="form-control form-control-sm" :class="invalid(index, 'name')"
                                           placeholder="Nazwa kosztu" maxlength="255" required>
                                </td>
                                <td>
                                    <input type="number" :name="`entries[${index}][amount]`" x-model="row.amount"
                                           class="form-control form-control-sm text-end font-mono" :class="invalid(index, 'amount')"
                                           step="0.01" min="0" placeholder="0,00" required>
                                </td>
                                <td>
                                    <select :name="`entries[${index}][currency]`" x-model="row.currency" class="form-select form-select-sm" :class="invalid(index, 'currency')" required>
                                        @foreach($currencies as $cur)
                                            <option value="{{ $cur }}">{{ $cur }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select :name="`entries[${index}][company_id]`" x-model="row.company_id" class="form-select form-select-sm" :class="invalid(index, 'company_id')">
                                        <option value="">— bez spółki —</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select :name="`entries[${index}][period]`" x-model="row.period" class="form-select form-select-sm" :class="invalid(index, 'period')" required>
                                        @foreach($periodOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select :name="`entries[${index}][category]`" x-model="row.category" class="form-select form-select-sm" :class="invalid(index, 'category')">
                                        <option value="">— brak —</option>
                                        @foreach($categoryOptions as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" :name="`entries[${index}][comment]`" x-model="row.comment"
                                           class="form-control form-control-sm" :class="invalid(index, 'comment')" placeholder="Opcjonalnie">
                                </td>
                                <td class="text-center">
                                    <input type="hidden" :name="`entries[${index}][financially_irrelevant]`" :value="row.financially_irrelevant ? 1 : 0">
                                    <input type="checkbox" class="form-check-input" x-model="row.financially_irrelevant" title="Nieistotny finansowo — poza sumami">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger cr-bulk-remove" @click="removeRow(row.id)" x-show="rows.length > 1" title="Usuń wiersz" tabindex="-1">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="addRow(1, true)">
                    <i class="bi bi-plus-lg me-1"></i>Dodaj wiersz
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="addRow(5, true)">
                    <i class="bi bi-plus-lg me-1"></i>Dodaj 5
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="pasteOpen = !pasteOpen" :class="{ 'active': pasteOpen }">
                    <i class="bi bi-clipboard-plus me-1"></i>Wklej z arkusza
                </button>
                <span class="text-muted small ms-auto">
                    <kbd>Enter</kbd> w ostatnim wierszu dodaje kolejny ·
                    <span x-text="`${rows.length} ${rows.length === 1 ? 'wiersz' : (rows.length < 5 ? 'wiersze' : 'wierszy')}`"></span>
                </span>
            </div>

            {{-- Wklejanie z arkusza: nazwa [TAB] kwota [TAB] waluta [TAB] data --}}
            <div x-show="pasteOpen" x-cloak class="cr-bulk-paste mb-3">
                <label class="form-label small text-muted mb-1">
                    Jedna pozycja na linię, kolumny rozdzielone tabulatorem lub średnikiem:
                    <code>nazwa ; kwota ; waluta (opc.) ; data RRRR-MM-DD (opc.) ; komentarz (opc.)</code>
                </label>
                <textarea class="form-control form-control-sm font-mono" rows="4" x-model="pasteText"
                          placeholder="Czynsz biuro&#9;4500&#9;PLN&#9;2026-10-01&#10;Licencja CRM&#9;89,99&#9;EUR"></textarea>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-primary" @click="importPaste" :disabled="!pasteText.trim()">
                        <i class="bi bi-arrow-down me-1"></i>Dodaj do siatki
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="pasteText = ''; pasteOpen = false">Zamknij</button>
                    <span class="text-danger small align-self-center" x-text="pasteError"></span>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top">
                <div class="cr-bulk-total">
                    <span class="cr-bulk-total__label">Razem</span>
                    <template x-for="[cur, sum] in totals()" :key="cur">
                        <span class="font-mono cr-bulk-total__amount" x-text="`${formatAmount(sum)} ${cur}`"></span>
                    </template>
                    <span class="text-muted font-mono" x-show="totals().length === 0">—</span>
                </div>
                <div class="d-flex gap-2">
                    <x-ui.button variant="ghost" href="{{ route('cost-registry.index') }}" action="cancel">Anuluj</x-ui.button>
                    <x-ui.button variant="primary" type="submit" action="save">
                        Zapisz <span x-text="rows.length"></span>
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>

    <style>
        .cr-bulk-defaults {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.8rem;
            border: 1px dashed var(--glass-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
        }
        .cr-bulk-defaults .form-control, .cr-bulk-defaults .form-select { width: auto; min-width: 9rem; }
        .cr-bulk-defaults__currency.form-select { min-width: 5.5rem; }
        .cr-bulk-defaults__label {
            font-size: 0.66rem;
            font-weight: 650;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-right: 0.3rem;
        }
        .cr-bulk-table { table-layout: fixed; min-width: 1100px; }
        .cr-bulk-table th { font-size: 0.72rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cr-bulk-table th, .cr-bulk-table td { padding: 0.3rem 0.25rem !important; }
        .cr-bulk-table .cr-col--idx { width: 2rem; }
        .cr-bulk-table .cr-col--date { width: 8.4rem; }
        .cr-bulk-table .cr-col--amount { width: 6.5rem; }
        .cr-bulk-table .cr-col--currency { width: 5.2rem; }
        .cr-bulk-table .cr-col--company { width: 9rem; }
        .cr-bulk-table .cr-col--period { width: 9.5rem; }
        .cr-bulk-table .cr-col--category { width: 9rem; }
        .cr-bulk-table .cr-col--flag { width: 3.6rem; }
        .cr-bulk-table .cr-col--remove { width: 2.4rem; }
        .cr-bulk-table .form-select-sm { padding-right: 1.5rem !important; background-position: right 0.4rem center !important; }
        .cr-bulk-table .form-control-sm, .cr-bulk-table .form-select-sm { border-radius: 8px !important; }
        .cr-bulk-table .form-check-input { width: 1.1rem; height: 1.1rem; margin: 0; }
        .cr-bulk-remove { padding: 0.15rem 0.4rem; line-height: 1; }
        .cr-bulk-paste { padding: 0.8rem; border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(255, 255, 255, 0.02); }
        .cr-bulk-total { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.3rem 0.9rem; }
        .cr-bulk-total__label { font-size: 0.66rem; font-weight: 650; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); }
        .cr-bulk-total__amount { font-weight: 600; font-size: 1.05rem; font-variant-numeric: tabular-nums; }
        [x-cloak] { display: none !important; }
    </style>

    @push('scripts')
    <script>
        function crBulkGrid({ initialRows, errorKeys, defaults }) {
            let nextId = 1;

            return {
                defaults,
                rows: [],
                errorKeys: errorKeys || [],
                pasteOpen: false,
                pasteText: '',
                pasteError: '',

                init() {
                    this.rows = (initialRows || []).map((r) => this.makeRow(r));
                    if (this.rows.length === 0) {
                        this.addRow(3);
                    }
                },

                makeRow(overrides = {}) {
                    return {
                        id: nextId++,
                        accounting_date: this.defaults.accounting_date,
                        name: '',
                        amount: '',
                        currency: this.defaults.currency,
                        company_id: this.defaults.company_id,
                        period: this.defaults.period,
                        category: '',
                        comment: '',
                        financially_irrelevant: false,
                        ...overrides,
                        // z old() checkbox wraca jako "1"/"0"
                        ...(overrides.financially_irrelevant !== undefined
                            ? { financially_irrelevant: overrides.financially_irrelevant === true || overrides.financially_irrelevant === '1' || overrides.financially_irrelevant === 1 }
                            : {}),
                    };
                },

                addRow(count = 1, focus = false) {
                    for (let i = 0; i < count; i++) {
                        this.rows.push(this.makeRow());
                    }
                    if (focus) {
                        this.$nextTick(() => this.focusName(this.rows.length - 1));
                    }
                },

                removeRow(id) {
                    if (this.rows.length > 1) {
                        this.rows = this.rows.filter((r) => r.id !== id);
                    }
                },

                applyDefaultsToAll() {
                    this.rows.forEach((r) => {
                        r.accounting_date = this.defaults.accounting_date;
                        r.currency = this.defaults.currency;
                        r.company_id = this.defaults.company_id;
                        r.period = this.defaults.period;
                    });
                },

                focusName(index) {
                    const inputs = this.$root.querySelectorAll('tbody input[data-field="name"]');
                    inputs[index]?.focus();
                },

                // Enter nie wysyła formularza z pola siatki; w ostatnim wierszu dodaje kolejny.
                onEnter(event) {
                    const el = event.target;
                    if (el.tagName === 'TEXTAREA' || el.tagName === 'BUTTON') {
                        return;
                    }
                    if (el.closest('tbody')) {
                        event.preventDefault();
                        const tr = el.closest('tr');
                        const isLast = tr && !tr.nextElementSibling;
                        if (isLast) {
                            this.addRow(1, true);
                        } else if (tr) {
                            const index = Array.from(tr.parentElement.children).indexOf(tr);
                            this.focusName(index + 1);
                        }
                    } else if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {
                        event.preventDefault();
                    }
                },

                invalid(index, field) {
                    return this.errorKeys.includes(`entries.${index}.${field}`) ? 'is-invalid' : '';
                },

                parseAmount(raw) {
                    const normalized = String(raw ?? '').replace(/\s/g, '').replace(',', '.');
                    const value = parseFloat(normalized);
                    return Number.isFinite(value) ? value : null;
                },

                importPaste() {
                    this.pasteError = '';
                    const lines = this.pasteText.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
                    const parsed = [];
                    const bad = [];

                    lines.forEach((line, i) => {
                        const cols = line.split(/\t|;/).map((c) => c.trim());
                        const [name, amountRaw, currencyRaw, dateRaw, comment] = cols;
                        const amount = this.parseAmount(amountRaw);
                        if (!name || amount === null) {
                            bad.push(i + 1);
                            return;
                        }
                        const currency = (currencyRaw || '').toUpperCase();
                        const date = /^\d{4}-\d{2}-\d{2}$/.test(dateRaw || '') ? dateRaw : this.defaults.accounting_date;
                        parsed.push(this.makeRow({
                            name,
                            amount: amount.toFixed(2),
                            currency: /^[A-Z]{3}$/.test(currency) ? currency : this.defaults.currency,
                            accounting_date: date,
                            comment: comment || '',
                        }));
                    });

                    if (parsed.length === 0) {
                        this.pasteError = 'Nie rozpoznano żadnej linii — potrzebna co najmniej nazwa i kwota.';
                        return;
                    }

                    // Zastąp puste wiersze startowe zamiast dokładać do nich.
                    const nonEmpty = this.rows.filter((r) => r.name || r.amount);
                    this.rows = [...nonEmpty, ...parsed];
                    this.pasteText = '';
                    this.pasteOpen = false;
                    if (bad.length) {
                        this.pasteError = `Pominięto linie: ${bad.join(', ')}.`;
                        this.pasteOpen = true;
                    }
                },

                totals() {
                    const sums = {};
                    this.rows.forEach((r) => {
                        if (r.financially_irrelevant) return;
                        const amount = this.parseAmount(r.amount);
                        if (amount === null) return;
                        sums[r.currency] = (sums[r.currency] || 0) + amount;
                    });
                    return Object.entries(sums).sort(([a], [b]) => a.localeCompare(b));
                },

                formatAmount(value) {
                    return new Intl.NumberFormat('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
