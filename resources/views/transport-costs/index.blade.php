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
                            <th class="text-start">Koszt / os.</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            @php
                                $eventShowRoute = match($event->type->value) {
                                    'departure' => route('departures.show', $event),
                                    'return'    => route('return-trips.show', $event),
                                    'transfer'  => route('transfers.show', $event),
                                    default     => route('departures.show', $event),
                                };

                                $isTransfer = $event->type->value === 'transfer';

                                // Koszty transportu (paliwo, bilety, inne)
                                $groupedCosts = $event->transportCosts->groupBy('cost_type');

                                // Wynagrodzenia kierowcy (Adjustment::bonus z transfer)
                                $driverAdjs = $event->driverAdjustments ?? collect();

                                // Suma walutowa: koszty transportu + wynagrodzenia kierowcy
                                $allAmountsByCurrency = collect();
                                foreach ($event->transportCosts as $tc) {
                                    $allAmountsByCurrency->push(['amount' => (float) $tc->amount, 'currency' => $tc->currency]);
                                }
                                foreach ($driverAdjs as $adj) {
                                    $allAmountsByCurrency->push(['amount' => (float) $adj->amount, 'currency' => $adj->currency]);
                                }
                                $currencySummary = $allAmountsByCurrency
                                    ->groupBy('currency')
                                    ->map(fn($items, $currency) => number_format($items->sum('amount'), 2) . ' ' . $currency)
                                    ->values();

                                $totalPositions = $event->costs_count + $driverAdjs->count();

                                // Koszt na osobę (per waluta)
                                $participantCount = $event->participants->pluck('employee_id')->unique()->count();
                                $costPerPerson = $participantCount > 0
                                    ? $allAmountsByCurrency
                                        ->groupBy('currency')
                                        ->map(fn($items, $currency) => number_format($items->sum('amount') / $participantCount, 2) . ' ' . $currency)
                                        ->values()
                                    : collect();
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
                                <td style="min-width: 420px;">
                                    @if($totalPositions > 0)
                                        <div class="mb-2">
                                            <span class="fw-semibold">Pozycji: {{ $totalPositions }}</span>
                                            @if($currencySummary->count())
                                                <small class="text-muted ms-2">Suma: {{ $currencySummary->join(', ') }}</small>
                                            @endif
                                        </div>

                                        <div class="small vstack gap-1">
                                            {{-- Bilety (transport costs ticket) --}}
                                            @if($groupedCosts->has('ticket'))
                                                @php
                                                    $tickets = $groupedCosts->get('ticket');
                                                    $ticketSumByCur = $tickets->groupBy('currency')
                                                        ->map(fn($items, $cur) => number_format($items->sum('amount'), 2) . ' ' . $cur)
                                                        ->values()->join(', ');
                                                @endphp
                                                <div class="border rounded px-2 py-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <span class="fw-semibold"><i class="bi bi-ticket-perforated me-1"></i>Cena biletów (łącznie)</span>
                                                            <span class="text-muted ms-1">— {{ $ticketSumByCur }}</span>
                                                        </div>
                                                        <div class="d-flex gap-1 ms-2 flex-shrink-0">
                                                            @foreach($tickets as $cost)
                                                                <x-action-buttons
                                                                    viewRoute="{{ route('transport-costs.show', $cost) }}"
                                                                    editRoute="{{ route('transport-costs.edit', $cost) }}"
                                                                    deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
                                                                    deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                                                />
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @foreach($tickets as $cost)
                                                        <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                            {{ number_format((float) $cost->amount, 2) }} {{ $cost->currency }}
                                                            @if($cost->description) — {{ $cost->description }} @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Wynagrodzenie kierowcy (Adjustment) --}}
                                            @foreach($driverAdjs as $adj)
                                                <div class="border rounded px-2 py-1" style="border-color: rgba(99,102,241,0.4) !important;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="fw-semibold"><i class="bi bi-person-badge me-1"></i>Wynagrodzenie za transfer</span>
                                                            <span class="text-muted ms-1">— {{ number_format((float) $adj->amount, 2) }} {{ $adj->currency }}</span>
                                                            @if($adj->employee)
                                                                <span class="text-muted ms-1">| {{ $adj->employee->full_name }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- Paliwo --}}
                                            @if($groupedCosts->has('fuel'))
                                                @php
                                                    $fuels = $groupedCosts->get('fuel');
                                                    $fuelSumByCur = $fuels->groupBy('currency')
                                                        ->map(fn($items, $cur) => number_format($items->sum('amount'), 2) . ' ' . $cur)
                                                        ->values()->join(', ');
                                                @endphp
                                                <div class="border rounded px-2 py-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <span class="fw-semibold"><i class="bi bi-fuel-pump me-1"></i>Paliwo</span>
                                                            <span class="text-muted ms-1">— {{ $fuelSumByCur }}</span>
                                                        </div>
                                                        <div class="d-flex gap-1 ms-2 flex-shrink-0">
                                                            @foreach($fuels as $cost)
                                                                <x-action-buttons
                                                                    viewRoute="{{ route('transport-costs.show', $cost) }}"
                                                                    editRoute="{{ route('transport-costs.edit', $cost) }}"
                                                                    deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
                                                                    deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                                                />
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @foreach($fuels as $cost)
                                                        <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                            {{ number_format((float) $cost->amount, 2) }} {{ $cost->currency }}
                                                            @if($cost->description) — {{ $cost->description }} @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Pozostałe typy (parking, toll, other) --}}
                                            @foreach(['parking', 'toll', 'other'] as $otherType)
                                                @if($groupedCosts->has($otherType))
                                                    @php
                                                        $otherItems = $groupedCosts->get($otherType);
                                                        $otherLabel = ['parking' => 'Parking', 'toll' => 'Opłata drogowa', 'other' => 'Inne'][$otherType];
                                                        $otherIcon  = ['parking' => 'p-square', 'toll' => 'sign-turn-right', 'other' => 'three-dots'][$otherType];
                                                        $otherSum = $otherItems->groupBy('currency')
                                                            ->map(fn($items, $cur) => number_format($items->sum('amount'), 2) . ' ' . $cur)
                                                            ->values()->join(', ');
                                                    @endphp
                                                    <div class="border rounded px-2 py-1">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <span class="fw-semibold"><i class="bi bi-{{ $otherIcon }} me-1"></i>{{ $otherLabel }}</span>
                                                                <span class="text-muted ms-1">— {{ $otherSum }}</span>
                                                            </div>
                                                            <div class="d-flex gap-1 ms-2 flex-shrink-0">
                                                                @foreach($otherItems as $cost)
                                                                    <x-action-buttons
                                                                        viewRoute="{{ route('transport-costs.show', $cost) }}"
                                                                        editRoute="{{ route('transport-costs.edit', $cost) }}"
                                                                        deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
                                                                        deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                                                    />
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        @foreach($otherItems as $cost)
                                                            <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                                {{ number_format((float) $cost->amount, 2) }} {{ $cost->currency }}
                                                                @if($cost->description) — {{ $cost->description }} @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">Brak kosztów</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($participantCount > 0 && $costPerPerson->count())
                                        <div class="fw-semibold">
                                            {!! $costPerPerson->join('<br>') !!}
                                        </div>
                                        <small class="text-muted">{{ $participantCount }} os.</small>
                                    @elseif($participantCount === 0)
                                        <span class="text-muted">Brak uczestników</span>
                                    @else
                                        <span class="text-muted">Brak kosztów</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if(!$isTransfer)
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
                                        @endif
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
                                        @if($isTransfer)
                                            <x-ui.button
                                                variant="ghost"
                                                href="{{ route('transfers.show', $event) }}"
                                            >
                                                <i class="bi bi-arrow-right me-1"></i>Transfer
                                            </x-ui.button>
                                        @endif
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
