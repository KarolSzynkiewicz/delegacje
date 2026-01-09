<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Pracownicy</h2>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Pracownika
            </a>
        </div>
    </x-slot>

    <livewire:employees-table />
</x-app-layout>
