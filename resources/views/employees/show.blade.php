@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Pracownik: {{ $employee->first_name }} {{ $employee->last_name }}</h1>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Wróć do listy</a>
            </div>

            <!-- Zakładki -->
            <ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                        Informacje
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                        Dokumenty ({{ $employee->employeeDocuments->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rotations-tab" data-bs-toggle="tab" data-bs-target="#rotations" type="button" role="tab">
                        Rotacje ({{ $employee->rotations->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                        Przypisania do projektów ({{ $projectAssignments->count() }})
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="employeeTabsContent">
                <!-- Zakładka Informacje -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
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
                            <h5>Role</h5>
                            <p>
                                @if($employee->roles->count() > 0)
                                    @foreach($employee->roles as $role)
                                        <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </p>
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

                    @if ($employee->notes)
                        <div class="mb-3">
                            <h5>Notatki</h5>
                            <p>{{ $employee->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">Edytuj</a>
                    </div>
                </div>
            </div>
                </div>

                <!-- Zakładka Dokumenty -->
                <div class="tab-pane fade" id="documents" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4" id="dokumenty">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Dokumenty</h5>
                            <a href="{{ route('employees.employee-documents.create', $employee) }}" class="btn btn-primary btn-sm">Dodaj Dokument</a>
                        </div>
                        @if($employee->employeeDocuments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Typ</th>
                                            <th>Rodzaj</th>
                                            <th>Ważny od</th>
                                            <th>Ważny do</th>
                                            <th>Plik</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employee->employeeDocuments as $employeeDocument)
                                            <tr>
                                                <td>{{ $employeeDocument->document->name ?? '-' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $employeeDocument->kind === 'bezokresowy' ? 'info' : 'secondary' }}">
                                                        {{ $employeeDocument->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                                    </span>
                                                </td>
                                                <td>{{ $employeeDocument->valid_from ? $employeeDocument->valid_from->format('Y-m-d') : '-' }}</td>
                                                <td>
                                                    @if($employeeDocument->kind === 'bezokresowy')
                                                        <span class="text-muted">Bezokresowy</span>
                                                    @else
                                                        {{ $employeeDocument->valid_to ? $employeeDocument->valid_to->format('Y-m-d') : '-' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($employeeDocument->file_path)
                                                        <a href="{{ $employeeDocument->file_url }}" target="_blank" class="btn btn-sm btn-info" title="Pobierz plik">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5h2.5a.5.5 0 0 1 0 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h2.717a.5.5 0 0 1 .357.135l2.415 2.414A.5.5 0 0 1 8 6.5v3.9a.5.5 0 0 1 .5.5h2.5a.5.5 0 0 1 0 1H9a1 1 0 0 1-1-1V6.5a.5.5 0 0 1 .146-.354l2.415-2.414A.5.5 0 0 1 11 3.5v-.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v6.5a.5.5 0 0 1-.5.5h-2.5a.5.5 0 0 1 0-1H13V4h-2v.5a.5.5 0 0 1-.5.5H9.5a.5.5 0 0 1-.5-.5V3H6.5a.5.5 0 0 1-.5.5v6.9z"/>
                                                                <path d="M14 10.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zm-2.5-2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                                                            </svg>
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
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
                                                    <a href="{{ route('employees.employee-documents.edit', [$employee, $employeeDocument]) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                                    <form action="{{ route('employees.employee-documents.destroy', [$employee, $employeeDocument]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć ten dokument?')">Usuń</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak dokumentów</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>

                <!-- Zakładka Rotacje -->
                <div class="tab-pane fade" id="rotations" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Rotacje</h5>
                            <a href="{{ route('employees.rotations.create', $employee) }}" class="btn btn-primary btn-sm">Dodaj Rotację</a>
                        </div>
                        @if($employee->rotations->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data rozpoczęcia</th>
                                            <th>Data zakończenia</th>
                                            <th>Status</th>
                                            <th>Notatki</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employee->rotations->sortByDesc('start_date') as $rotation)
                                            <tr>
                                                <td>{{ $rotation->start_date->format('Y-m-d') }}</td>
                                                <td>{{ $rotation->end_date->format('Y-m-d') }}</td>
                                                <td>
                                                    @php
                                                        $status = $rotation->status;
                                                    @endphp
                                                    @if($status === 'active')
                                                        <span class="badge bg-success">Aktywna</span>
                                                    @elseif($status === 'scheduled')
                                                        <span class="badge bg-primary">Zaplanowana</span>
                                                    @elseif($status === 'completed')
                                                        <span class="badge bg-info">Zakończona</span>
                                                    @elseif($status === 'cancelled')
                                                        <span class="badge bg-danger">Anulowana</span>
                                                    @endif
                                                </td>
                                                <td>{{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}</td>
                                                <td>
                                                    <a href="{{ route('employees.rotations.edit', [$employee, $rotation]) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                                    <form action="{{ route('employees.rotations.destroy', [$employee, $rotation]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć tę rotację?')">Usuń</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak rotacji dla tego pracownika.</p>
                            <a href="{{ route('employees.rotations.create', $employee) }}" class="btn btn-primary">Dodaj pierwszą rotację</a>
                        @endif
                    </div>
                </div>
            </div>
                </div>

                <!-- Zakładka Przypisania do projektów -->
                <div class="tab-pane fade" id="assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Przypisania do projektów</h5>
                        @if($projectAssignments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Projekt</th>
                                            <th>Rola</th>
                                            <th>Okres</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projectAssignments as $assignment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('projects.show', $assignment->project) }}" class="text-primary">
                                                        {{ $assignment->project->name }}
                                                    </a>
                                                </td>
                                                <td>{{ $assignment->role->name }}</td>
                                                <td>
                                                    {{ $assignment->start_date->format('Y-m-d') }}
                                                    @if($assignment->end_date)
                                                        - {{ $assignment->end_date->format('Y-m-d') }}
                                                    @else
                                                        - ...
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                        $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                        $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                                    @endphp
                                                    <span class="badge 
                                                        @if($statusValue === 'active') bg-success
                                                        @elseif($statusValue === 'completed') bg-info
                                                        @elseif($statusValue === 'cancelled') bg-danger
                                                        @elseif($statusValue === 'in_transit') bg-warning
                                                        @elseif($statusValue === 'at_base') bg-secondary
                                                        @else bg-secondary
                                                        @endif">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-sm btn-info">Szczegóły</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak przypisań do projektów dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Przełącz na zakładkę dokumentów jeśli jest hash #dokumenty w URL
    if (window.location.hash === '#dokumenty') {
        const documentsTab = document.getElementById('documents-tab');
        if (documentsTab) {
            const tab = new bootstrap.Tab(documentsTab);
            tab.show();
        }
    }
</script>
@endsection
