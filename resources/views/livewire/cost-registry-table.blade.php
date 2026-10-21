<div
    class="cr-root"
    x-data="{ savedId: null, savedTimer: null }"
    x-on:cost-registry-saved.window="
        savedId = $event.detail.id;
        clearTimeout(savedTimer);
        savedTimer = setTimeout(() => savedId = null, 1400);
    "
>
    @php
        $companyLabels = $companies->pluck('name', 'id');
        $companyNone = \App\Livewire\CostRegistryTable::COMPANY_NONE;
    @endphp

    {{-- Pasek KPI dla bieżącego filtra. Kafelki „Bez spółki” / „Nieistotne” są skrótami do filtra. --}}
    <div class="cr-kpis mb-2">
        <div class="cr-kpi cr-kpi--sum" title="Suma pozycji istotnych finansowo w bieżącym filtrze, per waluta.">
            <span class="cr-kpi__label">Suma</span>
            <span class="cr-kpi__value">
                @forelse($summary['totals'] as $currency => $total)
                    <span class="font-mono">{{ number_format($total, 2, ',', ' ') }}<small>{{ $currency }}</small></span>
                @empty
                    <span class="font-mono text-muted">—</span>
                @endforelse
            </span>
        </div>

        <div class="cr-kpi">
            <span class="cr-kpi__label">Pozycje</span>
            <span class="cr-kpi__value font-mono">{{ $summary['count'] }}</span>
        </div>

        <button
            type="button"
            class="cr-kpi cr-kpi--btn {{ $summary['without_company'] > 0 ? 'cr-kpi--warn' : '' }} {{ $companyFilter === $companyNone ? 'cr-kpi--active' : '' }}"
            wire:click="$set('companyFilter', '{{ $companyFilter === $companyNone ? '' : $companyNone }}')"
            title="Pozycje bez przypisanej spółki — kliknij, aby je wyfiltrować"
        >
            <span class="cr-kpi__label">Bez spółki</span>
            <span class="cr-kpi__value font-mono">{{ $summary['without_company'] }}</span>
        </button>

        <button
            type="button"
            class="cr-kpi cr-kpi--btn {{ $irrelevantFilter === 'yes' ? 'cr-kpi--active' : '' }}"
            wire:click="$set('irrelevantFilter', '{{ $irrelevantFilter === 'yes' ? '' : 'yes' }}')"
            title="Pozycje oznaczone jako nieistotne finansowo (nie wchodzą do sumy)"
        >
            <span class="cr-kpi__label">Nieistotne</span>
            <span class="cr-kpi__value font-mono">{{ $summary['irrelevant'] }}</span>
        </button>
    </div>

    <x-data-table :paginator="$entries" :has-filters="$hasFilters" class="cr-table">
        <x-slot:filters>
            <x-data-table-filters :count="$entries->total()">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    class="form-control form-control-sm cr-filter--search"
                    placeholder="Szukaj: nazwa, komentarz…"
                    autocomplete="off"
                    aria-label="Szukaj"
                >

                <select wire:model.live="sourceTypeFilter" class="form-select form-select-sm" aria-label="Rodzaj">
                    <option value="">Rodzaj</option>
                    @foreach($sourceTypeOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="companyFilter" class="form-select form-select-sm" aria-label="Spółka">
                    <option value="">Spółka</option>
                    <option value="{{ $companyNone }}">— bez spółki —</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="yearFilter" class="form-select form-select-sm cr-filter--narrow" aria-label="Rok">
                    <option value="">Rok</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>

                <select wire:model.live="monthFilter" class="form-select form-select-sm" aria-label="Miesiąc">
                    <option value="">Miesiąc</option>
                    @foreach($monthOptions as $num => $label)
                        <option value="{{ $num }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="irrelevantFilter" class="form-select form-select-sm" aria-label="Istotność">
                    <option value="">Istotność</option>
                    <option value="no">Tylko istotne</option>
                    <option value="yes">Tylko nieistotne</option>
                </select>

                <select wire:model.live="currencyFilter" class="form-select form-select-sm cr-filter--narrow" aria-label="Waluta">
                    <option value="">Waluta</option>
                    @foreach(['PLN', 'EUR', 'USD', 'GBP', 'CZK', 'NOK', 'SEK', 'DKK', 'CHF'] as $cur)
                        <option value="{{ $cur }}">{{ $cur }}</option>
                    @endforeach
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search !== '')
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($sourceTypeFilter !== '')
                <x-data-table-filter-chip label="Rodzaj: {{ $sourceTypeOptions[$sourceTypeFilter] ?? $sourceTypeFilter }}" wire:click="$set('sourceTypeFilter', '')" />
            @endif
            @if($companyFilter !== '')
                <x-data-table-filter-chip
                    label="Spółka: {{ $companyFilter === $companyNone ? 'bez spółki' : ($companyLabels[(int) $companyFilter] ?? $companyFilter) }}"
                    wire:click="$set('companyFilter', '')"
                />
            @endif
            @if($yearFilter !== '')
                <x-data-table-filter-chip label="Rok: {{ $yearFilter }}" wire:click="$set('yearFilter', '')" />
            @endif
            @if($monthFilter !== '')
                <x-data-table-filter-chip label="Miesiąc: {{ $monthOptions[(int) $monthFilter] ?? $monthFilter }}" wire:click="$set('monthFilter', '')" />
            @endif
            @if($irrelevantFilter !== '')
                <x-data-table-filter-chip label="{{ $irrelevantFilter === 'yes' ? 'Tylko nieistotne' : 'Tylko istotne' }}" wire:click="$set('irrelevantFilter', '')" />
            @endif
            @if($currencyFilter !== '')
                <x-data-table-filter-chip label="Waluta: {{ strtoupper($currencyFilter) }}" wire:click="$set('currencyFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                <x-livewire.sortable-header field="source_type" :sortField="$sortField" :sortDirection="$sortDirection">Rodzaj</x-livewire.sortable-header>
                <x-livewire.sortable-header field="company" :sortField="$sortField" :sortDirection="$sortDirection">Spółka</x-livewire.sortable-header>
                <x-livewire.sortable-header field="period" :sortField="$sortField" :sortDirection="$sortDirection">Miesiąc rozl.</x-livewire.sortable-header>
                <th class="text-start">Data</th>
                <th class="text-end">Kwota</th>
                <th class="text-center" title="Istotny finansowo — wyłączony nie wchodzi do sumy">Istotny</th>
                <th class="text-start">Komentarz</th>
                <th class="text-end"><span class="visually-hidden">Źródło</span></th>
            </tr>
        </x-slot:head>

        <x-slot:body>
            @foreach($entries as $entry)
                @include('livewire.partials.cost-entry-row', ['entry' => $entry, 'canUpdate' => $canUpdate])
            @endforeach
        </x-slot:body>

        <x-slot:cards>
            @foreach($entries as $entry)
                @include('livewire.partials.cost-entry-row-card', ['entry' => $entry, 'canUpdate' => $canUpdate])
            @endforeach
        </x-slot:cards>

        <x-slot:empty>
            <x-ui.empty-state
                icon="receipt"
                :message="$hasFilters ? 'Brak kosztów spełniających kryteria.' : 'Rejestr jest pusty. Dodaj koszty przyciskiem „Dodaj wiele” albo na kartach: ogólnofirmowe, projektowe, transport, serwis.'"
                :has-filters="$hasFilters"
                clear-filters-action="wire:clearFilters"
            />
        </x-slot:empty>
    </x-data-table>

    @if($canUpdate && $entries->count() > 0)
        <p class="cr-hint text-muted small mt-2 mb-0">
            <i class="bi bi-pencil-square me-1"></i>
            Nazwa, spółka, miesiąc i komentarz zapisują się od razu po zmianie w wierszu. Kwoty i daty edytujesz na karcie źródłowej (ikona <i class="bi bi-box-arrow-up-right"></i>).
        </p>
    @endif

    {{-- Vite bywa wyłączony — style krytyczne dla czytelności lokalnie. --}}
    <style>
        /* KPI */
        .cr-kpis {
            display: grid;
            grid-template-columns: minmax(0, 2fr) repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
        }
        @media (max-width: 767.98px) {
            .cr-kpis { grid-template-columns: 1fr 1fr; }
            .cr-kpi--sum { grid-column: 1 / -1; }
        }
        .cr-kpi {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
            padding: 0.6rem 0.9rem;
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-main);
            text-align: left;
        }
        .cr-kpi--btn { cursor: pointer; transition: border-color 0.15s ease, background 0.15s ease; }
        .cr-kpi--btn:hover { border-color: rgba(96, 165, 250, 0.45); background: rgba(255, 255, 255, 0.05); }
        .cr-kpi--active { border-color: var(--primary); background: rgba(59, 130, 246, 0.12); }
        .cr-kpi--warn .cr-kpi__value { color: #fbbf24; }
        .cr-kpi__label {
            font-size: 0.66rem;
            font-weight: 650;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .cr-kpi__value {
            display: flex;
            flex-wrap: wrap;
            gap: 0.2rem 0.9rem;
            font-size: 1.15rem;
            font-weight: 600;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }
        .cr-kpi__value small {
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-left: 0.3rem;
            letter-spacing: 0.04em;
        }

        /* Toolbar: pola wypełniają rząd i zawijają się zamiast poziomego scrolla */
        .cr-root .dt-toolbar__fields { flex-wrap: wrap; overflow: visible; }
        .cr-root .dt-toolbar__fields .form-control,
        .cr-root .dt-toolbar__fields .form-select { flex: 1 1 7.5rem; min-width: 7.5rem; max-width: none; }
        .cr-root .dt-toolbar__fields .cr-filter--search { flex: 2 1 12rem; min-width: 12rem; }
        .cr-root .dt-toolbar__fields .cr-filter--narrow { flex: 0 1 6rem; min-width: 6rem; }

        /* Tabela */
        .cr-table td { vertical-align: middle; }
        .cr-table td, .cr-table th { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        .cr-table th.text-start, .cr-table th.text-end, .cr-table th.text-center { white-space: nowrap; }
        .cr-row { transition: background 0.4s ease; }
        .cr-row--saved { background: rgba(34, 197, 94, 0.12) !important; }
        .cr-row--irrelevant .cr-dim { opacity: 0.45; }
        .cr-row--irrelevant .cr-amount { text-decoration: line-through; }
        .cr-amount { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .cr-context { font-size: 0.72rem; color: var(--text-muted); white-space: nowrap; max-width: 14rem; overflow: hidden; text-overflow: ellipsis; }
        .cr-date { white-space: nowrap; font-size: 0.82rem; color: var(--text-muted); }

        /* Pola inline: wyglądają jak tekst, ramka pojawia się na hover/focus */
        .cr-inline.form-control, .cr-inline.form-select {
            background: transparent !important;
            border-color: transparent !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            transition: border-color 0.15s ease, background 0.15s ease;
        }
        .cr-inline.form-select { padding-right: 1.6rem !important; background-position: right 0.4rem center !important; }
        .cr-table tr:hover .cr-inline.form-control,
        .cr-table tr:hover .cr-inline.form-select { border-color: var(--glass-border) !important; }
        .cr-inline.form-control:focus, .cr-inline.form-select:focus {
            background: var(--bg-input) !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        }
        .cr-inline.is-invalid { border-color: #ef4444 !important; }
        .cr-inline:disabled { opacity: 1; color: var(--text-main) !important; -webkit-text-fill-color: var(--text-main) !important; cursor: default; }
        .cr-inline--name { min-width: 12rem; font-weight: 600; }
        .cr-inline--company { min-width: 8rem; }
        .cr-inline--period { min-width: 9rem; }
        .cr-inline--comment { min-width: 9rem; font-size: 0.8rem !important; color: var(--text-muted) !important; -webkit-text-fill-color: var(--text-muted) !important; }
        .cr-inline--comment:focus { color: var(--text-main) !important; -webkit-text-fill-color: var(--text-main) !important; }
        .cr-inline-error { display: block; color: #f87171; font-size: 0.7rem; margin-top: 0.15rem; }

        /* Toggle istotności */
        .cr-toggle {
            width: 2.1rem; height: 1.2rem;
            border-radius: 999px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.08);
            position: relative;
            padding: 0;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .cr-toggle::after {
            content: '';
            position: absolute; top: 2px; left: 2px;
            width: calc(1.2rem - 6px); height: calc(1.2rem - 6px);
            border-radius: 50%;
            background: var(--text-muted);
            transition: transform 0.15s ease, background 0.15s ease;
        }
        .cr-toggle--on { background: rgba(34, 197, 94, 0.25); border-color: rgba(34, 197, 94, 0.5); }
        .cr-toggle--on::after { transform: translateX(0.9rem); background: #22c55e; }
        .cr-toggle:disabled { cursor: default; opacity: 0.7; }
        .cr-source-link { color: var(--text-muted); }
        .cr-source-link:hover { color: var(--text-main); }

        /* Karty mobilne */
        .dt-card.cr-card--irrelevant { opacity: 0.65; }
        .cr-card .cr-inline.form-control, .cr-card .cr-inline.form-select { border-color: var(--glass-border) !important; }
    </style>
</div>
