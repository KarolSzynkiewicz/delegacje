<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Wpis Ewidencji Godzin</h2>
            <div class="d-flex gap-2">
                <x-edit-button href="{{ route('time-logs.edit', $timeLog) }}" />
                <a href="{{ route('time-logs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-4">Informacje podstawowe</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Pracownik</h6>
                            <p class="fw-semibold">{{ $timeLog->projectAssignment->employee->full_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Projekt</h6>
                            <p class="fw-semibold">{{ $timeLog->projectAssignment->project->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Data pracy</h6>
                            <p class="fw-semibold">{{ $timeLog->start_time->format('Y-m-d') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Liczba godzin</h6>
                            <p class="fw-semibold fs-5">{{ number_format($timeLog->hours_worked, 2) }}h</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Godziny</h6>
                            <p class="fw-semibold">
                                {{ $timeLog->start_time->format('H:i') }} - {{ $timeLog->end_time->format('H:i') }}
                            </p>
                        </div>
                        @if($timeLog->notes)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Notatki</h6>
                            <p>{{ $timeLog->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
