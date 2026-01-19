<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wszystkie zapotrzebowania projektów" />
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    @forelse ($demands as $projectId => $projectDemands)
        @php
            $project = $projectDemands->first()->project;
            $sortedDemands = $projectDemands->sortBy('start_date');
        @endphp
        <x-ui.card label="{{ $project->name }}" class="mb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-start">Rola</th>
                                <th class="text-start">Liczba osób</th>
                                <th class="text-start">Od - Do</th>
                                <th class="text-start">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sortedDemands as $demand)
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
        </x-ui.card>
    @empty
        <x-ui.empty-state 
            icon="inbox" 
            message="Brak zapotrzebowań"
        />
    @endforelse
</x-app-layout>
