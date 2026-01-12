<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Pojazdy</h2>
            <x-ui.button variant="primary" href="{{ route('vehicles.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Pojazd
            </x-ui.button>
        </div>
    </x-slot>

    <livewire:vehicles-table />
</x-app-layout>
