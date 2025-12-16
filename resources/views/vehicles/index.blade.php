@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Pojazdy</h1>
                <a href="{{ route('vehicles.create') }}" class="btn btn-primary">Dodaj Pojazd</a>
            </div>

            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (count($vehicles) > 0)
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Numer Rejestracyjny</th>
                            <th>Marka i Model</th>
                            <th>Pojemność</th>
                            <th>Stan Techniczny</th>
                            <th>Przegląd Ważny Do</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->id }}</td>
                                <td><strong>{{ $vehicle->registration_number }}</strong></td>
                                <td>{{ $vehicle->brand ?? '-' }} {{ $vehicle->model ?? '' }}</td>
                                <td>{{ $vehicle->capacity ?? '-' }} osób</td>
                                <td>
                                    <span class="badge bg-{{ $vehicle->technical_condition == 'excellent' ? 'success' : ($vehicle->technical_condition == 'good' ? 'info' : ($vehicle->technical_condition == 'fair' ? 'warning' : 'danger')) }}">
                                        {{ ucfirst($vehicle->technical_condition) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($vehicle->inspection_valid_to)
                                        <span class="badge bg-{{ $vehicle->inspection_valid_to < now() ? 'danger' : 'success' }}">
                                            {{ $vehicle->inspection_valid_to->format('Y-m-d') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-info">Pokaż</a>
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                    <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $vehicles->links() }}
            @else
                <div class="alert alert-info">Brak pojazdów w systemie.</div>
            @endif
        </div>
    </div>
</div>
@endsection
