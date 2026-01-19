<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wpis Ewidencji Godzin">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('time-logs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('time-logs.edit', $timeLog) }}"
                    routeName="time-logs.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Informacje podstawowe">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Pracownik</h6>
                        <p class="fw-semibold mb-0">{{ $timeLog->projectAssignment->employee->full_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Projekt</h6>
                        <p class="fw-semibold mb-0">{{ $timeLog->projectAssignment->project->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Data pracy</h6>
                        <p class="fw-semibold mb-0">{{ $timeLog->start_time->format('Y-m-d') }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Liczba godzin</h6>
                        <p class="fw-semibold fs-5 mb-0">{{ number_format($timeLog->hours_worked, 2) }}h</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Godziny</h6>
                        <p class="fw-semibold mb-0">
                            {{ $timeLog->start_time->format('H:i') }} - {{ $timeLog->end_time->format('H:i') }}
                        </p>
                    </div>
                    @if($timeLog->notes)
                    <div class="col-12">
                        <h6 class="text-muted small mb-1">Notatki</h6>
                        <p class="mb-0">{{ $timeLog->notes }}</p>
                    </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
