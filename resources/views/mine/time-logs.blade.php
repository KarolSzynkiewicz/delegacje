<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
            <h2 class="fw-semibold fs-4 text-dark mb-0 text-center text-md-start">Ewidencje godzin mojego zespołu</h2>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center justify-content-md-end">
                <x-ui.button variant="ghost" href="{{ route('mine.time-logs.monthly-grid') }}" class="w-100 w-sm-auto">
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
            @if (empty($assignmentIds))
                <x-ui.card>
                    <div class="text-center py-5">
                        <i class="bi bi-clock-history fs-1 text-muted"></i>
                        <p class="text-muted mt-3">Brak ewidencji godzin w projektach zespołu.</p>
                    </div>
                </x-ui.card>
            @else
                <livewire:time-logs-table :filterAssignmentIds="$assignmentIds" />
            @endif
        </div>
    </div>
</x-app-layout>
