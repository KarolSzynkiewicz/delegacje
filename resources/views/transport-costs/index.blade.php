<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszty Transportu">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('transport-costs.create') }}"
                    routeName="transport-costs.create"
                    action="create"
                >
                    Dodaj Koszt
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @php
            $sortBy = $sortBy ?? 'event_date';
            $sortDir = $sortDir ?? 'desc';
            $toggleDir = function (string $column) use ($sortBy, $sortDir): string {
                return ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
            };
            $sortLink = function (string $column) use ($withoutCost, $toggleDir): string {
                return route('transport-costs.index', array_filter([
                    'without_cost' => $withoutCost ? 1 : null,
                    'sort_by' => $column,
                    'sort_dir' => $toggleDir($column),
                ], fn ($value) => $value !== null));
            };
        @endphp

        <form method="GET" action="{{ route('transport-costs.index') }}" class="mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="without_cost" name="without_cost" value="1" {{ $withoutCost ? 'checked' : '' }}>
                    <label class="form-check-label" for="without_cost">
                        Tylko zdarzenia bez kosztów
                    </label>
                </div>
                <x-ui.button type="submit" variant="ghost">Filtruj</x-ui.button>
                @if($withoutCost)
                    <x-ui.button href="{{ route('transport-costs.index') }}" variant="ghost">Wyczyść</x-ui.button>
                @endif
            </div>
        </form>

        @if($events->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-start">
                                <a href="{{ $sortLink('event_date') }}" class="text-decoration-none">
                                    Daty
                                    @if($sortBy === 'event_date')
                                        <i class="bi bi-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="text-start">Trasa</th>
                            <th class="text-start">Pojazd / Uczestnicy</th>
                            <th class="text-start">
                                <a href="{{ $sortLink('route_distance') }}" class="text-decoration-none">
                                    Dystans
                                    @if($sortBy === 'route_distance')
                                        <i class="bi bi-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="text-start">
                                <a href="{{ $sortLink('costs_count') }}" class="text-decoration-none">
                                    Koszty
                                    @if($sortBy === 'costs_count')
                                        <i class="bi bi-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            @php
                                $eventShowRoute = $event->type->value === 'departure'
                                    ? route('departures.show', $event)
                                    : route('return-trips.show', $event);
                                $groupedCosts = $event->transportCosts->groupBy('cost_type');
                                $currencySummary = $event->transportCosts
                                    ->groupBy('currency')
                                    ->map(fn($items) => number_format((float) $items->sum('amount'), 2) . ' ' . $items->first()->currency)
                                    ->values();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <a href="{{ $eventShowRoute }}" class="text-decoration-none">
                                            {{ $event->type->label() }} #{{ $event->id }}
                                        </a>
                                    </div>
                                    <small class="text-muted d-block">Start: {{ $event->event_date?->format('d.m.Y H:i') ?? '-' }}</small>
                                    <small class="text-muted d-block">Koniec: {{ $event->end_date?->format('d.m.Y H:i') ?? '-' }}</small>
                                </td>
                                <td>
                                    <div>{{ $event->fromLocation->name ?? '-' }} <span class="text-muted">→</span> {{ $event->toLocation->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $event->vehicle?->registration_number ?? 'Brak auta' }}</div>
                                    <small class="text-muted">{{ $event->participants->pluck('employee_id')->unique()->count() }} uczestników</small>
                                </td>
                                <td>
                                    @if(!is_null($event->route_distance))
                                        {{ number_format((float) $event->route_distance, 1) }} km
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td style="min-width: 380px;">
                                    @if($event->costs_count > 0)
                                        <div class="mb-2">
                                            <span class="fw-semibold">Rekordów: {{ $event->costs_count }}</span>
                                            <small class="text-muted ms-2">
                                                Suma: {{ $currencySummary->join(', ') }}
                                            </small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            @foreach($groupedCosts as $type => $items)
                                                <x-ui.badge variant="accent">{{ ucfirst($type) }}: {{ $items->count() }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                        <div class="small">
                                            @foreach($event->transportCosts as $cost)
                                                <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1">
                                                    <div>
                                                        <span class="fw-semibold">{{ ucfirst($cost->cost_type) }}</span>
                                                        <span class="text-muted">- {{ number_format((float) $cost->amount, 2) }} {{ $cost->currency }}</span>
                                                        @if($cost->description)
                                                            <span class="text-muted">| {{ $cost->description }}</span>
                                                        @endif
                                                    </div>
                                                    <x-action-buttons
                                                        viewRoute="{{ route('transport-costs.show', $cost) }}"
                                                        editRoute="{{ route('transport-costs.edit', $cost) }}"
                                                        deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
                                                        deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                                    />
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">Brak kosztów</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <x-ui.button
                                            variant="primary"
                                            href="{{ route('transport-costs.create', [
                                                'logistics_event_id' => $event->id,
                                                'cost_type' => 'ticket',
                                                'cost_date' => $event->event_date?->format('Y-m-d'),
                                                'description' => 'Bilet - zdarzenie #' . $event->id,
                                            ]) }}"
                                            action="create"
                                        >
                                            + Bilet
                                        </x-ui.button>
                                        <x-ui.button
                                            variant="ghost"
                                            href="{{ route('transport-costs.create', [
                                                'logistics_event_id' => $event->id,
                                                'cost_type' => 'fuel',
                                                'cost_date' => $event->event_date?->format('Y-m-d'),
                                                'description' => 'Paliwo - zdarzenie #' . $event->id,
                                            ]) }}"
                                            action="create"
                                        >
                                            + Paliwo
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="mt-3">
                    <x-ui.pagination :paginator="$events" />
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak zdarzeń logistycznych spełniających filtr."
            />
        @endif
    </x-ui.card>
</x-app-layout>
