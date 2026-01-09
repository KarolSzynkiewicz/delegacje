<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">
                Wszystkie Rotacje Pracowników
            </h2>
            <a href="{{ route('rotations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Rotację
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Formularz filtrowania -->
                    <div class="mb-4 bg-light rounded p-3 border">
                        <form method="GET" action="{{ route('rotations.index') }}" class="row g-3">
                            <!-- Filtrowanie po pracowniku -->
                            <div class="col-md-3">
                                <label for="employee_id" class="form-label small fw-semibold">
                                    Pracownik
                                </label>
                                <select name="employee_id" id="employee_id" class="form-select form-select-sm">
                                    <option value="">Wszyscy</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtrowanie po statusie -->
                            <div class="col-md-3">
                                <label for="status" class="form-label small fw-semibold">
                                    Status
                                </label>
                                <select name="status" id="status" class="form-select form-select-sm">
                                    <option value="">Wszystkie</option>
                                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Zaplanowana</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktywna</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Zakończona</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Anulowana</option>
                                </select>
                            </div>

                            <!-- Filtrowanie po dacie rozpoczęcia (od) -->
                            <div class="col-md-3">
                                <label for="start_date_from" class="form-label small fw-semibold">
                                    Data rozpoczęcia od
                                </label>
                                <input type="date" name="start_date_from" id="start_date_from" value="{{ request('start_date_from') }}" class="form-control form-control-sm">
                            </div>

                            <!-- Filtrowanie po dacie rozpoczęcia (do) -->
                            <div class="col-md-3">
                                <label for="start_date_to" class="form-label small fw-semibold">
                                    Data rozpoczęcia do
                                </label>
                                <input type="date" name="start_date_to" id="start_date_to" value="{{ request('start_date_to') }}" class="form-control form-control-sm">
                            </div>

                            <!-- Filtrowanie po dacie zakończenia (od) -->
                            <div class="col-md-3">
                                <label for="end_date_from" class="form-label small fw-semibold">
                                    Data zakończenia od
                                </label>
                                <input type="date" name="end_date_from" id="end_date_from" value="{{ request('end_date_from') }}" class="form-control form-control-sm">
                            </div>

                            <!-- Filtrowanie po dacie zakończenia (do) -->
                            <div class="col-md-3">
                                <label for="end_date_to" class="form-label small fw-semibold">
                                    Data zakończenia do
                                </label>
                                <input type="date" name="end_date_to" id="end_date_to" value="{{ request('end_date_to') }}" class="form-control form-control-sm">
                            </div>

                            <!-- Przyciski -->
                            <div class="col-md-6 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-funnel"></i> Filtruj
                                </button>
                                <a href="{{ route('rotations.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle"></i> Wyczyść
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Informacja o liczbie wyników -->
                    @if(request()->hasAny(['employee_id', 'status', 'start_date_from', 'start_date_to', 'end_date_from', 'end_date_to']))
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Znaleziono <strong>{{ $rotations->total() }}</strong> rotacji
                            @if(request('employee_id'))
                                dla pracownika: <strong>{{ $employees->find(request('employee_id'))?->full_name }}</strong>
                            @endif
                        </div>
                    @endif

                    @if($rotations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Pracownik</th>
                                        <th class="text-start">Data rozpoczęcia</th>
                                        <th class="text-start">Data zakończenia</th>
                                        <th class="text-start">Status</th>
                                        <th class="text-start">Notatki</th>
                                        <th class="text-start">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rotations as $rotation)
                                        <tr>
                                            <td>
                                                <a href="{{ route('employees.show', $rotation->employee) }}" 
                                                   class="text-primary text-decoration-none fw-medium">
                                                    {{ $rotation->employee->full_name }}
                                                </a>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $rotation->start_date->format('Y-m-d') }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $rotation->end_date->format('Y-m-d') }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $status = $rotation->status;
                                                    $badgeClass = match($status) {
                                                        'active' => 'bg-success',
                                                        'scheduled' => 'bg-primary',
                                                        'completed' => 'bg-secondary',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    @if($status === 'active') Aktywna
                                                    @elseif($status === 'scheduled') Zaplanowana
                                                    @elseif($status === 'completed') Zakończona
                                                    @elseif($status === 'cancelled') Anulowana
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('employees.rotations.edit', [$rotation->employee, $rotation]) }}" 
                                                       class="btn btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('employees.rotations.destroy', [$rotation->employee, $rotation]) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-outline-danger"
                                                                onclick="return confirm('Czy na pewno chcesz usunąć tę rotację?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($rotations->hasPages())
                            <div class="mt-3">
                                {{ $rotations->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak rotacji w systemie.</p>
                            <a href="{{ route('rotations.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszą rotację
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
