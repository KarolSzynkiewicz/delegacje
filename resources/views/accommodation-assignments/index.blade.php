<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header 
            title="{{ isset($employee) ? 'Mieszkania pracownika: ' . $employee->full_name : 'Wszystkie przypisania mieszkań' }}"
        >
            <x-slot name="right">
                @isset($employee)
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('accommodation-assignments.create', ['employee_id' => $employee->id]) }}"
                        routeName="accommodation-assignments.create"
                        action="create"
                    >
                        Przypisz Mieszkanie
                    </x-ui.button>
                @else
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('accommodation-assignments.create') }}"
                        routeName="accommodation-assignments.create"
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
                                <th>Mieszkanie</th>
                                <th>Od - Do</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->accommodation->name }} ({{ $assignment->accommodation->city }})</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $assignment->start_date->format('Y-m-d') }} - 
                                            {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                        </small>
                                    </td>
                                    <td>
                                        <x-action-buttons
                                            viewRoute="{{ route('accommodation-assignments.show', $assignment) }}"
                                            editRoute="{{ route('accommodation-assignments.edit', $assignment) }}"
                                            deleteRoute="{{ route('accommodation-assignments.destroy', $assignment) }}"
                                            deleteMessage="Czy na pewno chcesz usunąć to przypisanie mieszkania?"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($assignments->hasPages())
                    <div class="mt-3">
                        {{ $assignments->links() }}
                    </div>
                @endif
            @else
                <x-ui.empty-state 
                    icon="inbox" 
                    message="Brak przypisanych mieszkań."
                >
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('accommodation-assignments.create', ['employee_id' => $employee->id]) }}"
                        routeName="accommodation-assignments.create"
                        action="create"
                    >
                        Przypisz pierwsze mieszkanie
                    </x-ui.button>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
            @else
                {{-- Globalny widok - z Livewire i filtrowaniem --}}
                <livewire:accommodation-assignments-table />
            @endisset
</x-app-layout>
