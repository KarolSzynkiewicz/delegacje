<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wyjazdy">
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.create') }}"
                    routeName="departures.create"
                    action="create"
                >
                    Utwórz Wyjazd
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Data wyjazdu</th>
                        <th class="text-start">Z</th>
                        <th class="text-start">Do</th>
                        <th class="text-start">Pojazd</th>
                        <th class="text-start">Uczestnicy</th>
                        <th class="text-start">Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departures as $departure)
                        <tr>
                            <td>{{ $departure->event_date->format('Y-m-d H:i') }}</td>
                            <td>{{ $departure->fromLocation->name }}</td>
                            <td>{{ $departure->toLocation->name }}</td>
                            <td>
                                @if($departure->vehicle)
                                    {{ $departure->vehicle->registration_number }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $departure->participants->count() }} osób</td>
                            <td>
                                @php
                                    $badgeVariant = match($departure->status->value) {
                                        'planned' => 'primary',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'accent'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $departure->status->label() }}</x-ui.badge>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <x-ui.button variant="ghost" href="{{ route('departures.show', $departure) }}" class="btn-sm">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </x-ui.button>
                                    @if($departure->status !== \App\Enums\LogisticsEventStatus::CANCELLED)
                                        <x-ui.button variant="ghost" href="{{ route('departures.edit', $departure) }}" class="btn-sm">
                                            <i class="bi bi-pencil"></i>
                                            <span class="d-none d-sm-inline ms-1">Edytuj</span>
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="airplane"
                            message="Brak wyjazdów"
                            :in-table="true"
                            colspan="7"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($departures->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $departures->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
