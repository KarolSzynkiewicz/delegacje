<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header 
            title="{{ isset($project) ? 'Pracownicy w projekcie: ' . $project->name : 'Wszystkie przypisania' }}"
        >
            @isset($project)
                <x-slot name="right">
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('projects.assignments.create', $project) }}"
                        routeName="projects.assignments.create"
                        action="create"
                    >
                        Przypisz Pracownika
                    </x-ui.button>
                </x-slot>
            @endisset
        </x-ui.page-header>
    </x-slot>

    @isset($project)
        {{-- Widok dla konkretnego projektu - bez Livewire --}}
        
        @if (session('success'))
            <x-ui.alert variant="success" title="Sukces" class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger" title="Błąd" class="mb-3">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <x-ui.card>
            @if($assignments->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Pracownik</th>
                                <th>Rola</th>
                                <th>Od - Do</th>
                                <th>Status</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $assignment)
                                <tr>
                                    <td>
                                        <x-employee-cell :employee="$assignment->employee" />
                                    </td>
                                    <td>
                                        <x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                            $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                            $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                            
                                            $badgeVariant = match($statusValue) {
                                                'active' => 'success',
                                                'completed' => 'info',
                                                'cancelled' => 'danger',
                                                'in_transit' => 'warning',
                                                'at_base' => 'info',
                                                default => 'info'
                                            };
                                        @endphp
                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                    </td>
                                    <td>
                                        <x-action-buttons
                                            viewRoute="{{ route('assignments.show', $assignment) }}"
                                            editRoute="{{ route('assignments.edit', $assignment) }}"
                                            deleteRoute="{{ route('assignments.destroy', $assignment) }}"
                                            deleteMessage="Czy na pewno chcesz usunąć to przypisanie?"
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
                    message="Brak przypisanych pracowników."
                >
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('projects.assignments.create', $project) }}"
                        routeName="projects.assignments.create"
                        action="create"
                    >
                        Przypisz pierwszego pracownika
                    </x-ui.button>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    @else
        {{-- Globalny widok - z Livewire i filtrowaniem --}}
        <livewire:assignments-table />
    @endisset
</x-app-layout>
