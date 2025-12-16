@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Zapisy Czasu Pracy</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('time_logs.create') }}" class="btn btn-primary">Dodaj Zapis</a>
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
                    <th>Delegacja</th>
                    <th>Pracownik</th>
                    <th>Data Rozpoczęcia</th>
                    <th>Data Zakończenia</th>
                    <th>Godziny Pracy</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($timeLogs as $timeLog)
                    <tr>
                        <td>{{ $timeLog->id }}</td>
                        <td>{{ $timeLog->delegation->id }}</td>
                        <td>{{ $timeLog->delegation->employee->name }}</td>
                        <td>{{ $timeLog->start_time->format('Y-m-d H:i') }}</td>
                        <td>{{ $timeLog->end_time?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $timeLog->hours_worked ?? '-' }}</td>
                        <td>
                            <a href="{{ route('time_logs.show', $timeLog) }}" class="btn btn-sm btn-info">Widok</a>
                            <a href="{{ route('time_logs.edit', $timeLog) }}" class="btn btn-sm btn-warning">Edytuj</a>
                            <form action="{{ route('time_logs.destroy', $timeLog) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Brak zapisów czasu pracy</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
