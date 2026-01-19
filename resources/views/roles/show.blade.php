<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Rola: {{ $role->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('roles.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('roles.edit', $role) }}"
                    routeName="roles.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card label="Informacje podstawowe" class="mb-3">
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
    </x-ui.card>

    @if($role->employees->count() > 0)
    <x-ui.card label="Pracownicy z tą rolą ({{ $role->employees->count() }})" class="mb-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Imię i Nazwisko</th>
                        <th>Email</th>
                        <th>Telefon</th>
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
    </x-ui.card>
    @endif

    @if($role->projectDemands->count() > 0)
    <x-ui.card label="Zapotrzebowania na tę rolę ({{ $role->projectDemands->count() }})">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Projekt</th>
                        <th>Liczba osób</th>
                        <th>Okres</th>
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
    </x-ui.card>
    @endif
</x-app-layout>
