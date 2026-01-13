<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Payroll</h2>
            <x-ui.button variant="primary" href="{{ route('payrolls.create') }}">
                <i class="bi bi-plus-circle"></i> Wygeneruj Payroll
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <livewire:payrolls-table />
        </div>
    </div>
</x-app-layout>
