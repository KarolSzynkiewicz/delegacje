<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszty Zmienne Projektów">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('project-variable-costs.create') }}"
                    routeName="project-variable-costs.create"
                    action="create"
                >
                    Dodaj Koszt
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @if($costs->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Projekt</th>
                            <th>Nazwa</th>
                            <th>Kwota</th>
                            <th>Notatki</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                            <tbody>
                                @foreach ($costs as $cost)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.show', $cost->project) }}" class="text-decoration-none">
                                                {{ $cost->project->name }}
                                            </a>
                                        </td>
                                        <td>{{ $cost->name }}</td>
                                        <td class="fw-semibold">{{ number_format($cost->amount, 2) }} {{ $cost->currency }}</td>
                                        <td>{{ $cost->notes ?? '-' }}</td>
                                        <td>
                                            <x-action-buttons
                                                viewRoute="{{ route('project-variable-costs.show', $cost) }}"
                                                editRoute="{{ route('project-variable-costs.edit', $cost) }}"
                                                deleteRoute="{{ route('project-variable-costs.destroy', $cost) }}"
                                                deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($costs->hasPages())
                        <div class="mt-3">
                            <x-ui.pagination :paginator="$costs" />
                        </div>
                    @endif
        @else
            <x-ui.empty-state 
                icon="folder-x"
                message="Brak kosztów zmiennych w systemie"
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('project-variable-costs.create') }}"
                    routeName="project-variable-costs.create"
                    action="create"
                >
                    Dodaj pierwszy koszt
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
