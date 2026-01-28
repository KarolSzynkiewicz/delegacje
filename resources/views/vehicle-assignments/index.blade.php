<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header 
            title="{{ isset($employee) ? 'Pojazdy pracownika: ' . $employee->full_name : 'Wszystkie przypisania pojazdów' }}"
        >
            <x-slot name="right">
                @isset($employee)
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('vehicle-assignments.create', ['employee_id' => $employee->id]) }}"
                        routeName="vehicle-assignments.create"
                        action="create"
                    >
                        Przypisz Pojazd
                    </x-ui.button>
                @else
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('vehicle-assignments.create') }}"
                        routeName="vehicle-assignments.create"
                        action="create"
                    >
                        Dodaj przypisanie
                    </x-ui.button>
                @endisset
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @isset($employee)
        {{-- Widok dla konkretnego pracownika - bez Livewire --}}
        <x-ui.card>
            @if($assignments->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Pojazd</th>
                                <th>Rola</th>
                                <th>Od - Do</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->vehicle->registration_number }} ({{ $assignment->vehicle->brand }})</td>
                                    <td>
                                        @php
                                            $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                            $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                            $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                        @endphp
                                        @if($positionValue === 'driver')
                                            <x-ui.badge variant="primary">{{ $positionLabel }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="accent">{{ $positionLabel }}</x-ui.badge>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $assignment->start_date->format('Y-m-d') }} - 
                                            {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                        </small>
                                    </td>
                                    <td>
                                        <x-action-buttons
                                            viewRoute="{{ route('vehicle-assignments.show', $assignment) }}"
                                            editRoute="{{ route('vehicle-assignments.edit', $assignment) }}"
                                            deleteRoute="{{ route('vehicle-assignments.destroy', $assignment) }}"
                                            deleteMessage="Czy na pewno chcesz usunąć to przypisanie pojazdu?"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($assignments->hasPages())
                    <div class="mt-3">
                        <x-ui.pagination :paginator="$assignments" />
                    </div>
                @endif
            @else
                <x-ui.empty-state 
                    icon="inbox" 
                    message="Brak przypisanych pojazdów."
                >
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('vehicle-assignments.create', ['employee_id' => $employee->id]) }}"
                        routeName="vehicle-assignments.create"
                        action="create"
                    >
                        Przypisz pierwszy pojazd
                    </x-ui.button>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
            @else
                {{-- Globalny widok - z Livewire i filtrowaniem --}}
                <livewire:vehicle-assignments-table />
            @endisset
</x-app-layout>
