@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Dokument: {{ $document->name }}</h1>
                <div>
                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-warning me-2">Edytuj</a>
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">Wróć do listy</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5>Opis</h5>
                    <p>{{ $document->description ?? 'Brak opisu' }}</p>
                    <hr>
                    <h5>Dokument okresowy</h5>
                    <p>{{ $document->is_periodic ? 'Tak' : 'Nie' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Przypisane dokumenty pracowników ({{ $document->employee_documents_count }})</h5>
                </div>
                <div class="card-body">
                    @if($document->employeeDocuments->count() > 0)
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Ważny od</th>
                                    <th>Ważny do</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->employeeDocuments as $employeeDocument)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $employeeDocument->employee) }}">
                                                {{ $employeeDocument->employee->full_name }}
                                            </a>
                                        </td>
                                        <td>{{ $employeeDocument->valid_from->format('Y-m-d') }}</td>
                                        <td>
                                            @if($employeeDocument->kind === 'bezokresowy')
                                                <span class="text-muted">Bezokresowy</span>
                                            @else
                                                {{ $employeeDocument->valid_to ? $employeeDocument->valid_to->format('Y-m-d') : '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($employeeDocument->kind === 'bezokresowy')
                                                <span class="badge bg-success">Ważny</span>
                                            @elseif($employeeDocument->isExpired())
                                                <span class="badge bg-danger">Wygasł</span>
                                            @elseif($employeeDocument->isExpiringSoon())
                                                <span class="badge bg-warning">Wygasa wkrótce</span>
                                            @else
                                                <span class="badge bg-success">Ważny</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('employees.employee-documents.edit', [$employeeDocument->employee, $employeeDocument]) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Brak przypisanych dokumentów</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
