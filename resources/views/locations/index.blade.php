@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Lokalizacje (Stocznie)</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('locations.create') }}" class="btn btn-primary">Dodaj Lokalizację</a>
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
                    <th>Adres</th>
                    <th>Miasto</th>
                    <th>Telefon</th>
                    <th>Email</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>{{ $location->id }}</td>
                        <td>{{ $location->name }}</td>
                        <td>{{ $location->address }}</td>
                        <td>{{ $location->city ?? '-' }}</td>
                        <td>{{ $location->phone ?? '-' }}</td>
                        <td>{{ $location->email ?? '-' }}</td>
                        <td>
                            <a href="{{ route('locations.show', $location) }}" class="btn btn-sm btn-info">Widok</a>
                            <a href="{{ route('locations.edit', $location) }}" class="btn btn-sm btn-warning">Edytuj</a>
                            <form action="{{ route('locations.destroy', $location) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Brak lokalizacji</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
