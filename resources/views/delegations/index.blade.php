@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Delegacje Pracowników</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('delegations.create') }}" class="btn btn-primary">Dodaj Delegację</a>
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
                    <th>Pracownik</th>
                    <th>Projekt</th>
                    <th>Data Rozpoczęcia</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($delegations as $delegation)
                    <tr>
                        <td>{{ $delegation->id }}</td>
                        <td>{{ $delegation->employee->name }}</td>
                        <td>{{ $delegation->project->name }}</td>
                        <td>{{ $delegation->start_time->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge bg-{{ $delegation->status === 'active' ? 'success' : ($delegation->status === 'completed' ? 'info' : 'warning') }}">
                                {{ ucfirst($delegation->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('delegations.show', $delegation) }}" class="btn btn-sm btn-info">Widok</a>
                            <a href="{{ route('delegations.edit', $delegation) }}" class="btn btn-sm btn-warning">Edytuj</a>
                            <form action="{{ route('delegations.destroy', $delegation) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Brak delegacji</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
