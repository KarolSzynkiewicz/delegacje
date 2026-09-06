{{--
  Cały blok „Szczegóły …”: karta + trip-logistics-header + siatka miejsc (własny) / bilety (publiczny).
  Stan Livewire pozostaje w rodzicu — komponent tylko komponuje widok (jedno miejsce na layout).

  Ten komponent nie dziedziczy automatycznie zmiennych z widoku Livewire — nagłówek wymaga ich w scope,
  więc przekazujemy je przez @include (patrz tablica poniżej).

  @see components.logistics.trip-logistics-header
--}}
@props([
    'tripLogisticsHeader' => [],
    /** Zmienne wymagane przez trip-logistics-header (wire:model / $this w rodzicu Livewire) */
    'endDate' => '',
    'departureDate' => null,
    'returnDate' => null,
    'publicTransportHubKind' => null,
    'sharedStartAirportLocationId' => null,
    'sharedEndAirportLocationId' => null,
    /** Kolekcje z rodzica Livewire (nie używaj $this w zagnieżdżonym include) */
    'availableVehicles' => null,
    'availablePublicTransportHubs' => null,
    'transportMode' => null,
    'vehicleId' => null,
    'selectedVehicle' => null,
    'vehicleSeats' => [],
    'employees' => null,
    'deferSeatGridUntilEmployees' => false,
    'ownTransportEmptyHint' => '',
    'publicTransportEmptyHint' => '',
    'seatGridWireKeyPrefix' => 'vs',
    'publicTicketsSectionTitle' => '',
    'ticketCostsByEmployee' => [],
    'ticketsIncomplete' => false,
    'requireAttachmentTickets' => true,
    'ticketWireKeyPrefix' => 'header-ticket',
    'ticketCostsBindingKey' => 'ticketCostsByEmployee',
    'currencies' => null,
    'attachmentFlatBindingKey' => null,
    'flatAttachmentUploads' => null,
    'linkableDepartures' => null,
    /** Podgląd bez edycji dat / transportu (dopisywanie uczestnika do istniejącego wyjazdu). */
    'readOnly' => false,
    /** Siatka foteli: null = jak !readOnly. true = można zmienić kierowcę mimo zablokowanego nagłówka. */
    'seatsInteractive' => null,
    /** Osoby do kart biletów. Domyślnie = $employees. Siatka miejsc zawsze z $employees. */
    'ticketEmployees' => null,
])

@php
    $tripEmployees = $employees === null ? collect() : (is_array($employees) ? collect($employees) : $employees);
    $ticketPeople = $ticketEmployees === null
        ? $tripEmployees
        : (is_array($ticketEmployees) ? collect($ticketEmployees) : $ticketEmployees);
    $showOwnGrid = $transportMode === 'own' && ! empty($vehicleId) && $selectedVehicle;
    $showPublicTickets = $transportMode === 'public';
    $ownEmptyFallback = 'Wybierz uczestników, aby zobaczyć siatkę miejsc.';
    $publicEmptyFallback = 'Wybierz uczestników, aby uzupełnić bilety.';
    $flatUploads = $flatAttachmentUploads ?? [];
    $headerVehicles = $availableVehicles ?? collect();
    $headerHubs = $availablePublicTransportHubs ?? collect();
    $headerLinkableDepartures = $linkableDepartures ?? collect();
    $seatsAreInteractive = $seatsInteractive === null ? ! $readOnly : (bool) $seatsInteractive;
    $headerCfg = $tripLogisticsHeader;
    if ($readOnly) {
        $headerCfg['readOnly'] = true;
    }
@endphp

<x-ui.card {{ $attributes->class(['mb-4']) }}>
    @include('components.logistics.trip-logistics-header', [
        'tripLogisticsHeader' => $headerCfg,
        'endDate' => $endDate,
        'departureDate' => $departureDate,
        'returnDate' => $returnDate,
        'transportMode' => $transportMode,
        'publicTransportHubKind' => $publicTransportHubKind,
        'sharedStartAirportLocationId' => $sharedStartAirportLocationId,
        'sharedEndAirportLocationId' => $sharedEndAirportLocationId,
        'vehicleId' => $vehicleId,
        'availableVehicles' => $headerVehicles,
        'availablePublicTransportHubs' => $headerHubs,
        'linkableDepartures' => $headerLinkableDepartures,
        'selectedVehicle' => $selectedVehicle,
        'readOnly' => $readOnly,
    ])

    @if($showOwnGrid)
        @if($deferSeatGridUntilEmployees && $tripEmployees->isEmpty())
            <div class="mt-3 pt-3 small text-muted" style="border-top: 1px solid rgba(255,255,255,0.08);">
                <i class="bi bi-people me-1"></i>
                {{ $ownTransportEmptyHint !== '' ? $ownTransportEmptyHint : $ownEmptyFallback }}
            </div>
        @else
            <x-logistics.vehicle-seat-grid
                :vehicle="$selectedVehicle"
                :vehicle-seats="$vehicleSeats"
                :selected-employees="$tripEmployees"
                :wire-key-prefix="$seatGridWireKeyPrefix"
                :interactive="$seatsAreInteractive"
            />
            @if($readOnly && $seatsAreInteractive)
                <p class="small text-muted mt-2 mb-0">
                    <i class="bi bi-steering-wheel me-1"></i>
                    Możesz zmienić kierowcę: przeciągnij osobę na ten fotel albo odznacz „Zewnętrzny”, jeśli ktoś z auta może prowadzić.
                </p>
            @endif
        @endif
    @endif

    @if($showPublicTickets)
        @if($ticketPeople->isEmpty())
            <div class="mt-3 pt-3 small text-muted" style="border-top: 1px solid rgba(255,255,255,0.08);">
                <i class="bi bi-ticket-perforated me-1"></i>
                {{ $publicTransportEmptyHint !== '' ? $publicTransportEmptyHint : $publicEmptyFallback }}
            </div>
        @else
            <x-logistics.public-transport-tickets
                variant="cards"
                :section-title="$publicTicketsSectionTitle"
                :employees="$ticketPeople"
                :ticket-costs-by-employee="$ticketCostsByEmployee"
                :tickets-incomplete="$ticketsIncomplete"
                :require-attachment="$requireAttachmentTickets"
                :currencies="$currencies"
                :wire-key-prefix="$ticketWireKeyPrefix"
                :ticket-costs-binding-key="$ticketCostsBindingKey"
                :attachment-flat-binding-key="$attachmentFlatBindingKey"
                :flat-attachment-uploads="$flatUploads"
            />
        @endif
    @endif
</x-ui.card>
