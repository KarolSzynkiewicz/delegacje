<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Rola: {{ $role->name }}</h2>
            <div class="d-flex gap-2">
                <x-edit-button href="{{ route('roles.edit', $role) }}" />
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Nazwa</h5>
                            <p class="text-dark">{{ $role->name }}</p>
                        </div>
                        @if($role->description)
                        <div class="col-12">
                            <h5 class="fw-bold text-dark mb-2">Opis</h5>
                            <p class="text-dark">{{ $role->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($role->employees->count() > 0)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-4">Pracownicy z tą rolą ({{ $role->employees->count() }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Imię i Nazwisko</th>
                                    <th class="text-start">Email</th>
                                    <th class="text-start">Telefon</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($role->employees as $employee)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                                {{ $employee->full_name }}
                                            </a>
                                        </td>
                                        <td>{{ $employee->email }}</td>
                                        <td>{{ $employee->phone ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if($role->projectDemands->count() > 0)
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-4">Zapotrzebowania na tę rolę ({{ $role->projectDemands->count() }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Projekt</th>
                                    <th class="text-start">Liczba osób</th>
                                    <th class="text-start">Okres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($role->projectDemands as $demand)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.show', $demand->project) }}" class="text-decoration-none">
                                                {{ $demand->project->name }}
                                            </a>
                                        </td>
                                        <td>{{ $demand->required_count }}</td>
                                        <td>
                                            {{ $demand->date_from->format('Y-m-d') }}
                                            @if($demand->date_to)
                                                - {{ $demand->date_to->format('Y-m-d') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
