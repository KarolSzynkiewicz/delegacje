<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyny">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('warehouses.create') }}"
                    routeName="warehouses.create"
                    action="create"
                >
                    Dodaj magazyn
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

    <x-ui.card class="p-0">
        @if($warehouses->count() > 0)
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="padding-left:1rem;">Magazyn</th>
                            <th>Lokalizacja</th>
                            <th></th>
                            <th style="width:14rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($warehouses as $item)
                            <tr>
                                <td style="padding-left:1rem;" class="fw-semibold">{{ $item->name }}</td>
                                <td>{{ $item->location?->name ?? '—' }}</td>
                                <td>
                                    @if($item->is_default)
                                        <x-ui.badge variant="success">Siedziba</x-ui.badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <a
                                            href="{{ route('equipment-issues.create', ['warehouse_id' => $item->id]) }}"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Wydaj
                                        </a>
                                        <a
                                            href="{{ route('equipment-consumptions.create', ['warehouse_id' => $item->id]) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Rozchód
                                        </a>
                                        <x-action-buttons
                                            resource="equipment"
                                            viewRoute="{{ route('equipment.tab.stock', ['warehouse_id' => $item->id]) }}"
                                            editRoute="{{ route('warehouses.edit', $item) }}"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4">
                <x-ui.empty-state
                    icon="inbox"
                    message="Brak magazynów."
                >
                    <x-ui.button
                        variant="primary"
                        href="{{ route('warehouses.create') }}"
                        routeName="warehouses.create"
                        action="create"
                    >
                        Dodaj pierwszy magazyn
                    </x-ui.button>
                </x-ui.empty-state>
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
