<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj wiele kosztów ogólnofirmowych">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.tab.entries') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card label="Wprowadź koszty ogólnofirmowe">
        <form method="POST" action="{{ route('fixed-cost-entries.store-many') }}" x-data="entriesGrid()">
            @csrf

            @if ($errors->any())
                <x-alert type="danger" dismissible icon="exclamation-triangle">
                    Popraw błędy w formularzu.
                </x-alert>
            @endif

            {{-- Global period dates --}}
            <div class="row g-3 mb-4 pb-3 border-bottom">
                <div class="col-12">
                    <p class="fw-semibold mb-2 text-muted small text-uppercase tracking-wide">Okres obowiązywania (wspólny dla wszystkich wpisów)</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data od <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="period_start"
                        x-model="periodStart"
                        class="form-control"
                        required
                    >
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data do <span class="text-danger">*</span></label>
                    <input
                        type="date"
                        name="period_end"
                        x-model="periodEnd"
                        class="form-control"
                        required
                    >
                </div>
            </div>

            {{-- Per-row grid --}}
            <div class="table-responsive">
                <table class="table align-middle mb-2">
                    <thead>
                        <tr>
                            <th style="min-width:150px">Data księgowania <span class="text-danger">*</span></th>
                            <th style="min-width:220px">Nazwa <span class="text-danger">*</span></th>
                            <th style="min-width:130px">Cena <span class="text-danger">*</span></th>
                            <th style="min-width:110px">Waluta <span class="text-danger">*</span></th>
                            <th style="min-width:180px">Kategoria</th>
                            <th style="min-width:180px">Notatki</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr>
                                <td>
                                    <input
                                        type="date"
                                        :name="`entries[${index}][accounting_date]`"
                                        x-model="row.accounting_date"
                                        class="form-control form-control-sm"
                                        required
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        :name="`entries[${index}][name]`"
                                        x-model="row.name"
                                        class="form-control form-control-sm"
                                        placeholder="Nazwa kosztu"
                                        required
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        :name="`entries[${index}][amount]`"
                                        x-model="row.amount"
                                        class="form-control form-control-sm"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        required
                                    >
                                </td>
                                <td>
                                    <select
                                        :name="`entries[${index}][currency]`"
                                        x-model="row.currency"
                                        class="form-select form-select-sm"
                                        required
                                    >
                                        <option value="PLN">PLN</option>
                                        <option value="EUR">EUR</option>
                                        <option value="USD">USD</option>
                                        <option value="GBP">GBP</option>
                                    </select>
                                </td>
                                <td>
                                    <select
                                        :name="`entries[${index}][category]`"
                                        x-model="row.category"
                                        class="form-select form-select-sm"
                                    >
                                        <option value="">— brak —</option>
                                        @foreach(\App\Models\FixedCostTemplate::categoryOptions() as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        :name="`entries[${index}][notes]`"
                                        x-model="row.notes"
                                        class="form-control form-control-sm"
                                        placeholder="Opcjonalnie"
                                    >
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="removeRow(row.id)"
                                        x-show="rows.length > 1"
                                        title="Usuń wiersz"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center gap-3 mb-4">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="addRow">
                    <i class="bi bi-plus-lg me-1"></i>Dodaj wiersz
                </button>
                <span class="text-muted small" x-text="`${rows.length} ${rows.length === 1 ? 'wiersz' : (rows.length < 5 ? 'wiersze' : 'wierszy')}`"></span>
            </div>

            <div class="d-flex justify-content-end align-items-center gap-2">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.tab.entries') }}"
                    action="cancel"
                >
                    Anuluj
                </x-ui.button>
                <x-ui.button 
                    variant="primary" 
                    type="submit"
                    action="save"
                >
                    Zapisz wszystkie
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @push('scripts')
    <script>
        function entriesGrid() {
            const today = new Date().toISOString().split('T')[0];
            let nextId = 1;

            function makeRow() {
                return { id: nextId++, accounting_date: today, name: '', amount: '', currency: 'PLN', category: '', notes: '' };
            }

            return {
                periodStart: today,
                periodEnd: today,
                rows: [makeRow()],
                addRow() {
                    this.rows.push(makeRow());
                },
                removeRow(id) {
                    if (this.rows.length > 1) {
                        this.rows = this.rows.filter(r => r.id !== id);
                    }
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
