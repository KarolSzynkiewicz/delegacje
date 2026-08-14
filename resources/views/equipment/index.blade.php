<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyn">
            <x-slot name="right">
                @if(! ($archived ?? false))
                    <x-ui.button
                        variant="primary"
                        href="{{ route('equipment.create', ['warehouse_id' => $warehouse->id]) }}"
                        routeName="equipment.create"
                        action="create"
                    >
                        Dodaj do magazynu
                    </x-ui.button>
                @endif
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
        $archived = $archived ?? false;
        $tabRoute = $archived ? 'equipment.tab.archived' : 'equipment.tab.stock';
    @endphp
    <x-ui.card class="mb-4">
        @include('equipment._tabs', ['activeTab' => $activeTab ?? 'stock'])
        <form method="GET" action="{{ route($tabRoute) }}" id="filter-form" class="js-auto-submit">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-buildings me-1"></i> Magazyn
                    </label>
                    <select name="warehouse_id" class="form-control" onchange="this.form.submit()">
                        @foreach($warehouses as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $warehouse->id)>
                                {{ $option->display_name }}{{ $option->is_default ? ' — siedziba' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Nazwa typu lub rodzaj..."
                        class="form-control js-debounced"
                        autocomplete="off">
                </div>

                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-tags me-1"></i> Kategoria
                    </label>
                    <input type="search" name="category" value="{{ request('category') }}"
                        placeholder="Szukaj kategorii…"
                        class="form-control js-debounced"
                        autocomplete="off">
                </div>
            </div>
        </form>

        @if(request('search') || request('category'))
            <div class="mt-3 pt-3 border-top">
                <a href="{{ route($tabRoute, ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </a>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card class="p-0">
        @if($sections->isNotEmpty())
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="border-collapse:collapse;">
                    <thead>
                        <tr style="font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">
                            <th style="padding-left:1rem;border-bottom:0;">Typ / rodzaj</th>
                            <th class="text-end" style="border-bottom:0;width:7.5rem;">W magazynie</th>
                            <th class="text-end" style="border-bottom:0;width:9.5rem;">W innych magazynach</th>
                            <th class="text-end" style="border-bottom:0;width:8.5rem;">Do zwrotu tutaj</th>
                            <th class="text-end" style="border-bottom:0;width:10.5rem;">Do zwrotu w innych magazynach</th>
                            <th style="border-bottom:0;width:7rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <td colspan="6" style="padding:.85rem 1rem .35rem;background:rgba(255,255,255,.04);border-top:2px solid var(--glass-border);">
                                    <span style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);">
                                        {{ $section['title'] }}
                                    </span>
                                </td>
                            </tr>
                            @foreach ($section['groups'] as $group)
                                @if($group['title'])
                                    <tr>
                                        <td colspan="6" style="padding:.5rem 1rem .2rem;border-top:0;">
                                            <span style="font-size:.67rem;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);">
                                                {{ $group['title'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($group['items'] as $item)
                                    @include('equipment._stock-item', [
                                        'item' => $item,
                                        'warehouse' => $warehouse,
                                        'archived' => $archived,
                                    ])
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4">
            <x-ui.empty-state
                icon="inbox"
                :message="request('search') || request('category')
                    ? 'Brak pozycji spełniających kryteria wyszukiwania'
                    : ($archived ? 'Brak asortymentu historycznego.' : 'Magazyn jest pusty.')"
            >
                @if(!request('search') && !request('category'))
                    @if(! $archived)
                        <x-ui.button
                            variant="primary"
                            href="{{ route('equipment.create', ['warehouse_id' => $warehouse->id]) }}"
                            routeName="equipment.create"
                            action="create"
                        >
                            Dodaj pierwszą pozycję
                        </x-ui.button>
                    @endif
                @else
                    <a href="{{ route($tabRoute, ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </a>
                @endif
            </x-ui.empty-state>
            </div>
        @endif
    </x-ui.card>

    @include('equipment._filter-debounce')
</x-app-layout>
