@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Pracownicy</h1>
                <a href="{{ route('employees.create') }}" class="btn btn-primary">Dodaj Pracownika</a>
            </div>

            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (count($employees) > 0)
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Imię i Nazwisko</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Rola</th>
                            <th>Prawo Jazdy A1</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td>{{ $employee->id }}</td>
                                <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                                <td>{{ $employee->email }}</td>
                                <td>{{ $employee->phone ?? '-' }}</td>
                                <td>{{ $employee->role->name ?? '-' }}</td>
                                <td>
                                    @if ($employee->a1_valid_to)
                                        <span class="badge bg-{{ $employee->a1_valid_to < now() ? 'danger' : 'success' }}">
                                            {{ $employee->a1_valid_to->format('Y-m-d') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-info">Pokaż</a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $employees->links() }}
            @else
                <div class="alert alert-info">Brak pracowników w systemie.</div>
            @endif
        </div>
    </div>
</div>
@endsection
