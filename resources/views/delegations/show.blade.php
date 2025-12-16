@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Delegacja #{{ $delegation->id }}</h1>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Pracownik</h5>
                            <p>{{ $delegation->employee->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Projekt</h5>
                            <p>{{ $delegation->project->name }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Data Rozpoczęcia</h5>
                            <p>{{ $delegation->start_time->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Data Zakończenia</h5>
                            <p>{{ $delegation->end_time?->format('Y-m-d H:i') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Status</h5>
                            <p>
                                <span class="badge bg-{{ $delegation->status === 'active' ? 'success' : ($delegation->status === 'completed' ? 'info' : 'warning') }}">
                                    {{ ucfirst($delegation->status) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    @if ($delegation->notes)
                        <div class="mb-3">
                            <h5>Notatki</h5>
                            <p>{{ $delegation->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('delegations.edit', $delegation) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('delegations.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>

            <h3>Zapisy Czasu Pracy</h3>
            @if ($delegation->timeLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Data Rozpoczęcia</th>
                                <th>Data Zakończenia</th>
                                <th>Godziny Pracy</th>
                                <th>Notatki</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($delegation->timeLogs as $timeLog)
                                <tr>
                                    <td>{{ $timeLog->start_time->format('Y-m-d H:i') }}</td>
                                    <td>{{ $timeLog->end_time?->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>{{ $timeLog->hours_worked ?? '-' }}</td>
                                    <td>{{ $timeLog->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>Brak zapisów czasu pracy dla tej delegacji.</p>
            @endif
        </div>
    </div>
</div>
@endsection
