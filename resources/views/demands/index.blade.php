<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">
                Zapotrzebowanie projektu: {{ $project->name }}
            </h2>
            <x-ui.button variant="primary" href="{{ route('projects.demands.create', $project) }}">
                <i class="bi bi-plus-circle"></i> Dodaj Zapotrzebowanie
            </x-ui.button>
        </div>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-start">Rola</th>
                            <th class="text-start">Liczba osób</th>
                            <th class="text-start">Od - Do</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demands as $demand)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $demand->role->name }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $demand->required_count }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $demand->date_from->format('Y-m-d') }}
                                        @if($demand->date_to)
                                            - {{ $demand->date_to->format('Y-m-d') }}
                                        @else
                                            - ...
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('demands.show', $demand) }}"
                                        editRoute="{{ route('demands.edit', $demand) }}"
                                        deleteRoute="{{ route('demands.destroy', $demand) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to zapotrzebowanie?"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Brak zapotrzebowań dla tego projektu
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($demands->hasPages())
                <div class="mt-3">
                    {{ $demands->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
