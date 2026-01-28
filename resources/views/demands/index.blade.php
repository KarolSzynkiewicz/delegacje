<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zapotrzebowanie projektu: {{ $project->name }}">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('projects.demands.create', $project) }}"
                    routeName="projects.demands.create"
                    action="create"
                >
                    Dodaj Zapotrzebowanie
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <x-ui.card>
        @if($demands->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Rola</th>
                            <th>Liczba osób</th>
                            <th>Od - Do</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demands as $demand)
                            <tr>
                                <td>
                                    <x-ui.badge variant="accent">{{ $demand->role->name }}</x-ui.badge>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $demand->required_count }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $demand->start_date->format('Y-m-d') }}
                                        @if($demand->end_date)
                                            - {{ $demand->end_date->format('Y-m-d') }}
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($demands->hasPages())
                <div class="mt-3">
                    <x-ui.pagination :paginator="$demands" />
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak zapotrzebowań dla tego projektu."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('projects.demands.create', $project) }}"
                    routeName="projects.demands.create"
                    action="create"
                >
                    Dodaj pierwsze zapotrzebowanie
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
