<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przygotowanie Anulacji Wyjazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.show', $departure) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @php
        $totalAssignments = $affectedProjectAssignments->count()
            + $affectedVehicleAssignments->count()
            + $affectedAccommodationAssignments->count();
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card>
                <h3 class="fs-5 fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Konsekwencje anulacji wyjazdu
                </h3>

                @if(!$cancellationHasSideEffects)
                    <x-ui.alert type="success" icon="check-circle">
                        <strong>Brak dodatkowych powiązań</strong><br>
                        Ten wyjazd nie ma przypisań, kosztów transportu ani powiązanego transferu z lotniska. Możesz bezpiecznie anulować wyjazd.
                    </x-ui.alert>

                    <form method="POST" action="{{ route('departures.cancel', $departure) }}">
                        @csrf
                        <div class="d-flex gap-2">
                            <x-ui.button 
                                variant="danger" 
                                type="submit"
                            >
                                <i class="bi bi-x-circle me-1"></i>
                                Anuluj Wyjazd
                            </x-ui.button>
                            <x-ui.button 
                                variant="ghost" 
                                href="{{ route('departures.show', $departure) }}"
                            >
                                Rezygnuj
                            </x-ui.button>
                        </div>
                    </form>
                @else
                    <form method="POST" action="{{ route('departures.cancel', $departure) }}">
                        @csrf

                        <x-ui.alert type="warning" icon="exclamation-triangle">
                            <strong>Uwaga!</strong> Anulowanie wyjazdu usuwa przypisania oraz — jeśli jest — anuluje powiązany transfer. Koszty w ewidencji usuwane są tylko wtedy, gdy zaznaczysz je poniżej.
                        </x-ui.alert>

                        @if($hasAssignments)
                            <h5 class="fw-semibold mb-2 mt-4">Przypisania (projekt, pojazd, zakwaterowanie)</h5>
                            <div class="table-responsive mb-4">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pracownik</th>
                                            <th>
                                                <i class="bi bi-briefcase me-1"></i>
                                                Projekt
                                            </th>
                                            <th>
                                                <i class="bi bi-truck me-1"></i>
                                                Pojazd
                                            </th>
                                            <th>
                                                <i class="bi bi-house me-1"></i>
                                                Zakwaterowanie
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cancellationPreviewRows as $row)
                                            @php
                                                $pa = $row['project_assignment'];
                                                $va = $row['vehicle_assignment'];
                                                $aa = $row['accommodation_assignment'];
                                            @endphp
                                            <tr>
                                                <td>
                                                    <x-employee-cell :employee="$row['employee']" />
                                                </td>
                                                <td>
                                                    @if($pa)
                                                        <div>
                                                            <span class="badge bg-success me-1">✓</span>
                                                            <a href="{{ route('project-assignments.show', $pa) }}" class="text-decoration-none">
                                                                {{ $pa->project->name }}
                                                            </a>
                                                        </div>
                                                        <small class="text-muted d-block mt-1">
                                                            {{ $pa->start_date->format('d.m.Y') }} - {{ $pa->end_date ? $pa->end_date->format('d.m.Y') : 'brak daty' }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($va)
                                                        <div>
                                                            <span class="badge bg-success me-1">✓</span>
                                                            <a href="{{ route('vehicle-assignments.show', $va) }}" class="text-decoration-none">
                                                                {{ $va->vehicle->registration_number }}
                                                            </a>
                                                        </div>
                                                        <small class="text-muted d-block mt-1">
                                                            {{ $va->start_date->format('d.m.Y') }} - {{ $va->end_date ? $va->end_date->format('d.m.Y') : 'brak daty' }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($aa)
                                                        <div>
                                                            <span class="badge bg-success me-1">✓</span>
                                                            <a href="{{ route('accommodation-assignments.show', $aa) }}" class="text-decoration-none">
                                                                {{ $aa->accommodation->name }}
                                                            </a>
                                                        </div>
                                                        <small class="text-muted d-block mt-1">
                                                            {{ $aa->start_date->format('d.m.Y') }} - {{ $aa->end_date ? $aa->end_date->format('d.m.Y') : 'brak daty' }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small mb-0 mt-3">
                                Brak przypisań do projektów, pojazdów lub zakwaterowania powiązanych z tym wyjazdem.
                            </p>
                        @endif

                        @if($linkedTransfer)
                            <h5 class="fw-semibold mb-2 mt-4">
                                <i class="bi bi-airplane text-primary me-1"></i>
                                Powiązany transfer (lotnisko → domy)
                            </h5>
                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle">
                                    <tbody>
                                        <tr>
                                            <th class="text-muted small" scope="row" style="width: 11rem;">Trasa</th>
                                            <td class="fw-semibold">
                                                {{ $linkedTransfer->fromLocation?->name ?? '—' }}
                                                <span class="text-muted px-1">→</span>
                                                {{ $linkedTransfer->toLocation?->name ?? '—' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted small" scope="row">Pojazd transferu</th>
                                            <td>
                                                @if($linkedTransfer->vehicle)
                                                    {{ $linkedTransfer->vehicle->registration_number }}
                                                    — {{ $linkedTransfer->vehicle->brand }} {{ $linkedTransfer->vehicle->model }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted small" scope="row">Skutek anulowania</th>
                                            <td class="small text-muted">
                                                Zdarzenie zostanie oznaczone jako <strong class="text-body">anulowane</strong>,
                                                a lista uczestników transferu usunięta.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($showCostRemovalChoices)
                            <h5 class="fw-semibold mb-2 mt-4">Koszty transportu</h5>
                            <p class="small text-muted mb-3">
                                Wybierz, które pozycje chcesz <strong>usunąć z ewidencji</strong> (np. koszt jeszcze nieponiesiony lub zwrot).
                                Niezaznaczone zapisy pozostaną w systemie.
                            </p>
                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 2.75rem;" class="text-center"></th>
                                            <th>Nazwa</th>
                                            <th class="text-end">Kwota</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($fuelCostsSummary)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input m-0" name="remove_fuel" value="1" id="remove_fuel">
                                                </td>
                                                <td>
                                                    <label class="mb-0 fw-semibold" for="remove_fuel">Paliwo</label>
                                                    <div class="small text-muted">Wszystkie pozycje paliw przypisane do tego wyjazdu i transferu</div>
                                                </td>
                                                <td class="text-end text-nowrap">{{ $fuelCostsSummary }}</td>
                                            </tr>
                                        @endif

                                        @if($otherCostsSummary)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input m-0" name="remove_other_costs" value="1" id="remove_other_costs">
                                                </td>
                                                <td>
                                                    <label class="mb-0 fw-semibold" for="remove_other_costs">Inne koszty</label>
                                                    <div class="small text-muted">Parking, opłaty drogowe, inne</div>
                                                </td>
                                                <td class="text-end text-nowrap">{{ $otherCostsSummary }}</td>
                                            </tr>
                                        @endif

                                        @if($transferRewardSummary)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input m-0" name="remove_transfer_reward" value="1" id="remove_transfer_reward">
                                                </td>
                                                <td>
                                                    <label class="mb-0 fw-semibold" for="remove_transfer_reward">Wynagrodzenie za transfer</label>
                                                    <div class="small text-muted">Korekty niepowiązane z rozliczeniem płac</div>
                                                </td>
                                                <td class="text-end text-nowrap">{{ $transferRewardSummary }}</td>
                                            </tr>
                                        @endif

                                        @foreach($ticketRemovalRows as $row)
                                            @php
                                                $tc = $row['cost'];
                                                $ticketLabel = ($tc->description ?: 'Bilet').' ('.$row['eventLabel'].')';
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input m-0"
                                                        name="remove_ticket_ids[]"
                                                        value="{{ $tc->id }}"
                                                        id="ticket_cost_{{ $tc->id }}"
                                                    >
                                                </td>
                                                <td>
                                                    <label class="mb-0 fw-semibold" for="ticket_cost_{{ $tc->id }}">{{ $ticketLabel }}</label>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    {{ number_format((float) $tc->amount, 2) }} {{ $tc->currency }}
                                                </td>
                                            </tr>
                                        @endforeach

                                        @foreach($transferRewardsLocked as $adj)
                                            <tr class="text-muted">
                                                <td class="text-center">—</td>
                                                <td>
                                                    <span class="fw-semibold text-body">Wynagrodzenie za transfer</span>
                                                    <span class="small">(w rozliczeniu płac — nie można usunąć)</span>
                                                    @if($adj->employee)
                                                        <div class="small">{{ $adj->employee->full_name }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    {{ number_format((float) $adj->amount, 2) }} {{ $adj->currency }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="alert alert-danger mb-4">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Podsumowanie skutków
                            </h6>
                            <ul class="mb-0 small ps-3">
                                @if($hasAssignments)
                                    <li>Usunięcie {{ $totalAssignments }} {{ $totalAssignments === 1 ? 'przypisania' : 'przypisań' }} (projekt / pojazd / zakwaterowanie).</li>
                                @endif
                                @if($linkedTransfer)
                                    <li>Anulowanie powiązanego transferu i usunięcie listy uczestników transferu.</li>
                                @endif
                                @if($showCostRemovalChoices)
                                    <li>Koszty i korekty w ewidencji — usunięte zostaną wyłącznie pozycje zaznaczone w tabeli powyżej.</li>
                                @endif
                            </ul>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="accept_consequences" 
                                    name="accept_consequences" 
                                    value="1"
                                    required
                                >
                                <label class="form-check-label" for="accept_consequences">
                                    <strong>Akceptuję konsekwencje anulacji wyjazdu</strong> — rozumiem opisane powyżej działania i chcę anulować ten wyjazd.
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <x-ui.button 
                                variant="danger" 
                                type="submit"
                            >
                                <i class="bi bi-x-circle me-1"></i>
                                Anuluj wyjazd
                            </x-ui.button>
                            <x-ui.button 
                                variant="ghost" 
                                href="{{ route('departures.show', $departure) }}"
                            >
                                Rezygnuj
                            </x-ui.button>
                        </div>
                    </form>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
