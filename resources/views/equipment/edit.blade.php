<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj: {{ $equipment->name }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @include('equipment._warehouse-switcher', [
                    'warehouses' => $warehouses,
                    'current' => $warehouse,
                    'routeName' => 'equipment.edit',
                    'routeParams' => $equipment,
                ])
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj pozycję magazynową">
                <livewire:equipment-form :equipment="$equipment" :warehouse="$warehouse" :key="'eq-form-'.$equipment->id.'-'.$warehouse->id" />
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
