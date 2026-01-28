<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Rotacje pracownika: {{ $employee->full_name }}</h1>
            <x-ui.button variant="primary" href="{{ route('employees.rotations.create', $employee) }}">
                <i class="bi bi-plus-circle me-1"></i> Dodaj Rotację
            </x-ui.button>
        </div>
    </x-slot>

    <div class="mb-3">
        <a href="{{ route('employees.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Wróć do listy pracowników
        </a>
    </div>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" class="mb-4">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <!-- Formularz filtrowania -->
    <x-ui.card class="mb-4">
        <form method="GET" action="{{ route('employees.rotations.index', $employee) }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <x-ui.input 
                        type="select" 
                        name="status" 
                        label="Status"
                    >
                        <option value="">Wszystkie</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Zaplanowana</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktywna</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Zakończona</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Anulowana</option>
                    </x-ui.input>
                </div>

                <div class="col-md-3">
                    <x-ui.input 
                        type="date" 
                        name="start_date_from" 
                        label="Data rozpoczęcia od"
                        value="{{ request('start_date_from') }}"
                    />
                </div>

                <div class="col-md-3">
                    <x-ui.input 
                        type="date" 
                        name="start_date_to" 
                        label="Data rozpoczęcia do"
                        value="{{ request('start_date_to') }}"
                    />
                </div>

                <div class="col-md-3">
                    <x-ui.input 
                        type="date" 
                        name="end_date_from" 
                        label="Data zakończenia od"
                        value="{{ request('end_date_from') }}"
                    />
                </div>

                <div class="col-md-3">
                    <x-ui.input 
                        type="date" 
                        name="end_date_to" 
                        label="Data zakończenia do"
                        value="{{ request('end_date_to') }}"
                    />
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <x-ui.button variant="primary" type="submit">
                        <i class="bi bi-funnel me-1"></i> Filtruj
                    </x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('employees.rotations.index', $employee) }}">
                        Wyczyść
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>

    <!-- Informacja o liczbie wyników -->
    @if(request()->hasAny(['status', 'start_date_from', 'start_date_to', 'end_date_from', 'end_date_to']))
        <div class="mb-4 text-muted">
            Znaleziono <strong>{{ $rotations->total() }}</strong> rotacji
        </div>
    @endif

    @if($rotations->count() > 0)
        <x-ui.card>
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
                        @foreach($rotations as $rotation)
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
                                        <x-ui.badge variant="info">Zaplanowana</x-ui.badge>
                                    @elseif($status === 'completed')
                                        <x-ui.badge variant="info">Zakończona</x-ui.badge>
                                    @elseif($status === 'cancelled')
                                        <x-ui.badge variant="danger">Anulowana</x-ui.badge>
                                    @endif
                                </td>
                                <td>{{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <x-ui.button variant="warning" href="{{ route('employees.rotations.edit', [$employee, $rotation]) }}" class="btn-sm">
                                            Edytuj
                                        </x-ui.button>
                                        <form action="{{ route('employees.rotations.destroy', [$employee, $rotation]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button variant="danger" type="submit" class="btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć tę rotację?')">
                                                Usuń
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <x-ui.pagination :paginator="$rotations" />
            </div>
        </x-ui.card>
    @else
        <x-ui.card>
            <div class="text-center py-5">
                <p class="text-muted mb-4">Brak rotacji dla tego pracownika.</p>
                <x-ui.button variant="primary" href="{{ route('employees.rotations.create', $employee) }}">
                    <i class="bi bi-plus-circle me-1"></i> Dodaj pierwszą rotację
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif
</x-app-layout>
