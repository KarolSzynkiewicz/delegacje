@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Projekty</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('projects.create') }}" class="btn btn-primary">Dodaj Projekt</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Lokalizacja</th>
                    <th>Data Rozpoczęcia</th>
                    <th>Status</th>
                    <th>Klient</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->location->name ?? '-' }}</td>
                        <td>{{ $project->start_date->format('Y-m-d') }}</td>
                        <td>
                            <span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'info' : 'warning') }}">
                                {{ ucfirst($project->status) }}
                            </span>
                        </td>
                        <td>{{ $project->client_name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-info">Widok</a>
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-warning">Edytuj</a>
                            <form action="{{ route('projects.destroy', $project) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Brak projektów</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
