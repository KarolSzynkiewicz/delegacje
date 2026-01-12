<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">
                @isset($project)
                    Pracownicy w projekcie: {{ $project->name }}
                @else
                    Wszystkie przypisania
                @endisset
            </h2>
            @isset($project)
                <x-ui.button variant="primary" href="{{ route('projects.assignments.create', $project) }}">
                    <i class="bi bi-plus-circle"></i> Przypisz Pracownika
                </x-ui.button>
            @endisset
        </div>
    </x-slot>

    @isset($project)
        {{-- Widok dla konkretnego projektu - bez Livewire --}}
        <x-ui.card>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-start">Pracownik</th>
                            <th class="text-start">Rola</th>
                            <th class="text-start">Od - Do</th>
                            <th class="text-start">Status</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->employee->full_name }}</td>
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
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Brak przypisanych pracowników
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assignments->hasPages())
                <div class="mt-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </x-ui.card>
    @else
        {{-- Globalny widok - z Livewire i filtrowaniem --}}
        <livewire:assignments-table />
    @endisset
</x-app-layout>
