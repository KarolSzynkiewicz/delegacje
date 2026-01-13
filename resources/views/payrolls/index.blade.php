<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Payroll</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="primary" href="{{ route('payrolls.generate-batch') }}">
                    <i class="bi bi-gear"></i> Generuj Payroll
                </x-ui.button>
                <form action="{{ route('payrolls.recalculate-all') }}" method="POST" class="d-inline">
                    @csrf
                    <x-ui.button variant="warning" type="submit" onclick="return confirm('Czy na pewno chcesz przeliczyć wszystkie payrolle?');">
                        <i class="bi bi-arrow-clockwise"></i> Przelicz Wszystkie
                    </x-ui.button>
                </form>
                <x-ui.button variant="secondary" href="{{ route('payrolls.create') }}">
                    <i class="bi bi-plus-circle"></i> Nowy Payroll
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <livewire:payrolls-table />
        </div>
    </div>
</x-app-layout>
