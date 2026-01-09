<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Pojazdy</h2>
            <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Pojazd
            </a>
        </div>
    </x-slot>

    <livewire:vehicles-table />
</x-app-layout>
