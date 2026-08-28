<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyn">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    type="button"
                    action="create"
                    onclick="Livewire.dispatch('open-warehouse-create')"
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

    @include('equipment._tabs', ['activeTab' => $activeTab ?? 'warehouses'])

    <livewire:equipment-warehouse-manager />
</x-app-layout>
