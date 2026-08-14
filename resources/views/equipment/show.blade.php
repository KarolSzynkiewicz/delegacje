<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ $equipment->name }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route($equipment->isArchived() ? 'equipment.tab.archived' : 'equipment.index', ['warehouse_id' => $warehouse->id]) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @include('equipment._warehouse-switcher', [
                    'warehouses' => $warehouses,
                    'current' => $warehouse,
                    'routeName' => 'equipment.show',
                    'routeParams' => $equipment,
                ])
                @if($equipment->isArchived())
                    <form
                        action="{{ route('equipment.restore', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Przywrócić tę pozycję do bieżącego asortymentu?')"
                    >
                        @csrf
                        <x-ui.button variant="primary" type="submit">
                            Przywróć
                        </x-ui.button>
                    </form>
                @else
                    <x-ui.button
                        variant="ghost"
                        href="{{ route('equipment.edit', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                        routeName="equipment.edit"
                        action="edit"
                    >
                        Edytuj
                    </x-ui.button>
                    <form
                        action="{{ route('equipment.destroy', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Przenieść tę pozycję do asortymentu historycznego? Stan i historia wydań zostaną zachowane.')"
                    >
                        @csrf
                        @method('DELETE')
                        <x-ui.button variant="danger" type="submit">
                            Do historii
                        </x-ui.button>
                    </form>
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

    <x-ui.card label="Informacje podstawowe" class="mb-3">
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Typ</h6>
                <p class="fw-semibold mb-0">{{ $equipment->name }}</p>
                @if($equipment->isArchived())
                    <div class="mt-1">
                        <x-ui.badge variant="warning">Asortyment historyczny</x-ui.badge>
                        @if($equipment->removed_at)
                            <span class="small text-muted ms-1">od {{ $equipment->removed_at->format('Y-m-d') }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Kategoria</h6>
                <p class="fw-semibold">{{ $equipment->category ?? '-' }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Wydawalność</h6>
                <p class="fw-semibold">{{ $equipment->issuable ? 'Wydawalny pracownikom' : 'Niewydawalny' }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Zwrot</h6>
                <p class="fw-semibold">
                    @if(! $equipment->issuable)
                        Nie dotyczy
                    @elseif($equipment->returnable)
                        Zwracalny
                    @else
                        Niezwracalny
                    @endif
                </p>
            </div>
            @if($equipment->hasVariants())
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Wariant</h6>
                <p class="fw-semibold">{{ $equipment->variant_label ?? '-' }}</p>
            </div>
            @endif
            @if($equipment->unit_cost)
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Koszt jednostkowy</h6>
                <p class="fw-semibold">{{ number_format($equipment->unit_cost, 2) }} {{ $equipment->currency?->value ?? 'PLN' }}</p>
            </div>
            @endif
            @if($equipment->description)
            <div class="col-12">
                <h6 class="text-muted small mb-1">Opis</h6>
                <p>{{ $equipment->description }}</p>
            </div>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card label="Stan w magazynie: {{ $warehouse->display_name }}" class="mb-3">
        @if($equipment->variants->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            @if($equipment->hasVariants())
                                <th>{{ $equipment->variant_label ?: 'Wariant' }}</th>
                            @endif
                            <th class="text-end">W magazynie</th>
                            <th class="text-end">W innych magazynach</th>
                            <th class="text-end">Do zwrotu tutaj</th>
                            <th class="text-end">Do zwrotu w innych magazynach</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipment->variants as $variant)
                            <tr>
                                @if($equipment->hasVariants())
                                    <td>{{ $variant->kind_label }}</td>
                                @endif
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $variant->quantityIn($warehouse) }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $variant->quantityInOthers($warehouse) }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $variant->issuedOutstandingIn($warehouse) }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $variant->issuedOutstandingInOthers($warehouse) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Brak pozycji magazynowej</p>
        @endif
    </x-ui.card>

    <x-ui.card label="Wymagania dla ról" class="mb-3">
        @if($equipment->requirements->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Rola</th>
                            <th>Wymagana ilość</th>
                            <th>Obowiązkowe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipment->requirements as $requirement)
                            <tr>
                                <td>{{ $requirement->role->name }}</td>
                                <td>{{ $requirement->required_quantity }}</td>
                                <td>
                                    @if($requirement->is_mandatory)
                                        <x-ui.badge variant="danger">Tak</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="accent">Nie</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Brak wymagań</p>
        @endif
    </x-ui.card>

    <x-ui.card label="Ostatnie wydania z tego magazynu">
        @if($equipment->issuable && $equipment->issues->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Pracownik</th>
                            <th>Rodzaj</th>
                            <th>Ilość</th>
                            <th>Data wydania</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipment->issues->take(10) as $issue)
                            <tr>
                                <td>
                                    <x-employee-cell :employee="$issue->employee" />
                                </td>
                                <td>{{ $issue->variant?->kind_label ?? '—' }}</td>
                                <td>{{ $issue->quantity_issued }}</td>
                                <td>{{ $issue->issue_date->format('Y-m-d') }}</td>
                                <td>
                                    @php
                                        $badgeVariant = match($issue->status) {
                                            'issued' => 'primary',
                                            'returned' => 'success',
                                            default => 'accent'
                                        };
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($issue->status) }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif(! $equipment->issuable)
            <p class="text-muted mb-0">Ta pozycja nie jest wydawana pracownikom.</p>
        @else
            <p class="text-muted mb-0">Brak wydań</p>
        @endif
    </x-ui.card>
</x-app-layout>
