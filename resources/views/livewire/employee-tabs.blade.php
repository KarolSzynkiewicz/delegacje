<div>
    <x-ui.tabs 
        :tabs="$tabsForComponent" 
        :activeTab="$activeTab" 
        id="employeeTabs"
        :compact-mobile="true"
    />

<div id="employeeTabsContent">
    @if($activeTab === 'info')
        <!-- Zakładka Informacje -->
        <div id="info" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4 p-3 rounded" style="background:{{ $employee->isTerminated() ? 'rgba(239,68,68,.08)' : 'rgba(52,211,153,.08)' }};border:1px solid {{ $employee->isTerminated() ? 'rgba(239,68,68,.25)' : 'rgba(52,211,153,.25)' }};">
                        <div>
                            @if($employee->isTerminated())
                                <x-ui.badge variant="danger">
                                    <i class="bi bi-person-x me-1"></i>Zwolniony
                                </x-ui.badge>
                                <div class="small text-muted mt-1">
                                    {{ $employee->terminated_at->format('Y-m-d') }}
                                    &nbsp;·&nbsp;
                                    {{ $employee->termination_reason?->label() ?? '-' }}
                                    @if($employee->termination_note)
                                        <br>{{ $employee->termination_note }}
                                    @endif
                                </div>
                            @else
                                <x-ui.badge variant="success">
                                    <i class="bi bi-person-check me-1"></i>Zatrudniony
                                </x-ui.badge>
                            @endif
                        </div>
                        @if(auth()->user()->hasPermission('employees.update'))
                            <div>
                                @if($employee->isTerminated())
                                    <x-ui.button variant="outline-secondary" class="btn-sm" wire:click="reinstate" wire:confirm="Czy na pewno chcesz cofnąć zwolnienie tego pracownika?">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Cofnij zwolnienie
                                    </x-ui.button>
                                @else
                                    <x-ui.button variant="danger" class="btn-sm" wire:click="openTerminateModal">
                                        <i class="bi bi-person-x me-1"></i>Zwolnij pracownika
                                    </x-ui.button>
                                @endif
                            </div>
                        @endif
                    </div>

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
                            <p>{{ $employee->email ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Telefon</h5>
                            <p>{{ $employee->phone ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>
                                <i class="bi bi-geo-alt me-1"></i> Aktualna lokalizacja
                            </h5>
                            <p>
                                @php
                                    $locationTracker = app(\App\Services\LocationTrackingService::class);
                                    $locationStatus = $locationTracker->getLocationStatus($employee, now());
                                    $currentProjects = $employee->current_projects;
                                    $projectsList = $currentProjects->pluck('name')->join(', ');
                                @endphp
                                
                                @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                                    <x-tooltip title="Pracownik jest w trakcie wyjazdu/powrotu">
                                        <x-ui.badge variant="warning">🚗 W podróży</x-ui.badge>
                                    </x-tooltip>
                                @elseif($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE)
                                    <x-tooltip title="Pracownik jest w bazie">
                                        <x-ui.badge variant="success">🏠 Baza</x-ui.badge>
                                    </x-tooltip>
                                @elseif(count($locationStatus['accommodation_names'] ?? []) > 0 || count($locationStatus['project_names'] ?? []) > 0 || count($locationStatus['vehicle_labels'] ?? []) > 0)
                                    @if($locationStatus['has_assignment_overlap'] ?? false)
                                        <div class="small text-warning mb-1">⚠ Wiele aktywnych przypisań tego dnia — sprawdź dane.</div>
                                    @endif
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        @foreach($locationStatus['accommodation_names'] ?? [] as $n)
                                            <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🏡 {{ $n }}</x-ui.badge>
                                        @endforeach
                                        @foreach($locationStatus['vehicle_labels'] ?? [] as $reg)
                                            <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🚗 {{ $reg }}</x-ui.badge>
                                        @endforeach
                                        @foreach($locationStatus['project_names'] ?? [] as $pn)
                                            <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🏢 {{ $pn }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                    @if($projectsList)
                                        <div class="small text-muted mt-1">
                                            Projekt: {{ $projectsList }}
                                        </div>
                                    @endif
                                @else
                                    <x-tooltip title="Pracownik jest poza bazą, oczekuje na przypisania">
                                        <x-ui.badge variant="accent">⏳ Poza bazą</x-ui.badge>
                                    </x-tooltip>
                                @endif
                            </p>
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
    @elseif($activeTab === 'documents')
        <!-- Zakładka Dokumenty -->
        <div id="documents" role="tabpanel">
            <x-ui.card>
                <x-ui.table-header title="Dokumenty">
                    <x-slot name="actions">
                        <x-ui.button variant="primary" href="{{ route('employee-documents.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Dokument</x-ui.button>
                    </x-slot>
                </x-ui.table-header>
                @if($tabData && $tabData->count() > 0)
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
                                @foreach($tabData as $employeeDocument)
                                    <tr>
                                        <td>{{ $employeeDocument->document->name ?? '-' }}</td>
                                        <td>
                                            <x-ui.badge variant="info">
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
                                                <div class="d-flex gap-1">
                                                    <x-ui.button variant="ghost" href="{{ $employeeDocument->preview_url }}" target="_blank" class="btn-sm" title="Podgląd pliku">
                                                        <i class="bi bi-eye"></i>
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ $employeeDocument->file_url }}" target="_blank" class="btn-sm" title="Pobierz plik">
                                                        <i class="bi bi-download"></i>
                                                    </x-ui.button>
                                                </div>
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
                                                <x-ui.button variant="warning" href="{{ route('employee-documents.edit', $employeeDocument) }}" class="btn-sm">Edytuj</x-ui.button>
                                                <x-ui.delete-form 
                                                    :url="route('employee-documents.destroy', $employeeDocument)"
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
                    <div class="text-center py-5">
                        <p class="text-muted mb-3">Brak dokumentów</p>
                        <x-ui.button variant="primary" href="{{ route('employee-documents.create', ['employee_id' => $employee->id]) }}" class="btn-sm">
                            Dodaj dokument
                        </x-ui.button>
                    </div>
                @endif
            </x-ui.card>
        </div>
    @elseif($activeTab === 'rotations')
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
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData->sortByDesc('start_date') as $rotation)
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
        </div>
    @elseif($activeTab === 'assignments')
        <!-- Zakładka Przypisania do projektów -->
        <div id="assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do projektów">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('project-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj przypisanie</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $assignment)
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
                                                    <x-ui.button variant="ghost" href="{{ route('project-assignments.show', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <x-ui.empty-state 
                                icon="people" 
                                message="Brak przypisań do projektów dla tego pracownika."
                            >
                                <x-ui.button 
                                    variant="primary" 
                                    href="{{ route('project-assignments.create', ['employee_id' => $employee->id]) }}"
                                    routeName="project-assignments.create"
                                    action="create"
                                >
                                    Dodaj przypisanie
                                </x-ui.button>
                            </x-ui.empty-state>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'vehicle-assignments')
        <!-- Zakładka Przypisania do aut -->
        <div id="vehicle-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do aut">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('vehicle-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">
                                    <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                                </x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $assignment)
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
                            <x-ui.button variant="primary" href="{{ route('vehicle-assignments.create', ['employee_id' => $employee->id]) }}">Dodaj pierwsze przypisanie</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'accommodation-assignments')
        <!-- Zakładka Przypisania do domów -->
        <div id="accommodation-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do domów">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('accommodation-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">
                                    <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                                </x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $assignment)
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
                            <x-ui.button variant="primary" href="{{ route('accommodation-assignments.create', ['employee_id' => $employee->id]) }}">Dodaj pierwsze przypisanie</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'payrolls')
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
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Okres</th>
                                            <th>Godziny</th>
                                            <th>Obciążenia/uznania</th>
                                            <th>Suma</th>
                                            <th>Waluta</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $payroll)
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
    @elseif($activeTab === 'employee-rates')
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
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $rate)
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
    @elseif($activeTab === 'company-assignments')
        <div id="company-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do spółek">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('company-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">
                                    <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                                </x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Spółka</th>
                                            <th>Okres</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $assignment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('companies.show', $assignment->company) }}" class="text-primary">
                                                        {{ $assignment->company->name }}
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ $assignment->start_date->format('Y-m-d') }}
                                                    @if($assignment->end_date)
                                                        - {{ $assignment->end_date->format('Y-m-d') }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        if ($assignment->isCurrentlyActive()) {
                                                            $statusLabel = 'Aktywne';
                                                            $badgeVariant = 'success';
                                                        } elseif ($assignment->isPast()) {
                                                            $statusLabel = 'Zakończone';
                                                            $badgeVariant = 'accent';
                                                        } elseif ($assignment->isScheduled()) {
                                                            $statusLabel = 'Zaplanowane';
                                                            $badgeVariant = 'info';
                                                        } else {
                                                            $statusLabel = 'Nieznany';
                                                            $badgeVariant = 'accent';
                                                        }
                                                    @endphp
                                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('company-assignments.show', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('company-assignments.edit', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak przypisań do spółek dla tego pracownika.</p>
                            <x-ui.button variant="primary" href="{{ route('company-assignments.create', ['employee_id' => $employee->id]) }}">Dodaj pierwsze przypisanie</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'advances')
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
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $advance)
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
    @elseif($activeTab === 'time-logs')
        <!-- Zakładka Godziny -->
        <div id="time-logs" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Ewidencja Godzin">
                            @if(auth()->user()->isAdmin())
                                <x-slot name="actions">
                                    <x-ui.button variant="primary" href="{{ route('time-logs.create') }}" class="btn-sm">Dodaj Wpis</x-ui.button>
                                </x-slot>
                            @endif
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $timeLog)
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
    @elseif($activeTab === 'evaluations')
        <!-- Zakładka Oceny -->
        <div id="evaluations" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Oceny Pracownika">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employee-evaluations.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Ocenę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Zaangażowanie</th>
                                            <th>Umiejętności</th>
                                            <th>Porządek</th>
                                            <th>Zachowanie</th>
                                            <th>Średnia</th>
                                            <th>Ocenił</th>
                                            <th>Notatki</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $evaluation)
                                            <tr>
                                                <td>{{ $evaluation->created_at->format('Y-m-d') }}</td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->engagement >= 7 ? 'success' : ($evaluation->engagement >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->engagement }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->skills >= 7 ? 'success' : ($evaluation->skills >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->skills }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->orderliness >= 7 ? 'success' : ($evaluation->orderliness >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->orderliness }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->behavior >= 7 ? 'success' : ($evaluation->behavior >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->behavior }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <strong>{{ number_format($evaluation->average_score, 2, ',', ' ') }}/10</strong>
                                                </td>
                                                <td>
                                                    @if($evaluation->createdBy)
                                                        {{ $evaluation->createdBy->name }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($evaluation->notes)
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $evaluation->notes }}">
                                                            {{ Str::limit($evaluation->notes, 50) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-evaluations.show', $evaluation) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    @can('update', $evaluation)
                                                        <x-ui.button variant="ghost" href="{{ route('employee-evaluations.edit', $evaluation) }}" class="btn-sm">
                                                            <i class="bi bi-pencil"></i> Edytuj
                                                        </x-ui.button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak ocen dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'adjustments')
        <!-- Zakładka Obciążenia i uznania -->
        <div id="adjustments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Obciążenia i uznania">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('adjustments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj obciążenie / uznanie</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
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
                                        @foreach($tabData as $adjustment)
                                            <tr>
                                                <td>
                                                    <x-ui.badge variant="{{ $adjustment->type === 'bonus' ? 'success' : 'danger' }}">
                                                        {{ $adjustment->type === 'bonus' ? 'Uznanie' : 'Obciążenie' }}
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
    @elseif($activeTab === 'comments')
        <!-- Zakładka Komentarze -->
        <div id="comments" role="tabpanel">
            <x-comments :commentable="$employee" />
        </div>
    @elseif($activeTab === 'equipment')
        <div id="equipment" role="tabpanel">
            <livewire:employee-equipment-history :employee="$employee" :wire:key="'employee-equipment-'.$employee->id" />
        </div>
    @endif
</div>

@if($showTerminateModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
         style="background:rgba(0,0,0,.75);z-index:2000;"
         wire:click.self="closeTerminateModal"
         wire:key="employee-terminate-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">
                <div class="modal-header" style="border-color:var(--glass-border);">
                    <h5 class="modal-title">
                        <i class="bi bi-person-x me-2 text-danger"></i>
                        Zwolnij pracownika — {{ $employee->full_name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeTerminateModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-flex gap-2 align-items-start mb-3" style="font-size:.85rem;">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Ta akcja nie usuwa pracownika ani nie zmienia historii rekrutacji.
                            Zapisuje tylko datę i powód zwolnienia oraz dodaje wpis audytowy do bazy kandydatów (status: <em>Były pracownik</em>).
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Powód zwolnienia *</label>
                        <select wire:model="terminationReason" class="form-select @error('terminationReason') is-invalid @enderror">
                            <option value="">— wybierz —</option>
                            @foreach(\App\Enums\EmployeeTerminationReason::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('terminationReason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label small text-muted mb-1">Notatka (opcjonalnie)</label>
                        <textarea wire:model="terminationNote" class="form-control @error('terminationNote') is-invalid @enderror" rows="3"></textarea>
                        @error('terminationNote')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer" style="border-color:var(--glass-border);">
                    <button type="button" wire:click="terminate" wire:loading.attr="disabled" class="btn btn-danger">
                        <span wire:loading.remove wire:target="terminate">
                            <i class="bi bi-person-x me-1"></i>Zwolnij
                        </span>
                        <span wire:loading wire:target="terminate">
                            <i class="bi bi-hourglass-split me-1"></i>Zapisuję…
                        </span>
                    </button>
                    <button type="button" wire:click="closeTerminateModal" class="btn btn-outline-secondary">
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
@endif
</div>
