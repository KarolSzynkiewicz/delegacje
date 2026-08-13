<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj do magazynu">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.index', ['warehouse_id' => $warehouse->id]) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @include('equipment._warehouse-switcher', [
                    'warehouses' => $warehouses,
                    'current' => $warehouse,
                    'routeName' => 'equipment.create',
                ])
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Nowa pozycja magazynowa">
                <livewire:equipment-form :warehouse="$warehouse" :key="'eq-form-'.$warehouse->id" />
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
