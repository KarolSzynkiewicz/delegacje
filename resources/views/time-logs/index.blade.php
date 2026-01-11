<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Ewidencja Godzin</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('time-logs.monthly-grid') }}" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-month"></i> Widok Miesięczny
                </a>
                <a href="{{ route('time-logs.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Dodaj Wpis
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($timeLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Data</th>
                                        <th class="text-start">Pracownik</th>
                                        <th class="text-start">Projekt</th>
                                        <th class="text-start">Godziny</th>
                                        <th class="text-start">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($timeLogs as $timeLog)
                                        <tr>
                                            <td>{{ $timeLog->start_time->format('Y-m-d') }}</td>
                                            <td>{{ $timeLog->projectAssignment->employee->full_name }}</td>
                                            <td>{{ $timeLog->projectAssignment->project->name }}</td>
                                            <td class="fw-semibold">{{ number_format($timeLog->hours_worked, 2) }}h</td>
                                            <td>
                                                @can('delete', $timeLog)
                                                    <x-action-buttons
                                                        viewRoute="{{ route('time-logs.show', $timeLog) }}"
                                                        editRoute="{{ route('time-logs.edit', $timeLog) }}"
                                                        deleteRoute="{{ route('time-logs.destroy', $timeLog) }}"
                                                        deleteMessage="Czy na pewno chcesz usunąć ten wpis?"
                                                    />
                                                @else
                                                    <x-action-buttons
                                                        viewRoute="{{ route('time-logs.show', $timeLog) }}"
                                                        editRoute="{{ route('time-logs.edit', $timeLog) }}"
                                                    />
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($timeLogs->hasPages())
                            <div class="mt-3">
                                {{ $timeLogs->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak wpisów w systemie.</p>
                            <a href="{{ route('time-logs.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszy wpis
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
