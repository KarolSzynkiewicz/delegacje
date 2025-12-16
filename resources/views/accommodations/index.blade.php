@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Akomodacje</h1>
                <a href="{{ route('accommodations.create') }}" class="btn btn-primary">Dodaj Akomodację</a>
            </div>

            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (count($accommodations) > 0)
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Adres</th>
                            <th>Miasto</th>
                            <th>Pojemność</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accommodations as $accommodation)
                            <tr>
                                <td>{{ $accommodation->id }}</td>
                                <td>{{ $accommodation->name }}</td>
                                <td>{{ $accommodation->address }}</td>
                                <td>{{ $accommodation->city ?? '-' }}</td>
                                <td>{{ $accommodation->capacity }} osób</td>
                                <td>
                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="btn btn-sm btn-info">Pokaż</a>
                                    <a href="{{ route('accommodations.edit', $accommodation) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                    <form action="{{ route('accommodations.destroy', $accommodation) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $accommodations->links() }}
            @else
                <div class="alert alert-info">Brak akomodacji w systemie.</div>
            @endif
        </div>
    </div>
</div>
@endsection
