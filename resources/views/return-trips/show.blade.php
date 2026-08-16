<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Zjazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('return-trips.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($returnTrip->status === \App\Enums\LogisticsEventStatus::PLANNED)
                    <form method="POST" action="{{ route('return-trips.cancel', $returnTrip) }}" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz anulować ten zjazd?');">
                        @csrf
                        <x-ui.button 
                            variant="danger" 
                            type="submit"
                            action="cancel"
                        >
                            Anuluj Zjazd
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">
            {{ session('error') }}
        </x-alert>
    @endif

    <x-ui.card label="Informacje podstawowe">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Data zjazdu</h6>
                            <p class="fw-semibold">{{ $returnTrip->event_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Pojazd</h6>
                            <p class="fw-semibold">
                                {{ $returnTrip->vehicle ? $returnTrip->vehicle->registration_number . ' - ' . $returnTrip->vehicle->brand . ' ' . $returnTrip->vehicle->model : '-' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Z</h6>
                            <p class="fw-semibold">{{ $returnTrip->fromLocation->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Do</h6>
                            <p class="fw-semibold">{{ $returnTrip->toLocation->name }}</p>
                        </div>
                        @if($returnTrip->notes)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Notatki</h6>
                            <p>{{ $returnTrip->notes }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="border-top pt-4">
                        @php
                            // Get unique employees (one employee can have multiple assignments)
                            // Also remove duplicate assignments (same employee_id + assignment_type + assignment_id)
                            $uniqueEmployees = $returnTrip->participants
                                ->groupBy('employee_id')
                                ->map(function($participants) {
                                    // Remove duplicate assignments (same assignment_type + assignment_id)
                                    $uniqueAssignments = $participants
                                        ->unique(function($p) {
                                            return $p->assignment_type . '_' . $p->assignment_id;
                                        })
                                        ->map(function($p) {
                                            return [
                                                'type' => $p->assignment_type,
                                                'assignment' => $p->assignment,
                                                'assignment_id' => $p->assignment_id,
                                            ];
                                        })
                                        ->values();
                                    
                                    return [
                                        'employee' => $participants->first()->employee,
                                        'assignments' => $uniqueAssignments,
                                    ];
                                });
                            $uniqueEmployeeCount = $uniqueEmployees->count();
                        @endphp
                        <h5 class="fw-bold text-dark mb-4">Uczestnicy ({{ $uniqueEmployeeCount }} {{ $uniqueEmployeeCount == 1 ? 'osoba' : 'osób' }})</h5>
                        @if($returnTrip->status === \App\Enums\LogisticsEventStatus::CANCELLED)
                            <x-ui.alert variant="info" class="mb-3">
                                Ten zjazd został anulowany. Wszystkie przypisania zostały cofnięte do oryginalnych dat końcowych.
                            </x-ui.alert>
                        @endif
                        @if($uniqueEmployees->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-start">Pracownik</th>
                                            <th class="text-start">Przypisania</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($uniqueEmployees as $employeeData)
                                            <tr>
                                                <td>
                                                    <x-employee-cell :employee="$employeeData['employee']"  />
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach($employeeData['assignments'] as $assignmentData)
                                                            @php
                                                                $assignmentTypeLabel = match($assignmentData['type']) {
                                                                    'project_assignment' => 'Projekt',
                                                                    'accommodation_assignment' => 'Mieszkanie',
                                                                    'vehicle_assignment' => 'Auto',
                                                                    'rotation' => 'Rotacja',
                                                                    default => $assignmentData['type'],
                                                                };
                                                            @endphp
                                                            <x-ui.badge variant="info" class="small">
                                                                {{ $assignmentTypeLabel }}
                                                                @if($assignmentData['assignment'])
                                                                    #{{ $assignmentData['assignment_id'] }}
                                                                @endif
                                                            </x-ui.badge>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak uczestników</p>
                        @endif
                    </div>
    </x-ui.card>

    @php
        $costTypeLabels = [
            'ticket' => 'Bilet',
            'fuel' => 'Paliwo',
            'parking' => 'Parking',
            'toll' => 'Opłata drogowa',
            'other' => 'Inne',
        ];
        $costTypeIcons = [
            'ticket' => 'ticket-perforated',
            'fuel' => 'fuel-pump',
            'parking' => 'p-square',
            'toll' => 'sign-turn-right',
            'other' => 'three-dots',
        ];
        $transportCosts = $returnTrip->transportCosts;
        $costsTotalByCurrency = $transportCosts
            ->groupBy('currency')
            ->map(fn ($items, $currency) => number_format($items->sum('amount'), 2).' '.$currency)
            ->values();
    @endphp

    <x-ui.card label="Koszty transportu i bilety">
        <div class="d-flex justify-content-end gap-2 mb-3">
            <x-ui.button
                variant="primary"
                href="{{ route('transport-costs.create', [
                    'logistics_event_id' => $returnTrip->id,
                    'cost_type' => 'ticket',
                    'cost_date' => $returnTrip->event_date?->format('Y-m-d'),
                    'description' => 'Bilet - zjazd #'.$returnTrip->id,
                ]) }}"
                action="create"
            >
                + Bilet
            </x-ui.button>
            <x-ui.button
                variant="ghost"
                href="{{ route('transport-costs.create', [
                    'logistics_event_id' => $returnTrip->id,
                    'cost_type' => 'fuel',
                    'cost_date' => $returnTrip->event_date?->format('Y-m-d'),
                    'description' => 'Paliwo - zjazd #'.$returnTrip->id,
                ]) }}"
                action="create"
            >
                + Paliwo
            </x-ui.button>
        </div>

        @if($transportCosts->isNotEmpty())
            @if($costsTotalByCurrency->isNotEmpty())
                <div class="mb-3">
                    <span class="fw-semibold">Pozycji: {{ $transportCosts->count() }}</span>
                    <span class="text-muted ms-2">Suma: {{ $costsTotalByCurrency->join(', ') }}</span>
                </div>
            @endif
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-start">Typ</th>
                            <th class="text-start">Kwota</th>
                            <th class="text-start">Data</th>
                            <th class="text-start">Opis</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transportCosts as $cost)
                            <tr>
                                <td>
                                    <x-ui.badge variant="info">
                                        <i class="bi bi-{{ $costTypeIcons[$cost->cost_type] ?? 'three-dots' }} me-1"></i>
                                        {{ $costTypeLabels[$cost->cost_type] ?? ucfirst($cost->cost_type) }}
                                    </x-ui.badge>
                                </td>
                                <td class="fw-semibold text-nowrap">
                                    {{ number_format((float) $cost->amount, 2) }} {{ $cost->currency }}
                                </td>
                                <td class="text-nowrap">{{ $cost->cost_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $cost->description ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-buttons
                                        viewRoute="{{ route('transport-costs.show', $cost) }}"
                                        editRoute="{{ route('transport-costs.edit', $cost) }}"
                                        deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Brak powiązanych kosztów transportu.</p>
        @endif
    </x-ui.card>

    <div class="mb-4">
        <livewire:logistics-event-warehouse-transfers :event="$returnTrip" :key="'rt-wh-mm-'.$returnTrip->id" />
    </div>

    <x-comments
        :commentable="$returnTrip"
        commentable-type="logistics_event"
    />
</x-app-layout>
