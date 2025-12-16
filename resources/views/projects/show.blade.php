@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>{{ $project->name }}</h1>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Lokalizacja</h5>
                            <p>{{ $project->location->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Status</h5>
                            <p>
                                <span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'info' : 'warning') }}">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Data Rozpoczęcia</h5>
                            <p>{{ $project->start_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Data Zakończenia</h5>
                            <p>{{ $project->end_date?->format('Y-m-d') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Klient</h5>
                            <p>{{ $project->client_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Budżet</h5>
                            <p>{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</p>
                        </div>
                    </div>

                    @if ($project->description)
                        <div class="mb-3">
                            <h5>Opis</h5>
                            <p>{{ $project->description }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>

            <h3>Delegacje</h3>
            @if ($project->delegations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Pracownik</th>
                                <th>Status</th>
                                <th>Data Rozpoczęcia</th>
                                <th>Data Zakończenia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($project->delegations as $delegation)
                                <tr>
                                    <td>{{ $delegation->employee->name }}</td>
                                    <td>{{ ucfirst($delegation->status) }}</td>
                                    <td>{{ $delegation->start_time->format('Y-m-d H:i') }}</td>
                                    <td>{{ $delegation->end_time?->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>Brak delegacji dla tego projektu.</p>
            @endif
        </div>
    </div>
</div>
@endsection
