<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
            <h2 class="fw-semibold fs-4 text-dark mb-0 text-center text-md-start">Ewidencja Godzin</h2>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center justify-content-md-end">
                <x-ui.button variant="ghost" href="{{ route('time-logs.monthly-grid') }}" class="w-100 w-sm-auto">
                    <i class="bi bi-calendar-month"></i> Widok Miesięczny
                </x-ui.button>
                @if(auth()->user()->isAdmin())
                    <x-ui.button variant="primary" href="{{ route('time-logs.create') }}" class="w-100 w-sm-auto">
                        <i class="bi bi-plus-circle"></i> Dodaj Wpis
                    </x-ui.button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <livewire:time-logs-table />
        </div>
    </div> 
</x-app-layout>
