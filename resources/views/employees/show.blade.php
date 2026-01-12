@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Pracownik: {{ $employee->first_name }} {{ $employee->last_name }}</h1>
                <x-ui.button variant="ghost" href="{{ route('employees.index') }}">Wróć do listy</x-ui.button>
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
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vehicle-assignments-tab" data-bs-toggle="tab" data-bs-target="#vehicle-assignments" type="button" role="tab">
                        Przypisania do aut ({{ $vehicleAssignments->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="accommodation-assignments-tab" data-bs-toggle="tab" data-bs-target="#accommodation-assignments" type="button" role="tab">
                        Przypisania do domów ({{ $accommodationAssignments->count() }})
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
                                        <x-ui.badge variant="accent" class="me-1">{{ $role->name }}</x-ui.badge>
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
                        <x-ui.button variant="warning" href="{{ route('employees.edit', $employee) }}">Edytuj</x-ui.button>
                    </div>
                </div>
            </div>
                </div>

                <!-- Zakładka Dokumenty -->
                <div class="tab-pane fade" id="documents" role="tabpanel">
                    <x-ui.card>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Dokumenty</h5>
                            <x-ui.button variant="primary" href="{{ route('employees.employee-documents.create', $employee) }}" class="btn-sm">Dodaj Dokument</x-ui.button>
                        </div>
                        @if($employee->employeeDocuments->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
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
                                                    <x-ui.badge variant="{{ $employeeDocument->kind === 'bezokresowy' ? 'info' : 'info' }}">
                                                        {{ $employeeDocument->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                                    </x-ui.badge>
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
                                                        <x-ui.button variant="ghost" href="{{ $employeeDocument->file_url }}" target="_blank" class="btn-sm" title="Pobierz plik">
                                                            <i class="bi bi-download"></i>
                                                        </x-ui.button>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($employeeDocument->kind === 'bezokresowy')
                                                        <x-ui.badge variant="success">Ważny</x-ui.badge>
                                                    @elseif($employeeDocument->isExpired())
                                                        <x-ui.badge variant="danger">Wygasł</x-ui.badge>
                                                    @elseif($employeeDocument->isExpiringSoon())
                                                        <x-ui.badge variant="warning">Wygasa wkrótce</x-ui.badge>
                                                    @else
                                                        <x-ui.badge variant="success">Ważny</x-ui.badge>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <x-ui.button variant="warning" href="{{ route('employees.employee-documents.edit', [$employee, $employeeDocument]) }}" class="btn-sm">Edytuj</x-ui.button>
                                                        <form action="{{ route('employees.employee-documents.destroy', [$employee, $employeeDocument]) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <x-ui.button variant="danger" type="submit" class="btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć ten dokument?')">Usuń</x-ui.button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak dokumentów</p>
                        @endif
                    </x-ui.card>
                </div>

                <!-- Zakładka Rotacje -->
                <div class="tab-pane fade" id="rotations" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Rotacje</h5>
                            <x-ui.button variant="primary" href="{{ route('employees.rotations.create', $employee) }}" class="btn-sm">Dodaj Rotację</x-ui.button>
                        </div>
                        @if($employee->rotations->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
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
                                                        <x-ui.badge variant="success">Aktywna</x-ui.badge>
                                                    @elseif($status === 'scheduled')
                                                        <x-ui.badge variant="accent">Zaplanowana</x-ui.badge>
                                                    @elseif($status === 'completed')
                                                        <x-ui.badge variant="info">Zakończona</x-ui.badge>
                                                    @elseif($status === 'cancelled')
                                                        <x-ui.badge variant="danger">Anulowana</x-ui.badge>
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
                            <x-ui.button variant="primary" href="{{ route('employees.rotations.create', $employee) }}">Dodaj pierwszą rotację</x-ui.button>
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
                                <table class="table">
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

                <!-- Zakładka Przypisania do aut -->
                <div class="tab-pane fade" id="vehicle-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Przypisania do aut</h5>
                            <x-ui.button variant="primary" href="{{ route('employees.vehicles.create', $employee) }}" class="btn-sm">
                                <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                            </x-ui.button>
                        </div>
                        @if($vehicleAssignments->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Pojazd</th>
                                            <th>Rola</th>
                                            <th>Okres</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vehicleAssignments as $assignment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('vehicles.show', $assignment->vehicle) }}" class="text-primary">
                                                        {{ $assignment->vehicle->registration_number }}
                                                        @if($assignment->vehicle->brand)
                                                            ({{ $assignment->vehicle->brand }}{{ $assignment->vehicle->model ? ' ' . $assignment->vehicle->model : '' }})
                                                        @endif
                                                    </a>
                                                </td>
                                                <td>
                                                    @php
                                                        $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                        $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                        $isDriver = $positionValue === 'driver';
                                                    @endphp
                                                    <x-ui.badge variant="{{ $isDriver ? 'success' : 'info' }}">
                                                        {{ $isDriver ? 'Kierowca' : 'Pasażer' }}
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    {{ $assignment->start_date->format('Y-m-d') }}
                                                    @if($assignment->end_date)
                                                        - {{ $assignment->end_date->format('Y-m-d') }}
                                                    @else
                                                        - ...
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('vehicle-assignments.show', $assignment) }}" class="btn btn-sm btn-info">Szczegóły</a>
                                                    <a href="{{ route('vehicle-assignments.edit', $assignment) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak przypisań do aut dla tego pracownika.</p>
                            <x-ui.button variant="primary" href="{{ route('employees.vehicles.create', $employee) }}">Dodaj pierwsze przypisanie</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
                </div>

                <!-- Zakładka Przypisania do domów -->
                <div class="tab-pane fade" id="accommodation-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Przypisania do domów</h5>
                            <x-ui.button variant="primary" href="{{ route('employees.accommodations.create', $employee) }}" class="btn-sm">
                                <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                            </x-ui.button>
                        </div>
                        @if($accommodationAssignments->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Mieszkanie</th>
                                            <th>Okres</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($accommodationAssignments as $assignment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('accommodations.show', $assignment->accommodation) }}" class="text-primary">
                                                        {{ $assignment->accommodation->name }}
                                                        @if($assignment->accommodation->city)
                                                            ({{ $assignment->accommodation->city }})
                                                        @endif
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ $assignment->start_date->format('Y-m-d') }}
                                                    @if($assignment->end_date)
                                                        - {{ $assignment->end_date->format('Y-m-d') }}
                                                    @else
                                                        - ...
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('accommodation-assignments.show', $assignment) }}" class="btn btn-sm btn-info">Szczegóły</a>
                                                    <a href="{{ route('accommodation-assignments.edit', $assignment) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak przypisań do domów dla tego pracownika.</p>
                            <x-ui.button variant="primary" href="{{ route('employees.accommodations.create', $employee) }}">Dodaj pierwsze przypisanie</x-ui.button>
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
