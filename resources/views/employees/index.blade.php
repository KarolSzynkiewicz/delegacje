<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Pracownicy</h2>
            <x-ui.button variant="primary" href="{{ route('employees.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Pracownika
            </x-ui.button>
        </div>
    </x-slot>

    <livewire:employees-table />
</x-app-layout>
