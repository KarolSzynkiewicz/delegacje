<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyn">
            <x-slot name="right">
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

    <x-ui.card class="mb-4">
        @include('equipment._tabs', ['activeTab' => $activeTab ?? 'stock'])
        <form method="GET" action="{{ route('equipment.tab.stock') }}" id="filter-form" class="js-auto-submit">
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
                <a href="{{ route('equipment.tab.stock', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </a>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card class="p-0">
        @if($equipment->count() > 0)
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
                        @foreach ($equipment as $item)
                            @php
                                $hasVariants = $item->hasVariants();
                            @endphp
                            <tr style="background:rgba(255,255,255,.025);border-top:2px solid var(--glass-border);">
                                <td style="padding:.65rem 1rem;">
                                    <div class="d-flex align-items-baseline flex-wrap gap-2">
                                        <span class="fw-semibold" style="font-size:.92rem;">{{ $item->name }}</span>
                                        @if($item->description)
                                            <span style="font-size:.82rem;color:var(--text-muted);">{{ \Illuminate\Support\Str::limit($item->description, 90) }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        @if($item->category)
                                            <span class="badge badge-secondary" style="font-size:.62rem;">{{ $item->category }}</span>
                                        @endif
                                        @if($item->variant_label)
                                            <span style="font-size:.72rem;color:var(--text-muted);">{{ $item->variant_label }}</span>
                                        @endif
                                        @if($item->issuable)
                                            <span class="badge" style="font-size:.62rem;background:rgba(120,180,255,.18);color:#c9ddff;">Wydawalny</span>
                                        @else
                                            <span class="badge" style="font-size:.62rem;background:rgba(255,255,255,.08);color:var(--text-muted);">Niewydawalny</span>
                                        @endif
                                        @if($item->issuable)
                                            @if($item->returnable)
                                                <span class="badge" style="font-size:.62rem;background:rgba(120,220,160,.16);color:#c8f0d8;">Zwracalny</span>
                                            @else
                                                <span class="badge" style="font-size:.62rem;background:rgba(255,255,255,.08);color:var(--text-muted);">Niezwracalny</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                @include('equipment._qty-cell', ['value' => $item->quantityIn($warehouse)])
                                @include('equipment._qty-cell', ['value' => $item->quantityInOthers($warehouse)])
                                @include('equipment._qty-cell', ['value' => $item->issuedOutstandingIn($warehouse)])
                                @include('equipment._qty-cell', ['value' => $item->issuedOutstandingInOthers($warehouse)])
                                <td style="padding:.65rem .75rem;">
                                    <x-action-buttons
                                        viewRoute="{{ route('equipment.show', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                                        editRoute="{{ route('equipment.edit', ['equipment' => $item, 'warehouse_id' => $warehouse->id]) }}"
                                        deleteRoute="{{ route('equipment.destroy', $item) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć tę pozycję magazynową?"
                                    />
                                </td>
                            </tr>

                            @if($hasVariants)
                                @forelse ($item->variants as $variant)
                                    <tr style="font-size:.82rem;">
                                        <td style="padding:.45rem 1rem .45rem 1.75rem;color:var(--text-muted);white-space:nowrap;">
                                            <i class="bi bi-arrow-return-right me-1" style="font-size:.65rem;"></i>
                                            {{ $variant->kind_label }}
                                        </td>
                                        @include('equipment._qty-cell', [
                                            'value' => $variant->quantityIn($warehouse),
                                            'compact' => true,
                                        ])
                                        @include('equipment._qty-cell', [
                                            'value' => $variant->quantityInOthers($warehouse),
                                            'compact' => true,
                                        ])
                                        @include('equipment._qty-cell', [
                                            'value' => $variant->issuedOutstandingIn($warehouse),
                                            'compact' => true,
                                        ])
                                        @include('equipment._qty-cell', [
                                            'value' => $variant->issuedOutstandingInOthers($warehouse),
                                            'compact' => true,
                                        ])
                                        <td></td>
                                    </tr>
                                @empty
                                    <tr style="font-size:.82rem;">
                                        <td colspan="6" style="padding:.45rem 1rem .45rem 1.75rem;color:var(--text-muted);">
                                            <i class="bi bi-arrow-return-right me-1" style="font-size:.65rem;"></i>
                                            Brak wariantów
                                        </td>
                                    </tr>
                                @endforelse
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($equipment->hasPages())
                <div class="px-3 py-2" style="border-top:1px solid var(--glass-border);">
                    <x-ui.pagination :paginator="$equipment" />
                </div>
            @endif
        @else
            <div class="p-4">
            <x-ui.empty-state
                icon="inbox"
                :message="request('search') || request('category') ? 'Brak pozycji spełniających kryteria wyszukiwania' : 'Magazyn jest pusty.'"
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
                    <a href="{{ route('equipment.tab.stock', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </a>
                @endif
            </x-ui.empty-state>
            </div>
        @endif
    </x-ui.card>

    @include('equipment._filter-debounce')
</x-app-layout>
