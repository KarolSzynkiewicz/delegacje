<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Typ Dokumentu: {{ $documentType->name }}</h2>
            <div>
                <x-ui.button variant="warning" href="{{ route('document-types.edit', $documentType) }}" class="me-2">Edytuj</x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('document-types.index') }}">Wróć do listy</x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="container-xxl">
        <div class="row">
            <div class="col-md-12">

            <div class="card mb-4">
                <div class="card-body">
                    <h5>Opis</h5>
                    <p>{{ $documentType->description ?? 'Brak opisu' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Przypisane dokumenty pracowników ({{ $documentType->documents->sum(fn($doc) => $doc->employeeDocuments->count()) }})</h5>
                </div>
                <div class="card-body">
                    @if($documentType->employeeDocuments->count() > 0)
                        <table class="table">
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
                                @php
                                    // Pobierz wszystkie employee_documents z tym typem dokumentu
                                    $employeeDocuments = \App\Models\EmployeeDocument::where('type', $documentType->name)
                                        ->with('employee')
                                        ->get();
                                @endphp
                                @foreach($employeeDocuments as $employeeDocument)
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
                                                <a href="{{ route('employee-documents.edit', $employeeDocument) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                            </td>
                                        </tr>
                                    @endforeach
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
</x-app-layout>
