<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Rozchód z magazynu">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.tab.issues') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @include('equipment._warehouse-switcher', [
                    'warehouses' => $warehouses,
                    'current' => $warehouse,
                    'routeName' => 'equipment-consumptions.create',
                ])
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card label="Rozchód: {{ $warehouse->display_name }}">
        <livewire:warehouse-consume-form :warehouse="$warehouse" :key="'consume-form-'.$warehouse->id" />
    </x-ui.card>
</x-app-layout>
