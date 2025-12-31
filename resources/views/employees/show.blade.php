@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Pracownik: {{ $employee->first_name }} {{ $employee->last_name }}</h1>

            <div class="card">
                <div class="card-body">
                    @if($employee->image_path)
                        <div class="mb-4 text-center">
                            <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}" class="img-fluid rounded" style="max-width: 500px; max-height: 400px; object-fit: cover;">
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Imię i Nazwisko</h5>
                            <p>{{ $employee->first_name }} {{ $employee->last_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Rola</h5>
                            <p>{{ $employee->role->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Email</h5>
                            <p>{{ $employee->email }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Telefon</h5>
                            <p>{{ $employee->phone ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Prawo Jazdy A1 Ważne Od</h5>
                            <p>{{ $employee->a1_valid_from?->format('Y-m-d') ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Prawo Jazdy A1 Ważne Do</h5>
                            <p>
                                @if ($employee->a1_valid_to)
                                    <span class="badge bg-{{ $employee->a1_valid_to < now() ? 'danger' : 'success' }}">
                                        {{ $employee->a1_valid_to->format('Y-m-d') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h5>Dokument 1</h5>
                            <p>{{ $employee->document_1 ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h5>Dokument 2</h5>
                            <p>{{ $employee->document_2 ?? '-' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h5>Dokument 3</h5>
                            <p>{{ $employee->document_3 ?? '-' }}</p>
                        </div>
                    </div>

                    @if ($employee->notes)
                        <div class="mb-3">
                            <h5>Notatki</h5>
                            <p>{{ $employee->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
