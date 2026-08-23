<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyn — {{ $warehouse->name }}">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('equipment-issues.create', ['warehouse_id' => $warehouse->id]) }}"
                    routeName="equipment-issues.create"
                    action="create"
                >
                    Zleć wydanie
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @include('equipment._warehouse-cards', [
        'warehouses' => $warehouses,
        'current' => $warehouse,
        'counts' => $warehouseCounts,
        'routeName' => 'equipment.tab.orders',
    ])

    @include('equipment._tabs', ['activeTab' => $activeTab ?? 'orders'])

    @include('equipment._pending-dispatches', [
        'pendingDispatches' => $pendingDispatches,
        'warehouse' => $warehouse,
    ])
</x-app-layout>
