<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Pracownik: {{ $employee->full_name }}</h2>
            <x-ui.button variant="ghost" href="{{ route('employees.index') }}">Wróć do listy</x-ui.button>
        </div>
    </x-slot>

    <div class="container-xxl">
        <div class="row">
            <div class="col-md-12">

            <!-- Zakładki -->
            @php
                $activeTab = $activeTab ?? 'info';
            @endphp
            <ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'info' ? 'active' : '' }}" href="{{ route('employees.show', $employee) }}">
                        Informacje
                    </a>
                </li>
                @if(auth()->user()->hasPermission('employee-documents.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" href="{{ route('employees.show.documents', $employee) }}">
                        Dokumenty ({{ $employee->employeeDocuments->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('rotations.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'rotations' ? 'active' : '' }}" href="{{ route('employees.show.rotations', $employee) }}">
                        Rotacje ({{ $employee->rotations->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('assignments.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'assignments' ? 'active' : '' }}" href="{{ route('employees.show.assignments', $employee) }}">
                        Przypisania do projektów ({{ $projectAssignments->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('vehicle-assignments.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'vehicle-assignments' ? 'active' : '' }}" href="{{ route('employees.show.vehicle-assignments', $employee) }}">
                        Przypisania do aut ({{ $vehicleAssignments->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('accommodation-assignments.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'accommodation-assignments' ? 'active' : '' }}" href="{{ route('employees.show.accommodation-assignments', $employee) }}">
                        Przypisania do domów ({{ $accommodationAssignments->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('payrolls.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'payrolls' ? 'active' : '' }}" href="{{ route('employees.show.payrolls', $employee) }}">
                        Płace ({{ $payrolls->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('employee-rates.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'employee-rates' ? 'active' : '' }}" href="{{ route('employees.show.employee-rates', $employee) }}">
                        Stawki ({{ $employeeRates->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('advances.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'advances' ? 'active' : '' }}" href="{{ route('employees.show.advances', $employee) }}">
                        Zaliczki ({{ $advances->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('time-logs.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'time-logs' ? 'active' : '' }}" href="{{ route('employees.show.time-logs', $employee) }}">
                        Godziny ({{ $timeLogs->count() }})
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('adjustments.view'))
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'adjustments' ? 'active' : '' }}" href="{{ route('employees.show.adjustments', $employee) }}">
                        Kary i nagrody ({{ $adjustments->count() }})
                    </a>
                </li>
                @endif
            </ul>

            <div id="employeeTabsContent">
                @if($activeTab === 'info')
                <!-- Zakładka Informacje -->
                <div id="info" role="tabpanel">
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
                @elseif($activeTab === 'documents' && auth()->user()->hasPermission('employee-documents.view'))
                <!-- Zakładka Dokumenty -->
                <div id="documents" role="tabpanel">
                    <x-ui.card>
                        <x-ui.table-header title="Dokumenty">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employees.employee-documents.create', $employee) }}" class="btn-sm">Dodaj Dokument</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
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
                                                    <x-ui.action-buttons>
                                                        <x-ui.button variant="warning" href="{{ route('employees.employee-documents.edit', [$employee, $employeeDocument]) }}" class="btn-sm">Edytuj</x-ui.button>
                                                        <x-ui.delete-form 
                                                            :url="route('employees.employee-documents.destroy', [$employee, $employeeDocument])"
                                                            message="Czy na pewno chcesz usunąć ten dokument?"
                                                        />
                                                    </x-ui.action-buttons>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <x-ui.empty-state 
                                icon="file-earmark"
                                message="Brak dokumentów"
                            />
                        @endif
                    </x-ui.card>
                </div>
                @elseif($activeTab === 'rotations' && auth()->user()->hasPermission('rotations.view'))
                <!-- Zakładka Rotacje -->
                <div id="rotations" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Rotacje">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employees.rotations.create', $employee) }}" class="btn-sm">Dodaj Rotację</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
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
                                                    <x-ui.button variant="ghost" href="{{ route('employees.rotations.edit', [$employee, $rotation]) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                    <x-ui.delete-form 
                                                        action="{{ route('employees.rotations.destroy', [$employee, $rotation]) }}"
                                                        message="Czy na pewno chcesz usunąć tę rotację?"
                                                        size="sm"
                                                    />
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
                @elseif($activeTab === 'assignments' && auth()->user()->hasPermission('assignments.view'))
                <!-- Zakładka Przypisania do projektów -->
                <div id="assignments" role="tabpanel">
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
                                                    <x-ui.button variant="ghost" href="{{ route('assignments.show', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
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
                @elseif($activeTab === 'vehicle-assignments' && auth()->user()->hasPermission('vehicle-assignments.view'))
                <!-- Zakładka Przypisania do aut -->
                <div id="vehicle-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do aut">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employees.vehicles.create', $employee) }}" class="btn-sm">
                                    <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                                </x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
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
                                                    <x-ui.button variant="ghost" href="{{ route('vehicle-assignments.show', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('vehicle-assignments.edit', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
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
                @elseif($activeTab === 'accommodation-assignments' && auth()->user()->hasPermission('accommodation-assignments.view'))
                <!-- Zakładka Przypisania do domów -->
                <div id="accommodation-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do domów">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employees.accommodations.create', $employee) }}" class="btn-sm">
                                    <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                                </x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
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
                                                    <x-ui.button variant="ghost" href="{{ route('accommodation-assignments.show', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('accommodation-assignments.edit', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
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
                @elseif($activeTab === 'payrolls' && auth()->user()->hasPermission('payrolls.view'))
                <!-- Zakładka Płace -->
                <div id="payrolls" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Płace">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('payrolls.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Payroll</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($payrolls->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Okres</th>
                                            <th>Godziny</th>
                                            <th>Kary/Nagrody</th>
                                            <th>Suma</th>
                                            <th>Waluta</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payrolls as $payroll)
                                            <tr>
                                                <td>
                                                    {{ $payroll->period_start->format('Y-m-d') }} - {{ $payroll->period_end->format('Y-m-d') }}
                                                </td>
                                                <td>{{ number_format($payroll->hours_amount, 2, ',', ' ') }}</td>
                                                <td>{{ number_format($payroll->adjustments_amount, 2, ',', ' ') }}</td>
                                                <td><strong>{{ number_format($payroll->total_amount, 2, ',', ' ') }}</strong></td>
                                                <td>{{ $payroll->currency }}</td>
                                                <td>
                                                    <x-ui.badge variant="info">{{ ucfirst($payroll->status->value ?? $payroll->status) }}</x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('payrolls.show', $payroll) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak płac dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>
                @elseif($activeTab === 'employee-rates' && auth()->user()->hasPermission('employee-rates.view'))
                <!-- Zakładka Stawki -->
                <div id="employee-rates" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Stawki">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employee-rates.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Stawkę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($employeeRates->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Od</th>
                                            <th>Do</th>
                                            <th>Kwota</th>
                                            <th>Waluta</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employeeRates as $rate)
                                            <tr>
                                                <td>{{ $rate->start_date->format('Y-m-d') }}</td>
                                                <td>{{ $rate->end_date ? $rate->end_date->format('Y-m-d') : '-' }}</td>
                                                <td><strong>{{ number_format($rate->amount, 2, ',', ' ') }}</strong></td>
                                                <td>{{ $rate->currency }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-rates.show', $rate) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-rates.edit', $rate) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak stawek dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>
                @elseif($activeTab === 'advances' && auth()->user()->hasPermission('advances.view'))
                <!-- Zakładka Zaliczki -->
                <div id="advances" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Zaliczki">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('advances.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Zaliczkę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($advances->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kwota</th>
                                            <th>Oprocentowanie</th>
                                            <th>Do odliczenia</th>
                                            <th>Data</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($advances as $advance)
                                            <tr>
                                                <td><strong>{{ number_format($advance->amount, 2, ',', ' ') }} {{ $advance->currency }}</strong></td>
                                                <td>
                                                    @if($advance->is_interest_bearing && $advance->interest_rate)
                                                        <x-ui.badge variant="warning">{{ number_format($advance->interest_rate, 2, ',', ' ') }}%</x-ui.badge>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td><strong class="text-danger">{{ number_format($advance->getTotalDeductionAmount(), 2, ',', ' ') }} {{ $advance->currency }}</strong></td>
                                                <td>{{ $advance->date->format('Y-m-d') }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('advances.show', $advance) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('advances.edit', $advance) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak zaliczek dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>
                @elseif($activeTab === 'time-logs' && auth()->user()->hasPermission('time-logs.view'))
                <!-- Zakładka Godziny -->
                <div id="time-logs" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Ewidencja Godzin">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('time-logs.create') }}" class="btn-sm">Dodaj Wpis</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($timeLogs->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Projekt</th>
                                            <th>Data</th>
                                            <th>Godziny</th>
                                            <th>Notatki</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($timeLogs as $timeLog)
                                            <tr>
                                                <td>
                                                    @if($timeLog->projectAssignment && $timeLog->projectAssignment->project)
                                                        <a href="{{ route('projects.show', $timeLog->projectAssignment->project) }}" class="text-primary">
                                                            {{ $timeLog->projectAssignment->project->name }}
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $timeLog->start_time->format('Y-m-d H:i') }}</td>
                                                <td><strong>{{ number_format($timeLog->hours_worked, 2, ',', ' ') }}</strong></td>
                                                <td>{{ $timeLog->notes ?? '-' }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('time-logs.show', $timeLog) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('time-logs.edit', $timeLog) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak wpisów godzin dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>
                @elseif($activeTab === 'adjustments' && auth()->user()->hasPermission('adjustments.view'))
                <!-- Zakładka Kary i Nagrody -->
                <div id="adjustments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Kary i Nagrody">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('adjustments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Karę/Nagrodę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($adjustments->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Typ</th>
                                            <th>Kwota</th>
                                            <th>Data</th>
                                            <th>Notatki</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($adjustments as $adjustment)
                                            <tr>
                                                <td>
                                                    <x-ui.badge variant="{{ $adjustment->type === 'bonus' ? 'success' : 'danger' }}">
                                                        {{ $adjustment->type === 'bonus' ? 'Nagroda' : 'Kara' }}
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <strong class="{{ $adjustment->type === 'bonus' ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($adjustment->amount, 2, ',', ' ') }} {{ $adjustment->currency }}
                                                    </strong>
                                                </td>
                                                <td>{{ $adjustment->date->format('Y-m-d') }}</td>
                                                <td>{{ $adjustment->notes ?? '-' }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('adjustments.show', $adjustment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('adjustments.edit', $adjustment) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak kar i nagród dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>
