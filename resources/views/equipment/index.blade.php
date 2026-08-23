<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyn — {{ $warehouse->name }}">
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment-issues.create', ['warehouse_id' => $warehouse->id]) }}"
                    routeName="equipment-issues.create"
                    action="create"
                >
                    Zleć wydanie
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    href="{{ route('equipment.create', ['warehouse_id' => $warehouse->id]) }}"
                    routeName="equipment.create"
                    action="create"
                >
                    Dodaj do magazynu
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    @php
        $showWithdrawn = $showWithdrawn ?? false;
        $filterParams = array_filter([
            'warehouse_id' => $warehouse->id,
            'withdrawn' => $showWithdrawn ? 1 : null,
        ]);
        $cardKeep = array_filter([
            'search' => request('search'),
            'category' => request('category'),
            'withdrawn' => $showWithdrawn ? 1 : null,
        ]);
    @endphp

    @include('equipment._warehouse-cards', [
        'warehouses' => $warehouses,
        'current' => $warehouse,
        'counts' => $warehouseCounts,
        'routeName' => 'equipment.tab.stock',
        'keep' => $cardKeep,
    ])

    @include('equipment._tabs', ['activeTab' => $activeTab ?? 'stock'])

    <x-ui.card class="mb-4">
        <form method="GET" action="{{ route('equipment.tab.stock') }}" id="filter-form" class="js-auto-submit eq-stock-filters">
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <div class="eq-stock-filters__grid">
                <div>
                    <label class="form-label small" for="stock-filter-search">
                        <i class="bi bi-search" aria-hidden="true"></i> Szukaj
                    </label>
                    <input
                        id="stock-filter-search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Nazwa typu lub rodzaj…"
                        class="form-control js-debounced"
                        autocomplete="off"
                    >
                </div>
                <div>
                    <label class="form-label small" for="stock-filter-category">
                        <i class="bi bi-tag" aria-hidden="true"></i> Kategoria
                    </label>
                    <select
                        id="stock-filter-category"
                        name="category"
                        class="form-select"
                        onchange="this.form.submit()"
                    >
                        <option value="">Wszystkie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="eq-stock-filters__check">
                    <div class="form-check mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="withdrawn"
                            value="1"
                            id="show-withdrawn"
                            @checked($showWithdrawn)
                            onchange="this.form.submit()"
                        >
                        <label class="form-check-label" for="show-withdrawn" title="Pozycje, których już nie ewidencjonujemy — kiedyś były w asortymencie i mogły być wydawane.">
                            Pokaż wycofane
                        </label>
                    </div>
                </div>
            </div>
        </form>

        @if(request('search') || request('category'))
            <div class="eq-stock-filters__clear">
                <a href="{{ route('equipment.tab.stock', $filterParams) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </a>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card class="p-0">
        @if($sections->isNotEmpty())
            <div class="table-responsive d-none d-md-block">
                <table class="table mb-0 align-middle eq-stock-table">
                    <thead>
                        <tr>
                            <th>Typ / rodzaj</th>
                            <th class="text-end">W magazynie</th>
                            <th class="text-end">Zarezerwowane</th>
                            <th class="text-end">W innych magazynach</th>
                            <th class="text-end">Do zwrotu tutaj</th>
                            <th class="text-end">Do zwrotu w innych magazynach</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr class="eq-stock-section">
                                <td colspan="7">
                                    <span class="eq-stock-section__label">{{ $section['title'] }}</span>
                                </td>
                            </tr>
                            @foreach ($section['groups'] as $group)
                                @if($group['title'])
                                    <tr class="eq-stock-group">
                                        <td colspan="7">
                                            <span class="eq-stock-group__label">{{ $group['title'] }}</span>
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($group['items'] as $item)
                                    @include('equipment._stock-item', [
                                        'item' => $item,
                                        'warehouse' => $warehouse,
                                    ])
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="eq-stock-cards d-md-none">
                @foreach ($sections as $section)
                    <div class="eq-stock-cards__section">{{ $section['title'] }}</div>
                    @foreach ($section['groups'] as $group)
                        @if($group['title'])
                            <div class="eq-stock-cards__group">{{ $group['title'] }}</div>
                        @endif
                        @foreach ($group['items'] as $item)
                            @include('equipment._stock-item-card', [
                                'item' => $item,
                                'warehouse' => $warehouse,
                            ])
                        @endforeach
                    @endforeach
                @endforeach
            </div>
        @else
            <div class="p-4">
            <x-ui.empty-state
                icon="inbox"
                :message="request('search') || request('category')
                    ? 'Brak pozycji spełniających kryteria wyszukiwania'
                    : ($showWithdrawn ? 'Brak pozycji w asortymencie.' : 'Asortyment jest pusty.')"
            >
                @if(!request('search') && !request('category'))
                    <x-ui.button
                        variant="primary"
                        href="{{ route('equipment.create', ['warehouse_id' => $warehouse->id]) }}"
                        routeName="equipment.create"
                        action="create"
                    >
                        Dodaj pierwszą pozycję
                    </x-ui.button>
                @else
                    <a href="{{ route('equipment.tab.stock', $filterParams) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </a>
                @endif
            </x-ui.empty-state>
            </div>
        @endif
    </x-ui.card>

    @include('equipment._filter-debounce')

    @push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-eq-stock-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.getAttribute('data-eq-stock-toggle');
                    const open = button.getAttribute('aria-expanded') !== 'true';
                    document.querySelectorAll('[data-eq-stock-toggle="' + id + '"]').forEach((toggle) => {
                        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                        toggle.closest('.eq-stock-item, .eq-stock-card')?.classList.toggle('is-open', open);
                    });
                    document.querySelectorAll('[data-eq-stock-parent="' + id + '"]').forEach((row) => {
                        row.hidden = !open;
                    });
                });
            });
        })();
    </script>
    @endpush
</x-app-layout>
