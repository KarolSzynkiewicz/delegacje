<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Ewidencja Godzin</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="ghost" href="{{ route('time-logs.monthly-grid') }}">
                    <i class="bi bi-calendar-month"></i> Widok Miesięczny
                </x-ui.button>
                <x-ui.button variant="primary" href="{{ route('time-logs.create') }}">
                    <i class="bi bi-plus-circle"></i> Dodaj Wpis
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <livewire:time-logs-table />
        </div>
    </div>
</x-app-layout>
