<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Stawki Pracowników</h2>
            <x-ui.button variant="primary" href="{{ route('employee-rates.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Stawkę
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <livewire:employee-rates-table />
        </div>
    </div>
</x-app-layout>
