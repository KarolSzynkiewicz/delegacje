<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">
                Wszystkie Rotacje Pracowników
            </h2>
            <a href="{{ route('rotations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Rotację
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <livewire:rotations-table />
        </div>
    </div>
</x-app-layout>
