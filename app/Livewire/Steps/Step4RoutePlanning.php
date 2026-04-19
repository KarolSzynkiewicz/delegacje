<?php

namespace App\Livewire\Steps;

use App\Enums\Currency;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\GeocodingService;
use App\Services\LocationTrackingService;
use App\Services\RoutePlanningService;
use App\Support\PublicTransportTicketCosts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;

class Step4RoutePlanning extends Component
{
    use WithFileUploads;

    // Dane otrzymane z rodzica (read-only)
    public $departureDate;

    public $endDate;

    public $vehicleId;

    public $accommodationAssignments = [];

    public $assignmentRanges = [];

    public $vehicleAssignments = [];

    /** Bilety lotnicze z nagłówka wyjazdu — reactive, żeby przebudowa kroku 4 nie rozjeżdżała załączników względem rodzica. */
    #[Reactive]
    public $ticketCostsByEmployee = [];

    // Shared airports (for public transport)
    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    // Dane trasy
    public $routeWaypoints = []; // Array of accommodation IDs in order

    public $routeData = null;

    public $isPlanningRoute = false;

    public $routeError = null;

    /** Błędy planowania / walidacji odcinka „na lotnisko” (osobno od routeError = transfer po locie). */
    public $preRouteError = null;

    // Transfer config (only for public transport)
    public $transferVehicleId = null;

    public $transferDriverEmployeeId = null;

    public $transferDriverBonusAmount = null;

    public $transferDriverBonusCurrency = 'PLN';

    public $transferPickupLocationId = null; // optional: where transfer car departs from before airport

    // Optional manual extra stop (user-added) — tylko tryb własny samochód (nie transport publiczny)
    public $extraStopLocationId = null;

    /** null = nie skonfigurowano karty; public = powszechny; own = pojazd firmy (car/other poniżej) */
    public ?string $transferToAirportLegKind = null;

    /** car = liczenie ORS; other = bez km (np. autobus prywatnie) — tylko przy transferToAirportLegKind === 'own' */
    public string $transferToAirportGroundMode = 'car';

    /** Zwinięty widok po „Zatwierdź” — transfer własny przed lotem, tryb samochód. */
    public bool $preTransferCarSectionCollapsed = false;

    /** Zwinięty widok po „Zatwierdź” — transfer własny przed lotem, tryb inny transport (bilety). */
    public bool $preTransferOtherSectionCollapsed = false;

    /** Modal: konfiguracja środka (samochód / inny transport, bilety, pojazd). */
    public bool $showPreTransferConfigModal = false;

    /**
     * Roboczy tryb środka w modalu (Samochód / Inny transport).
     * Osobno od {@see $transferToAirportGroundMode}, żeby przełączanie w oknie nie wywoływało
     * `updatedTransferToAirportGroundMode` ani nie znikało okna. Zapis do właściwego pola przy „Zatwierdź”.
     */
    public ?string $preTransferConfigModalGroundMode = null;

    /** Potwierdzenie przełączenia typu odcinka (public / samochód / inny) w modalu. */
    public bool $showPreTransferGroundModeSwitchModal = false;

    /** Docelowy segment po potwierdzeniu utraty danych (public|car|other). */
    public ?string $pendingPreTransferModalSegment = null;

    /** @var 'car'|'other'|null */
    public ?string $pendingPreTransferModalGroundMode = null;

    /** Modal: trasa na lotnisko startowe (przystanki / przelicz / km). */
    public bool $showPreTransferRouteModal = false;

    /** Modal: trasa i dystans lotnisko docelowe → domy (jak pre-transfer route modal). */
    public bool $showPostTransferRouteModal = false;

    /** Modal: konfiguracja transferu z lotniska docelowego (własny środek). */
    public bool $showPostTransferConfigModal = false;

    /** @var 'car'|'other'|null Roboczy tryb w modalu — zapis do {@see} przy „Zatwierdź”. */
    public ?string $postTransferConfigModalGroundMode = null;

    public bool $showPostTransferGroundModeSwitchModal = false;

    /** @var 'car'|'other'|null */
    public ?string $pendingPostTransferModalGroundMode = null;

    public bool $postTransferCarSectionCollapsed = false;

    public bool $postTransferOtherSectionCollapsed = false;

    /** Karta „Lotnisko” — opcjonalne podsumowanie (UI); null = pusty stan */
    public ?string $airportHubLegKind = null;

    public bool $airportHubModePickerOpen = false;

    /**
     * Kolejność odcinka „na lotnisko”: tokeny `base`, `sap` (lotnisko startowe z nagłówka), `loc:ID`.
     * Dowolna kolejność — `sap` jest wymagany dokładnie raz (nieusuwalny), `base` opcjonalny.
     */
    public array $transferToAirportWaypoints = [];

    public $transferToAirportExtraStopLocationId = null;

    /** Notatki loc: tylko dla przystanków w transferToAirportWaypoints */
    public array $transferToAirportLocationStopNotes = [];

    /**
     * Odcinek zawiera token `base` — synchronizowane z transferToAirportWaypoints; zapis do segmentu jako starts_from_base.
     */
    public bool $transferToAirportStartsFromBase = true;

    /** Metryki odcinka przed lotem (distance km, duration sek.) */
    public $preRouteData = null;

    public bool $isManualPreRouteDistance = false;

    public $preManualRouteDistanceKm = null;

    public $preManualRouteDurationMinutes = null;

    /** null = przy pierwszym renderze uzupełniane z tras; public / own — transfer po locie */
    public ?string $transferFromAirportLegKind = null;

    /**
     * Czy użytkownik włączył kartę transferu lotnisko docelowe → domy (odpowiada segmentowi from_airport).
     * false = opcjonalny odcinek pominięty (jak transfer przed lotem) — bez wymuszania km/biletów.
     */
    public bool $postAirportTransferUserEnabled = false;

    public bool $transferFromAirportModePickerOpen = false;

    /** Odcinek po locie (own): samochód vs inny transport — przy public ignorowane (jak „inny”) */
    public string $transferFromAirportGroundMode = 'car';

    /** Notatki do przystanków ręcznych (loc) — klucz: id lokalizacji jako string */
    public array $locationStopNotes = [];

    // Manual distance fallback (mainly for transfer when ORS cannot route)
    public $manualRouteDistanceKm = null;

    public $manualRouteDurationMinutes = null;

    public $isManualRouteDistance = false;

    public $manualRouteHint = null; // e.g. failing location name from ORS

    /** Segmenty trasy (z rodzica) — kolejność lot ↔ transfer ziemny. */
    public array $routeSegments = [];

    /** ID pracowników wybranych w wyjeździe (bilety odcinków ziemnych). */
    public array $selectedEmployeeIds = [];

    /**
     * Koszty „ziemne” (kwota, waluta, załącznik) — transport publiczny na odcinek przed/po locie.
     * Struktura jak ticketCostsByEmployee w nagłówku.
     *
     * @var array<int|string, array<string, mixed>>
     */
    public array $toAirportPublicTicketCostsByEmployee = [];

    /** @var array<int|string, array<string, mixed>> */
    public array $fromAirportPublicTicketCostsByEmployee = [];

    /**
     * Płaskie sloty plików dla biletów odcinka „z lotniska” — wire:model na upload wymaga płytkiego klucza.
     *
     * @var array<int|string, mixed>
     */
    public array $fromAirportTicketFiles = [];

    /** @var array<int|string, mixed> */
    public array $toAirportTicketFiles = [];

    /** Pojazd/kierowca tylko dla odcinka baza → lotnisko startowe (gdy „Autem”). */
    public $preTransferVehicleId = null;

    public $preTransferDriverEmployeeId = null;

    public $preTransferDriverBonusAmount = null;

    public $preTransferDriverBonusCurrency = 'PLN';

    /** Inny transport (przed lotem): opis odcinka kolejowy — bez mapy waypointów. */
    public ?string $preTransferPublicStationStart = null;

    public ?string $preTransferPublicStationEnd = null;

    /** airport | station — z planu trasy (do podpisu karty środkowej). */
    public ?string $publicTransportHubKind = null;

    // Internal IDs (no Eloquent objects as public props)
    public $baseLocationId = null;

    public $accommodationIds = [];

    protected $geocodingService;

    protected $routePlanningService;

    public function boot(GeocodingService $geocodingService, RoutePlanningService $routePlanningService)
    {
        $this->geocodingService = $geocodingService;
        $this->routePlanningService = $routePlanningService;
    }

    public function mount(
        $departureDate,
        $endDate,
        $vehicleId = null,
        $accommodationAssignments = [],
        $assignmentRanges = [],
        $vehicleAssignments = [],
        $sharedStartAirportLocationId = null,
        $sharedEndAirportLocationId = null,
        $initialRouteWaypoints = [],
        $initialLocationStopNotes = [],
        $initialRouteDistance = null,
        $initialRouteDuration = null,
        $initialRouteManual = false,
        $initialTransferConfig = [],
        $initialRouteSegments = [],
        $selectedEmployeeIds = [],
    ) {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->vehicleId = $vehicleId;
        $this->accommodationAssignments = $accommodationAssignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->vehicleAssignments = $vehicleAssignments;
        $this->sharedStartAirportLocationId = $sharedStartAirportLocationId;
        $this->sharedEndAirportLocationId = $sharedEndAirportLocationId;
        $this->selectedEmployeeIds = array_values(array_map('intval', is_array($selectedEmployeeIds) ? $selectedEmployeeIds : []));

        $this->loadLocations();

        $initialRouteWaypoints = is_array($initialRouteWaypoints) ? $initialRouteWaypoints : [];
        $this->locationStopNotes = is_array($initialLocationStopNotes) ? $initialLocationStopNotes : [];
        $initialTransferConfig = is_array($initialTransferConfig) ? $initialTransferConfig : [];
        $initialRouteSegments = is_array($initialRouteSegments) ? $initialRouteSegments : [];
        if ($initialRouteSegments !== []) {
            $this->routeSegments = $initialRouteSegments;
            $this->hydrateOptionalLegsFromRouteSegments();
        }

        $this->hydrateTransferFieldsFromParent($initialTransferConfig);
        $this->syncPostTransferCollapsedFromStoredState();

        if (! empty($initialRouteWaypoints)) {
            $this->routeWaypoints = array_values($initialRouteWaypoints);
        } else {
            $this->initializeWaypoints();
        }

        $restored = $this->hydrateRouteMetricsFromParent($initialRouteDistance, $initialRouteDuration, (bool) $initialRouteManual);

        // Bez automatycznego wywołania API przy wejściu — użytkownik klika „Przelicz trasę”.
        if ($restored || ! empty($this->routeWaypoints) || ! empty($this->routeSegments)) {
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        }

        $this->dispatchTransferConfig();
    }

    /**
     * Po zmianie przystanków odcinka „z lotniska”: kasuj km/czas tego odcinka (bez API).
     */
    protected function invalidateRouteMetricsAndSyncToParent(): void
    {
        $this->routeData = null;
        $this->isManualRouteDistance = false;
        $this->manualRouteDistanceKm = null;
        $this->manualRouteDurationMinutes = null;
        $this->routeError = null;
        $this->manualRouteHint = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    /**
     * Po zmianie przystanków odcinka „na lotnisko”.
     */
    protected function invalidatePreRouteMetricsAndSyncToParent(): void
    {
        $this->preRouteData = null;
        $this->isManualPreRouteDistance = false;
        $this->preManualRouteDistanceKm = null;
        $this->preManualRouteDurationMinutes = null;
        $this->preRouteError = null;
        $this->manualRouteHint = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    /**
     * Po wczytaniu kroku 4 z zapisu: zwinięty widok (jak po „Zatwierdź” w modalu), jeśli dane są kompletne.
     */
    protected function syncPostTransferCollapsedFromStoredState(): void
    {
        if (! $this->isPublicTransport || ! $this->postAirportTransferUserEnabled) {
            return;
        }
        if (($this->transferFromAirportLegKind ?? null) !== 'own') {
            return;
        }
        if ($this->transferFromAirportGroundMode === 'car') {
            if (! $this->transferVehicleIncomplete && ! $this->transferDriverIncomplete && ! $this->transferBonusIncomplete) {
                $this->postTransferCarSectionCollapsed = true;
            }
        } elseif (! $this->fromAirportGroundTicketsIncomplete) {
            $this->postTransferOtherSectionCollapsed = true;
        }
    }

    protected function hydrateTransferFieldsFromParent(array $tc): void
    {
        if (! empty($this->vehicleId)) {
            return;
        }

        if (array_key_exists('vehicle_id', $tc) && $tc['vehicle_id'] !== null && $tc['vehicle_id'] !== '') {
            $this->transferVehicleId = (int) $tc['vehicle_id'];
        }
        if (array_key_exists('driver_employee_id', $tc) && $tc['driver_employee_id'] !== null && $tc['driver_employee_id'] !== '') {
            $this->transferDriverEmployeeId = (int) $tc['driver_employee_id'];
        }
        if (array_key_exists('bonus_amount', $tc) && $tc['bonus_amount'] !== null && $tc['bonus_amount'] !== '') {
            $this->transferDriverBonusAmount = $tc['bonus_amount'];
        }
        if (! empty($tc['bonus_currency'])) {
            $this->transferDriverBonusCurrency = (string) $tc['bonus_currency'];
        }
        if (array_key_exists('pickup_location_id', $tc) && $tc['pickup_location_id'] !== null && $tc['pickup_location_id'] !== '') {
            $this->transferPickupLocationId = (int) $tc['pickup_location_id'];
        }
    }

    protected function hydrateOptionalLegsFromRouteSegments(): void
    {
        foreach ($this->routeSegments as $seg) {
            if (($seg['mode'] ?? '') !== 'own') {
                continue;
            }
            $leg = $seg['leg'] ?? '';
            if ($leg === 'to_airport') {
                $this->transferToAirportLegKind = $seg['leg_kind'] ?? 'own';
                $preGm = (($seg['ground_mode'] ?? 'car') === 'other') ? 'other' : 'car';
                $this->transferToAirportGroundMode = $preGm;
                $legacyStarts = array_key_exists('starts_from_base', $seg)
                    ? (bool) $seg['starts_from_base']
                    : true;
                $ownOther = ($this->transferToAirportLegKind === 'own' && $preGm === 'other');
                if ($ownOther) {
                    $this->transferToAirportWaypoints = [];
                    $this->transferToAirportLocationStopNotes = [];
                    $this->transferToAirportStartsFromBase = false;
                } else {
                    $this->transferToAirportWaypoints = $this->migratePreTransferWaypointsFromSegment(
                        array_values($seg['route_waypoints'] ?? []),
                        $legacyStarts
                    );
                    $this->ensureSingleSapInPreTransferWaypoints();
                    $this->syncTransferToAirportStartsFromBaseFromWaypoints();
                    $notes = $seg['location_stop_notes'] ?? [];
                    $this->transferToAirportLocationStopNotes = is_array($notes) ? $notes : [];
                }
                $gt = $seg['public_leg_ticket_costs_by_employee'] ?? [];
                $this->toAirportPublicTicketCostsByEmployee = is_array($gt) ? $gt : [];
                $preTc = $seg['transfer_config'] ?? [];
                if (is_array($preTc) && ($this->transferToAirportLegKind ?? 'own') === 'own' && $preGm === 'car') {
                    if (array_key_exists('vehicle_id', $preTc) && $preTc['vehicle_id'] !== null && $preTc['vehicle_id'] !== '') {
                        $this->preTransferVehicleId = (int) $preTc['vehicle_id'];
                    }
                    if (array_key_exists('driver_employee_id', $preTc) && $preTc['driver_employee_id'] !== null && $preTc['driver_employee_id'] !== '') {
                        $this->preTransferDriverEmployeeId = (int) $preTc['driver_employee_id'];
                    }
                    if (array_key_exists('bonus_amount', $preTc) && $preTc['bonus_amount'] !== null && $preTc['bonus_amount'] !== '') {
                        $this->preTransferDriverBonusAmount = $preTc['bonus_amount'];
                    }
                    if (! empty($preTc['bonus_currency'])) {
                        $this->preTransferDriverBonusCurrency = (string) $preTc['bonus_currency'];
                    }
                }
                if ($ownOther && is_array($preTc)) {
                    $this->preTransferPublicStationStart = isset($preTc['public_station_start']) ? (string) $preTc['public_station_start'] : null;
                    $this->preTransferPublicStationEnd = isset($preTc['public_station_end']) ? (string) $preTc['public_station_end'] : null;
                } elseif (! $ownOther) {
                    $this->preTransferPublicStationStart = null;
                    $this->preTransferPublicStationEnd = null;
                }
                $m = $seg['route_metrics'] ?? null;
                if (is_array($m) && isset($m['distance'], $m['duration'])) {
                    $this->preRouteData = [
                        'distance' => (float) $m['distance'],
                        'duration' => (int) $m['duration'],
                    ];
                    $this->isManualPreRouteDistance = (bool) ($m['is_manual'] ?? false);
                    $this->syncPreManualFieldsFromPreRouteData();
                }
            }
            if ($leg === 'from_airport' || $leg === '') {
                $this->transferFromAirportLegKind = $seg['leg_kind'] ?? 'own';
                $this->transferFromAirportGroundMode = (($seg['ground_mode'] ?? 'car') === 'other') ? 'other' : 'car';
                $gf = $seg['public_leg_ticket_costs_by_employee'] ?? [];
                $this->fromAirportPublicTicketCostsByEmployee = is_array($gf) ? $gf : [];
                $fromTc = $seg['transfer_config'] ?? [];
                if (is_array($fromTc) && array_key_exists('pickup_location_id', $fromTc) && $fromTc['pickup_location_id'] !== null && $fromTc['pickup_location_id'] !== '') {
                    $this->transferPickupLocationId = (int) $fromTc['pickup_location_id'];
                }
            }
        }

        $this->postAirportTransferUserEnabled = $this->detectPostAirportSegmentInRouteSegments();
    }

    /** Czy w planie jest segment „ziemny” po locie (lotnisko docelowe → domy). */
    protected function detectPostAirportSegmentInRouteSegments(): bool
    {
        foreach ($this->routeSegments as $seg) {
            if (($seg['mode'] ?? '') !== 'own') {
                continue;
            }
            $leg = $seg['leg'] ?? '';
            if ($leg === 'to_airport') {
                continue;
            }
            if ($leg === 'from_airport' || $leg === '') {
                return true;
            }
        }

        return false;
    }

    protected function syncPreManualFieldsFromPreRouteData(): void
    {
        if (empty($this->preRouteData) || ! isset($this->preRouteData['distance'], $this->preRouteData['duration'])) {
            return;
        }
        $this->preManualRouteDistanceKm = round((float) $this->preRouteData['distance'], 3);
        $secs = (int) $this->preRouteData['duration'];
        $this->preManualRouteDurationMinutes = max(1, (int) round($secs / 60));
    }

    /**
     * @param  list<string>  $raw
     * @return list<string>
     */
    protected function migratePreTransferWaypointsFromSegment(array $raw, ?bool $legacyStartsFromBase): array
    {
        $raw = array_values(array_filter(array_map(
            static fn ($w) => ($w === null || $w === '') ? null : (string) $w,
            $raw
        )));
        $hasSap = in_array('sap', $raw, true);
        $hasBaseToken = in_array('base', $raw, true);
        $onlyLegacyLocs = ! $hasSap && ! $hasBaseToken
            && (empty($raw) || collect($raw)->every(fn ($k) => str_starts_with((string) $k, 'loc:')));
        if ($onlyLegacyLocs) {
            if ($raw === []) {
                return ($legacyStartsFromBase ?? true) ? ['base', 'sap'] : ['sap'];
            }
            if ($legacyStartsFromBase ?? true) {
                return array_merge(['base'], $raw, ['sap']);
            }

            return array_merge($raw, ['sap']);
        }
        if ($raw === []) {
            return ['base', 'sap'];
        }
        if (! $hasSap) {
            $raw[] = 'sap';
        }

        return $this->dedupeSapTokens(array_values($raw));
    }

    /**
     * @param  list<string>  $raw
     * @return list<string>
     */
    protected function dedupeSapTokens(array $raw): array
    {
        $out = [];
        $keptSap = false;
        foreach ($raw as $k) {
            $k = (string) $k;
            if ($k === 'sap') {
                if ($keptSap) {
                    continue;
                }
                $keptSap = true;
            }
            $out[] = $k;
        }

        return array_values($out);
    }

    protected function ensureSingleSapInPreTransferWaypoints(): void
    {
        $wps = $this->transferToAirportWaypoints;
        $sapCount = 0;
        foreach ($wps as $k) {
            if ((string) $k === 'sap') {
                $sapCount++;
            }
        }
        if ($sapCount === 0) {
            $this->transferToAirportWaypoints[] = 'sap';
            $this->transferToAirportWaypoints = array_values($this->transferToAirportWaypoints);

            return;
        }
        if ($sapCount > 1) {
            $this->transferToAirportWaypoints = $this->dedupeSapTokens($wps);
        }
    }

    protected function syncTransferToAirportStartsFromBaseFromWaypoints(): void
    {
        $this->transferToAirportStartsFromBase = in_array('base', $this->transferToAirportWaypoints, true);
    }

    /**
     * Składa routeSegments z UI (lot + opcjonalny transfer przed lotem + transfer po locie).
     */
    protected function rebuildRouteSegmentsFromUiState(): void
    {
        if (! $this->isPublicTransport) {
            return;
        }

        $publicSeg = null;
        foreach ($this->routeSegments as $seg) {
            if (($seg['mode'] ?? '') === 'public') {
                $publicSeg = $seg;

                break;
            }
        }
        if ($publicSeg === null) {
            return;
        }

        $publicSeg['hub_kind'] = $publicSeg['hub_kind'] ?? null;
        $this->publicTransportHubKind = $publicSeg['hub_kind'];
        $publicSeg['start_location_id'] = $this->sharedStartAirportLocationId;
        $publicSeg['end_location_id'] = $this->sharedEndAirportLocationId;

        // Lot (segment publiczny): bilety z nagłówka — nie mieszają się z transferami ziemnymi; bez tego segment tracił
        // ticket_costs_by_employee przy zmianie trybu transferu na lotnisko i rodzic nadpisywał nagłówek pustą tablicą.
        $publicSeg['ticket_costs_by_employee'] = $this->mergePublicFlightTicketCostsForSegment(
            is_array($publicSeg['ticket_costs_by_employee'] ?? null) ? $publicSeg['ticket_costs_by_employee'] : [],
            is_array($this->ticketCostsByEmployee) ? $this->ticketCostsByEmployee : []
        );

        $fromSeg = null;
        if ($this->postAirportTransferUserEnabled) {
            $fromId = null;
            foreach ($this->routeSegments as $seg) {
                if (($seg['mode'] ?? '') === 'own' && (($seg['leg'] ?? '') === 'from_airport' || ($seg['leg'] ?? '') === '')) {
                    $fromId = $seg['id'] ?? null;

                    break;
                }
            }
            if ($fromId === null) {
                $fromId = (string) Str::uuid();
            }

            $postKind = $this->transferFromAirportLegKind
                ?? (count($this->routeWaypoints) > 0 ? 'own' : 'public');
            $fromGround = ($postKind === 'public') ? 'other' : $this->transferFromAirportGroundMode;

            $fromSeg = [
                'id' => $fromId,
                'mode' => 'own',
                'leg' => 'from_airport',
                'leg_kind' => $postKind,
                'ground_mode' => $fromGround,
                'route_waypoints' => array_values($this->routeWaypoints),
                'location_stop_notes' => $this->getLocationStopNotesPayload(),
                'transfer_config' => ($postKind === 'own' && $this->transferFromAirportGroundMode === 'car')
                    ? $this->buildTransferConfigSnapshot()
                    : [],
                'route_metrics' => null,
                'public_leg_ticket_costs_by_employee' => ($postKind === 'public'
                    || ($postKind === 'own' && $this->transferFromAirportGroundMode === 'other'))
                    ? $this->fromAirportPublicTicketCostsByEmployee
                    : [],
            ];
            if (is_array($this->routeData) && isset($this->routeData['distance'], $this->routeData['duration'])) {
                $fromSeg['route_metrics'] = [
                    'distance' => (float) $this->routeData['distance'],
                    'duration' => (int) $this->routeData['duration'],
                    'is_manual' => (bool) $this->isManualRouteDistance,
                ];
            }
        }

        if ($this->transferToAirportLegKind !== null) {
            $ownCarPre = $this->transferToAirportLegKind === 'own' && $this->transferToAirportGroundMode === 'car';
            if ($ownCarPre) {
                $this->ensureSingleSapInPreTransferWaypoints();
                $this->syncTransferToAirportStartsFromBaseFromWaypoints();
            }

            $toId = null;
            foreach ($this->routeSegments as $seg) {
                if (($seg['mode'] ?? '') === 'own' && ($seg['leg'] ?? '') === 'to_airport') {
                    $toId = $seg['id'] ?? null;

                    break;
                }
            }
            if ($toId === null) {
                $toId = (string) Str::uuid();
            }

            $preKind = $this->transferToAirportLegKind;
            $preGround = ($preKind === 'public') ? 'other' : $this->transferToAirportGroundMode;

            $toSeg = [
                'id' => $toId,
                'mode' => 'own',
                'leg' => 'to_airport',
                'leg_kind' => $preKind,
                'ground_mode' => $preGround,
                'route_waypoints' => array_values($this->transferToAirportWaypoints),
                'location_stop_notes' => $this->getPreLocationStopNotesPayload(),
                'transfer_config' => ($preKind === 'own' && $this->transferToAirportGroundMode === 'car')
                    ? $this->buildPreAirportTransferConfigSnapshot()
                    : (($preKind === 'own' && $this->transferToAirportGroundMode === 'other')
                        ? $this->buildPreAirportPublicOtherTransferConfigSnapshot()
                        : []),
                'route_metrics' => null,
                'public_leg_ticket_costs_by_employee' => ($preKind === 'public'
                    || ($preKind === 'own' && $this->transferToAirportGroundMode === 'other'))
                    ? $this->toAirportPublicTicketCostsByEmployee
                    : [],
                'starts_from_base' => $this->transferToAirportStartsFromBase,
            ];
            if (is_array($this->preRouteData) && isset($this->preRouteData['distance'], $this->preRouteData['duration'])) {
                $toSeg['route_metrics'] = [
                    'distance' => (float) $this->preRouteData['distance'],
                    'duration' => (int) $this->preRouteData['duration'],
                    'is_manual' => (bool) $this->isManualPreRouteDistance,
                ];
            }

            $pieces = [$toSeg, $publicSeg];
            if ($fromSeg !== null) {
                $pieces[] = $fromSeg;
            }
            $this->routeSegments = $pieces;
        } else {
            $pieces = [$publicSeg];
            if ($fromSeg !== null) {
                $pieces[] = $fromSeg;
            }
            $this->routeSegments = $pieces;
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $fromSegment
     * @param  array<int|string, array<string, mixed>>  $fromHeader
     * @return array<int|string, array<string, mixed>>
     */
    protected function mergePublicFlightTicketCostsForSegment(array $fromSegment, array $fromHeader): array
    {
        if ($fromHeader !== []) {
            return $fromHeader;
        }

        return $fromSegment;
    }

    protected function getPreLocationStopNotesPayload(): array
    {
        $out = [];
        foreach ($this->transferToAirportWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $id = (string) $p['id'];
                $out[$id] = trim((string) ($this->transferToAirportLocationStopNotes[$id] ?? ''));
            }
        }

        return $out;
    }

    protected function buildPreAirportTransferConfigSnapshot(): array
    {
        return [
            'vehicle_id' => $this->preTransferVehicleId,
            'driver_employee_id' => $this->preTransferDriverEmployeeId,
            'bonus_amount' => $this->preTransferDriverBonusAmount,
            'bonus_currency' => $this->preTransferDriverBonusCurrency,
            'pickup_location_id' => null,
            'route_distance' => is_array($this->preRouteData) ? ($this->preRouteData['distance'] ?? null) : null,
            'route_duration' => is_array($this->preRouteData) ? ($this->preRouteData['duration'] ?? null) : null,
            'route_waypoints' => $this->transferToAirportWaypoints,
            'location_stop_notes' => $this->getPreLocationStopNotesPayload(),
            'end_airport_location_id' => $this->sharedStartAirportLocationId,
            'route_distance_is_manual' => (bool) $this->isManualPreRouteDistance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPreAirportPublicOtherTransferConfigSnapshot(): array
    {
        return [
            'public_station_start' => mb_substr(trim((string) ($this->preTransferPublicStationStart ?? '')), 0, 500),
            'public_station_end' => mb_substr(trim((string) ($this->preTransferPublicStationEnd ?? '')), 0, 500),
        ];
    }

    protected function buildTransferConfigSnapshot(): array
    {
        return [
            'vehicle_id' => $this->transferVehicleId,
            'driver_employee_id' => $this->transferDriverEmployeeId,
            'bonus_amount' => $this->transferDriverBonusAmount,
            'bonus_currency' => $this->transferDriverBonusCurrency,
            'pickup_location_id' => $this->transferPickupLocationId ? (int) $this->transferPickupLocationId : null,
            'route_distance' => $this->routeData['distance'] ?? null,
            'route_duration' => $this->routeData['duration'] ?? null,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->getLocationStopNotesPayload(),
            'end_airport_location_id' => $this->sharedEndAirportLocationId,
            'route_distance_is_manual' => (bool) $this->isManualRouteDistance,
        ];
    }

    public function addTransferToAirportCard(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== null) {
            return;
        }
        $this->preTransferConfigModalGroundMode = null;
        $this->showPreTransferGroundModeSwitchModal = false;
        $this->pendingPreTransferModalSegment = null;
        $this->showPreTransferConfigModal = true;
    }

    public function selectTransferToAirportLegKind(string $kind): void
    {
        if (! $this->isPublicTransport || ! in_array($kind, ['public', 'own'], true)) {
            return;
        }
        $this->preTransferCarSectionCollapsed = false;
        $this->preTransferOtherSectionCollapsed = false;
        $this->transferToAirportLegKind = $kind;
        if ($kind === 'own') {
            $this->transferToAirportGroundMode = 'car';
            $this->toAirportPublicTicketCostsByEmployee = [];
            $this->toAirportTicketFiles = [];
            $this->preTransferConfigModalGroundMode = 'car';
            $this->transferToAirportStartsFromBase = true;
            $this->showPreTransferGroundModeSwitchModal = false;
            $this->pendingPreTransferModalGroundMode = null;
            $this->showPreTransferConfigModal = true;
        } else {
            $this->transferToAirportGroundMode = 'other';
            $this->preTransferVehicleId = null;
            $this->preTransferDriverEmployeeId = null;
            $this->preTransferDriverBonusAmount = null;
            $this->showPreTransferConfigModal = false;
            $this->showPreTransferRouteModal = false;
            $this->preTransferConfigModalGroundMode = null;
            $this->invalidatePreRouteMetricsAndSyncToParent();

            return;
        }
        $this->transferToAirportWaypoints = ['base', 'sap'];
        $this->ensureSingleSapInPreTransferWaypoints();
        $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        $this->preRouteData = null;
        $this->isManualPreRouteDistance = false;
        $this->preRouteError = null;
        $this->preTransferPublicStationStart = null;
        $this->preTransferPublicStationEnd = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function removeTransferToAirportCard(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind === null) {
            return;
        }
        $this->preTransferCarSectionCollapsed = false;
        $this->preTransferOtherSectionCollapsed = false;
        $this->showPreTransferConfigModal = false;
        $this->showPreTransferRouteModal = false;
        $this->showPreTransferGroundModeSwitchModal = false;
        $this->pendingPreTransferModalGroundMode = null;
        $this->preTransferConfigModalGroundMode = null;
        $this->transferToAirportLegKind = null;
        $this->transferToAirportWaypoints = [];
        $this->transferToAirportStartsFromBase = true;
        $this->transferToAirportLocationStopNotes = [];
        $this->toAirportPublicTicketCostsByEmployee = [];
        $this->toAirportTicketFiles = [];
        $this->preTransferVehicleId = null;
        $this->preTransferDriverEmployeeId = null;
        $this->preTransferDriverBonusAmount = null;
        $this->preRouteData = null;
        $this->isManualPreRouteDistance = false;
        $this->preRouteError = null;
        $this->preTransferPublicStationStart = null;
        $this->preTransferPublicStationEnd = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function addAirportHubCard(): void
    {
        if (! $this->isPublicTransport || $this->airportHubLegKind !== null) {
            return;
        }
        $this->airportHubModePickerOpen = true;
    }

    public function cancelAirportHubPicker(): void
    {
        $this->airportHubModePickerOpen = false;
    }

    public function selectAirportHubLegKind(string $kind): void
    {
        if (! $this->isPublicTransport || ! in_array($kind, ['public', 'own'], true)) {
            return;
        }
        $this->airportHubLegKind = $kind;
        $this->airportHubModePickerOpen = false;
    }

    public function removeAirportHubCard(): void
    {
        $this->airportHubLegKind = null;
        $this->airportHubModePickerOpen = false;
    }

    public function addTransferFromAirportCard(): void
    {
        if (! $this->isPublicTransport) {
            return;
        }
        $this->postAirportTransferUserEnabled = true;
        $this->transferFromAirportModePickerOpen = true;
    }

    public function cancelTransferFromAirportPicker(): void
    {
        $this->transferFromAirportModePickerOpen = false;
    }

    public function selectTransferFromAirportLegKind(string $kind): void
    {
        if (! $this->isPublicTransport || ! in_array($kind, ['public', 'own'], true)) {
            return;
        }
        $this->postAirportTransferUserEnabled = true;
        $this->transferFromAirportLegKind = $kind;
        $this->transferFromAirportModePickerOpen = false;
        if ($kind === 'own') {
            $this->transferFromAirportGroundMode = 'car';
            $this->fromAirportPublicTicketCostsByEmployee = [];
            $this->fromAirportTicketFiles = [];
            $this->postTransferConfigModalGroundMode = 'car';
            $this->postTransferCarSectionCollapsed = false;
            $this->postTransferOtherSectionCollapsed = false;
            $this->showPostTransferGroundModeSwitchModal = false;
            $this->pendingPostTransferModalGroundMode = null;
            $this->showPostTransferConfigModal = true;
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();

            return;
        }

        $this->transferFromAirportGroundMode = 'other';
        $this->transferVehicleId = null;
        $this->transferDriverEmployeeId = null;
        $this->transferDriverBonusAmount = null;
        $this->fromAirportPublicTicketCostsByEmployee = [];
        $this->fromAirportTicketFiles = [];
        $this->postTransferCarSectionCollapsed = false;
        $this->postTransferOtherSectionCollapsed = false;
        $this->showPostTransferConfigModal = false;
        $this->postTransferConfigModalGroundMode = null;
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function removeTransferFromAirportCard(): void
    {
        if (! $this->isPublicTransport) {
            return;
        }
        $this->postAirportTransferUserEnabled = false;
        $this->transferFromAirportModePickerOpen = false;
        $this->transferFromAirportLegKind = null;
        $this->transferFromAirportGroundMode = 'car';
        $this->transferVehicleId = null;
        $this->transferDriverEmployeeId = null;
        $this->transferDriverBonusAmount = null;
        $this->transferPickupLocationId = null;
        $this->fromAirportPublicTicketCostsByEmployee = [];
        $this->fromAirportTicketFiles = [];
        $this->showPostTransferRouteModal = false;
        $this->showPostTransferConfigModal = false;
        $this->postTransferConfigModalGroundMode = null;
        $this->showPostTransferGroundModeSwitchModal = false;
        $this->pendingPostTransferModalGroundMode = null;
        $this->postTransferCarSectionCollapsed = false;
        $this->postTransferOtherSectionCollapsed = false;
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    /**
     * Odtwarzanie dystansu/czasu z rodzica (po powrocie z innego kroku — bez wymuszania nowego planowania API).
     */
    protected function hydrateRouteMetricsFromParent($distance, $duration, bool $manual): bool
    {
        if ($distance === null || $distance === '' || $duration === null || $duration === '') {
            return false;
        }
        if (! is_numeric($distance) || (float) $distance <= 0) {
            return false;
        }
        if (! is_numeric($duration) || (int) $duration <= 0) {
            return false;
        }

        $this->routeData = [
            'distance' => (float) $distance,
            'duration' => (int) $duration,
        ];
        $this->isManualRouteDistance = $manual;
        $this->syncManualFieldsFromRouteData();

        return true;
    }

    protected function buildRoutePlannedPayload(): array
    {
        if ($this->isPublicTransport) {
            $this->rebuildRouteSegmentsFromUiState();
        }

        return [
            'route_distance' => $this->routeData['distance'] ?? null,
            'route_duration' => $this->routeData['duration'] ?? null,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->getLocationStopNotesPayload(),
            'route_distance_is_manual' => (bool) $this->isManualRouteDistance,
            'route_segments' => $this->routeSegments,
            'pre_route_distance' => is_array($this->preRouteData) ? ($this->preRouteData['distance'] ?? null) : null,
            'pre_route_duration' => is_array($this->preRouteData) ? ($this->preRouteData['duration'] ?? null) : null,
            'pre_route_distance_is_manual' => (bool) $this->isManualPreRouteDistance,
        ];
    }

    /**
     * Zamiana kolejności dwóch segmentów (np. transfer → lot zamiast lot → transfer).
     */
    public function swapFirstTwoRouteSegments(): void
    {
        if (count($this->routeSegments) !== 2) {
            return;
        }
        $this->routeSegments = [$this->routeSegments[1], $this->routeSegments[0]];
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
    }

    protected function syncManualFieldsFromRouteData(): void
    {
        if (empty($this->routeData) || ! isset($this->routeData['distance'], $this->routeData['duration'])) {
            return;
        }
        $this->manualRouteDistanceKm = round((float) $this->routeData['distance'], 3);
        $secs = (int) $this->routeData['duration'];
        $this->manualRouteDurationMinutes = max(1, (int) round($secs / 60));
    }

    // ─── Location loading ──────────────────────────────────────────────────────

    protected function loadLocations(): void
    {
        // Load base location
        $base = Location::getBase();
        if ($base) {
            $this->baseLocationId = $base->id;
            if (! $base->hasCoordinates()) {
                $this->geocodingService->geocodeLocation($base);
            }
        }

        // Geocode shared airports if needed
        foreach ([$this->sharedStartAirportLocationId, $this->sharedEndAirportLocationId] as $airportId) {
            if ($airportId) {
                $airport = Location::find($airportId);
                if ($airport && ! $airport->hasCoordinates()) {
                    $this->geocodingService->geocodeLocation($airport);
                }
            }
        }

        // Geocode pickup location if set
        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if ($pickup && ! $pickup->hasCoordinates()) {
                $this->geocodingService->geocodeLocation($pickup);
            }
        }

        // Collect unique accommodation IDs
        $accommodationIds = [];
        foreach ($this->accommodationAssignments as $assignment) {
            if (! is_array($assignment) || empty($assignment['accommodation_id'])) {
                continue;
            }
            $accommodationIds[] = (int) $assignment['accommodation_id'];
        }
        $accommodationIds = array_values(array_unique($accommodationIds));

        // Geocode accommodations that are missing coordinates
        $accommodations = Accommodation::whereIn('id', $accommodationIds)->get();
        foreach ($accommodations as $accommodation) {
            if (! $accommodation->hasCoordinates()) {
                $address = $accommodation->getFullAddress();
                if (! empty($address)) {
                    try {
                        $coordinates = $this->geocodingService->geocode($address);
                        if ($coordinates && isset($coordinates['latitude'], $coordinates['longitude'])) {
                            $accommodation->update([
                                'latitude' => $coordinates['latitude'],
                                'longitude' => $coordinates['longitude'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Geocoding exception', ['id' => $accommodation->id, 'error' => $e->getMessage()]);
                    }
                }
            }
        }

        $this->accommodationIds = $accommodationIds;
    }

    /** Notatki przy przystankach loc — wysyłane do rodzica razem z trasą */
    protected function getLocationStopNotesPayload(): array
    {
        $out = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $id = (string) $p['id'];
                $out[$id] = trim((string) ($this->locationStopNotes[$id] ?? ''));
            }
        }

        return $out;
    }

    public function saveLocationStopNotesToParent(): void
    {
        if (empty($this->routeData) || ! isset($this->routeData['distance'], $this->routeData['duration'])) {
            return;
        }
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
    }

    public function updatedLocationStopNotes(): void
    {
        $this->saveLocationStopNotesToParent();
    }

    public function updatedTransferToAirportLocationStopNotes(): void
    {
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
    }

    protected function pruneLocationStopNotes(): void
    {
        $allowed = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $allowed[] = (string) $p['id'];
            }
        }
        $this->locationStopNotes = array_intersect_key($this->locationStopNotes, array_flip($allowed));
    }

    protected function initializeWaypoints(): void
    {
        $this->routeWaypoints = array_values(array_map(fn ($id) => 'acc:'.((int) $id), $this->accommodationIds));
    }

    // ─── Computed properties ───────────────────────────────────────────────────

    public function getIsPublicTransportProperty(): bool
    {
        return empty($this->vehicleId);
    }

    public function getBaseLocationDataProperty(): array
    {
        if (! $this->baseLocationId) {
            return [];
        }
        $loc = Location::find($this->baseLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address,
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getStartAirportDataProperty(): array
    {
        if (! $this->sharedStartAirportLocationId) {
            return [];
        }
        $loc = Location::find($this->sharedStartAirportLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address ?? '',
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getEndAirportDataProperty(): array
    {
        if (! $this->sharedEndAirportLocationId) {
            return [];
        }
        $loc = Location::find($this->sharedEndAirportLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address ?? '',
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getPickupLocationDataProperty(): array
    {
        if (! $this->transferPickupLocationId) {
            return [];
        }
        $loc = Location::find($this->transferPickupLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address ?? '',
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getAccommodationsDataProperty(): array
    {
        if (empty($this->accommodationIds)) {
            return [];
        }
        $rows = Accommodation::whereIn('id', $this->accommodationIds)->get();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = [
                'id' => $row->id,
                'name' => $row->name,
                'address' => $row->address,
                'city' => $row->city,
                'latitude' => $row->latitude ? (float) $row->latitude : null,
                'longitude' => $row->longitude ? (float) $row->longitude : null,
            ];
        }

        return $result;
    }

    protected function parseWaypointKey(string|int $key): array
    {
        if (is_int($key) || ctype_digit((string) $key)) {
            return ['type' => 'acc', 'id' => (int) $key];
        }

        $key = (string) $key;
        if ($key === 'base' || $key === 'sap') {
            return ['type' => 'pre_transfer_token', 'id' => 0];
        }
        if (str_starts_with($key, 'acc:')) {
            return ['type' => 'acc', 'id' => (int) substr($key, 4)];
        }
        if (str_starts_with($key, 'loc:')) {
            return ['type' => 'loc', 'id' => (int) substr($key, 4)];
        }

        return ['type' => 'acc', 'id' => (int) $key];
    }

    protected function getWaypointAccommodationIds(): array
    {
        return collect($this->routeWaypoints)
            ->map(fn ($k) => $this->parseWaypointKey($k))
            ->filter(fn ($p) => $p['type'] === 'acc' && $p['id'] > 0)
            ->map(fn ($p) => (int) $p['id'])
            ->values()
            ->all();
    }

    protected function getWaypointLocationIds(): array
    {
        return collect($this->routeWaypoints)
            ->map(fn ($k) => $this->parseWaypointKey($k))
            ->filter(fn ($p) => $p['type'] === 'loc' && $p['id'] > 0)
            ->map(fn ($p) => (int) $p['id'])
            ->values()
            ->all();
    }

    /**
     * Czy są przystanki typu loc (dodatkowe lokalizacje) na odcinku przed lotem.
     */
    public function getHasPreTransferLocationStopsProperty(): bool
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind === null) {
            return false;
        }
        foreach ($this->transferToAirportWaypoints as $k) {
            if (str_starts_with((string) $k, 'loc:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Jedna lista kafelków UI: baza, przystanki loc, lotnisko startowe — w kolejności z transferToAirportWaypoints.
     *
     * @return list<array{kind: string, key: string, index: int, can_remove: bool, can_move_up: bool, can_move_down: bool, location?: array<string, mixed>, display_name?: string}>
     */
    public function getPreTransferRouteTilesProperty(): array
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind === null) {
            return [];
        }
        if ($this->transferToAirportLegKind === 'own' && $this->transferToAirportGroundMode === 'other') {
            return [];
        }
        $keys = $this->transferToAirportWaypoints;
        $locIds = [];
        foreach ($keys as $key) {
            if ($key === 'base' || $key === 'sap') {
                continue;
            }
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc' && $p['id'] > 0) {
                $locIds[] = (int) $p['id'];
            }
        }
        $locations = $locIds !== [] ? Location::whereIn('id', array_unique($locIds))->get()->keyBy('id') : collect();
        $airportName = $this->startAirportData['name'] ?? 'Lotnisko startowe';
        $baseName = $this->baseLocationData['name'] ?? 'Baza';
        $n = count($keys);
        $tiles = [];
        foreach ($keys as $index => $key) {
            $key = (string) $key;
            $canMoveUp = $index > 0;
            $canMoveDown = $index < $n - 1;
            if ($key === 'base') {
                $tiles[] = [
                    'kind' => 'base',
                    'key' => 'base',
                    'index' => $index,
                    'display_name' => $baseName,
                    'can_remove' => true,
                    'can_move_up' => $canMoveUp,
                    'can_move_down' => $canMoveDown,
                ];

                continue;
            }
            if ($key === 'sap') {
                $tiles[] = [
                    'kind' => 'sap',
                    'key' => 'sap',
                    'index' => $index,
                    'display_name' => $airportName,
                    'can_remove' => false,
                    'can_move_up' => $canMoveUp,
                    'can_move_down' => $canMoveDown,
                ];

                continue;
            }
            $parsed = $this->parseWaypointKey($key);
            if ($parsed['type'] !== 'loc') {
                continue;
            }
            $loc = $locations->get((int) $parsed['id']);
            $tiles[] = [
                'kind' => 'loc',
                'key' => $key,
                'index' => $index,
                'can_remove' => true,
                'can_move_up' => $canMoveUp,
                'can_move_down' => $canMoveDown,
                'location' => $loc ? [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'address' => $loc->address,
                    'city' => $loc->city,
                    'latitude' => $loc->latitude ? (float) $loc->latitude : null,
                    'longitude' => $loc->longitude ? (float) $loc->longitude : null,
                ] : [
                    'id' => (int) $parsed['id'],
                    'name' => '—',
                    'address' => null,
                    'city' => null,
                    'latitude' => null,
                    'longitude' => null,
                ],
            ];
        }

        return $tiles;
    }

    public function getWaypointStopsProperty(): array
    {
        try {
            $accommodationsData = $this->accommodationsData;
            $locationIds = $this->getWaypointLocationIds();
            $locations = $locationIds ? Location::whereIn('id', $locationIds)->get()->keyBy('id') : collect();
            $result = [];
            foreach ((array) $this->routeWaypoints as $key) {
                $parsed = $this->parseWaypointKey($key);
                if ($parsed['type'] === 'acc') {
                    $accId = (int) $parsed['id'];
                    if (! isset($accommodationsData[$accId])) {
                        continue;
                    }
                    $employees = $this->getEmployeesForAccommodation($accId);
                    $result[] = [
                        'key' => 'acc:'.$accId,
                        'type' => 'acc',
                        'id' => $accId,
                        'label' => $accommodationsData[$accId]['name'],
                        'accommodation' => $accommodationsData[$accId],
                        'location' => null,
                        'employees' => $employees->map(fn ($e) => [
                            'id' => $e->id,
                            'full_name' => $e->full_name,
                        ])->values()->toArray(),
                    ];
                } elseif ($parsed['type'] === 'loc') {
                    $loc = $locations->get((int) $parsed['id']);
                    if (! $loc) {
                        continue;
                    }
                    $result[] = [
                        'key' => 'loc:'.$loc->id,
                        'type' => 'loc',
                        'id' => $loc->id,
                        'label' => $loc->name,
                        'accommodation' => null,
                        'location' => [
                            'id' => $loc->id,
                            'name' => $loc->name,
                            'address' => $loc->address,
                            'city' => $loc->city,
                            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
                            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
                        ],
                        'employees' => [],
                    ];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getWaypointStopsProperty failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    // Back-compat alias
    public function getWaypointAccommodationsProperty(): array
    {
        return $this->waypointStops;
    }

    public function addExtraStop(): void
    {
        if ($this->isPublicTransport) {
            return;
        }

        // Odczytaj ID po synchronizacji (np. po submit formularza — pewniejsze niż samo kliknięcie przy wire:model)
        $raw = $this->extraStopLocationId;
        $id = is_numeric($raw) ? (int) $raw : 0;
        if ($id <= 0) {
            return;
        }

        $key = 'loc:'.$id;
        if (in_array($key, $this->routeWaypoints, true)) {
            $this->extraStopLocationId = null;

            return;
        }

        $loc = Location::find($id);
        if ($loc && ! $loc->hasCoordinates()) {
            $this->geocodingService->geocodeLocation($loc);
        }

        $this->routeWaypoints[] = $key;
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->extraStopLocationId = null;
        $this->locationStopNotes[(string) $id] = $this->locationStopNotes[(string) $id] ?? '';
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    /** Dodatkowe lokalizacje na odcinku lotnisko docelowe → domy (transport publiczny + własny transfer). */
    public function addExtraLocationToPostTransfer(): void
    {
        if (! $this->isPublicTransport) {
            return;
        }

        $raw = $this->extraStopLocationId;
        $id = is_numeric($raw) ? (int) $raw : 0;
        if ($id <= 0) {
            return;
        }

        $key = 'loc:'.$id;
        if (in_array($key, $this->routeWaypoints, true)) {
            $this->extraStopLocationId = null;

            return;
        }

        $loc = Location::find($id);
        if ($loc && ! $loc->hasCoordinates()) {
            $this->geocodingService->geocodeLocation($loc);
        }

        $this->routeWaypoints[] = $key;
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->extraStopLocationId = null;
        $this->locationStopNotes[(string) $id] = $this->locationStopNotes[(string) $id] ?? '';
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function addExtraStopToPreTransfer(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind === null
            || $this->transferToAirportLegKind !== 'own' || $this->transferToAirportGroundMode !== 'car') {
            return;
        }

        $raw = $this->transferToAirportExtraStopLocationId;
        $id = is_numeric($raw) ? (int) $raw : 0;
        if ($id <= 0) {
            return;
        }

        $key = 'loc:'.$id;

        $loc = Location::find($id);
        if ($loc && ! $loc->hasCoordinates()) {
            $this->geocodingService->geocodeLocation($loc);
        }

        $this->transferToAirportWaypoints[] = $key;
        $this->transferToAirportWaypoints = array_values($this->transferToAirportWaypoints);
        $this->transferToAirportLocationStopNotes[(string) $id] = $this->transferToAirportLocationStopNotes[(string) $id] ?? '';
        $this->transferToAirportExtraStopLocationId = null;
        $this->invalidatePreRouteMetricsAndSyncToParent();
    }

    public function removePreWaypoint(int $index): void
    {
        if ($index < 0 || $index >= count($this->transferToAirportWaypoints)) {
            return;
        }
        if (($this->transferToAirportWaypoints[$index] ?? null) === 'sap') {
            return;
        }
        $waypoints = $this->transferToAirportWaypoints;
        array_splice($waypoints, $index, 1);
        $this->transferToAirportWaypoints = array_values($waypoints);
        $this->ensureSingleSapInPreTransferWaypoints();
        $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        $this->prunePreLocationStopNotes();
        $this->invalidatePreRouteMetricsAndSyncToParent();
    }

    public function movePreWaypointUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->transferToAirportWaypoints)) {
            return;
        }
        $w = $this->transferToAirportWaypoints;
        [$w[$index - 1], $w[$index]] = [$w[$index], $w[$index - 1]];
        $this->transferToAirportWaypoints = array_values($w);
        $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        $this->invalidatePreRouteMetricsAndSyncToParent();
    }

    public function movePreWaypointDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->transferToAirportWaypoints) - 1) {
            return;
        }
        $w = $this->transferToAirportWaypoints;
        [$w[$index], $w[$index + 1]] = [$w[$index + 1], $w[$index]];
        $this->transferToAirportWaypoints = array_values($w);
        $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        $this->invalidatePreRouteMetricsAndSyncToParent();
    }

    protected function prunePreLocationStopNotes(): void
    {
        $allowed = [];
        foreach ($this->transferToAirportWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $allowed[] = (string) $p['id'];
            }
        }
        $this->transferToAirportLocationStopNotes = array_intersect_key($this->transferToAirportLocationStopNotes, array_flip($allowed));
    }

    public function removeWaypoint(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        array_splice($waypoints, $index, 1);
        $this->routeWaypoints = array_values($waypoints);
        $this->pruneLocationStopNotes();
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function getAvailableVehiclesProperty()
    {
        $arrivalDate = $this->endDate ? \Carbon\Carbon::parse($this->endDate) : now();
        $locationTrackingService = app(LocationTrackingService::class);

        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $vehicle) use ($arrivalDate, $locationTrackingService) {
                $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $arrivalDate);

                // Transfer vehicle should be outside base on arrival date
                return ! $status['in_transit'] && $status['outside_base'];
            });
    }

    public function getAvailableEmployeesProperty()
    {
        $arrivalDate = $this->endDate ? \Carbon\Carbon::parse($this->endDate) : now();
        $locationTrackingService = app(LocationTrackingService::class);

        return Employee::orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(function (Employee $employee) use ($arrivalDate, $locationTrackingService) {
                $status = $locationTrackingService->getLocationStatus($employee, $arrivalDate);

                return $status['state'] === \App\Enums\EmployeeLocationState::OUTSIDE_BASE;
            });
    }

    public function getAvailableLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getCurrencyCasesProperty(): array
    {
        return Currency::cases();
    }

    public function getSelectedEmployeesForTicketsProperty(): Collection
    {
        if ($this->selectedEmployeeIds === []) {
            return collect();
        }

        return Employee::whereIn('id', $this->selectedEmployeeIds)->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * Tryb „Samochód / Inny transport” widoczny w UI — w otwartym modalu bierzemy draft,
     * żeby walidacja i podświetlenia zgadzały się z przełącznikami zanim użytkownik zatwierdzi.
     */
    protected function effectivePreTransferGroundModeForUi(): string
    {
        if ($this->showPreTransferConfigModal
            && $this->preTransferConfigModalGroundMode !== null
            && in_array($this->preTransferConfigModalGroundMode, ['car', 'other'], true)) {
            return $this->preTransferConfigModalGroundMode;
        }

        return $this->transferToAirportGroundMode;
    }

    /**
     * Tryb Samochód / Inny transport w modalu transferu z lotniska (draft).
     */
    protected function effectivePostTransferGroundModeForUi(): string
    {
        if ($this->showPostTransferConfigModal
            && $this->postTransferConfigModalGroundMode !== null
            && in_array($this->postTransferConfigModalGroundMode, ['car', 'other'], true)) {
            return $this->postTransferConfigModalGroundMode;
        }

        return $this->transferFromAirportGroundMode;
    }

    public function getToAirportGroundTicketsIncompleteProperty(): bool
    {
        if (! $this->isPublicTransport || $this->selectedEmployeeIds === []) {
            return false;
        }
        $g = $this->effectivePreTransferGroundModeForUi();
        $needTickets = $this->transferToAirportLegKind === 'public'
            || ($this->transferToAirportLegKind === 'own' && $g === 'other');
        if (! $needTickets) {
            return false;
        }

        return PublicTransportTicketCosts::areIncompleteForEmployees(
            $this->selectedEmployeeIds,
            $this->toAirportPublicTicketCostsByEmployee,
            true
        );
    }

    public function getFromAirportGroundTicketsIncompleteProperty(): bool
    {
        if (! $this->isPublicTransport || $this->selectedEmployeeIds === [] || ! $this->postAirportTransferUserEnabled) {
            return false;
        }
        $eff = $this->effectiveTransferFromAirportLegKind;
        if ($eff === null) {
            return false;
        }
        $g = $this->effectivePostTransferGroundModeForUi();
        $needTickets = $eff === 'public'
            || ($eff === 'own' && $g === 'other');
        if (! $needTickets) {
            return false;
        }

        return PublicTransportTicketCosts::areIncompleteForEmployees(
            $this->selectedEmployeeIds,
            $this->fromAirportPublicTicketCostsByEmployee,
            true
        );
    }

    public function getPreTransferVehicleIncompleteProperty(): bool
    {
        $g = $this->effectivePreTransferGroundModeForUi();
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own'
            || $g !== 'car') {
            return false;
        }

        return empty($this->preTransferVehicleId);
    }

    public function getPreTransferDriverIncompleteProperty(): bool
    {
        $g = $this->effectivePreTransferGroundModeForUi();
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own'
            || $g !== 'car') {
            return false;
        }

        return empty($this->preTransferDriverEmployeeId);
    }

    public function getPreTransferBonusIncompleteProperty(): bool
    {
        $g = $this->effectivePreTransferGroundModeForUi();
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own'
            || $g !== 'car' || empty($this->preTransferDriverEmployeeId)) {
            return false;
        }
        $bonus = $this->preTransferDriverBonusAmount;
        if ($bonus === null || $bonus === '' || ! is_numeric($bonus) || (float) $bonus <= 0) {
            return true;
        }
        $cur = strtoupper(trim((string) ($this->preTransferDriverBonusCurrency ?? 'PLN')));

        return strlen($cur) !== 3;
    }

    /**
     * Natychmiast utrwala wgrany plik biletu ziemnego na dysku i w zagnieżdżonej tablicy biletów zostawia
     * wyłącznie `attachment_path` (string). Dzięki temu event `route-planned` dispatchowany do rodzica
     * niesie w `route_segments` wartości bezpiecznie serializowalne do JSON — inaczej obiekty
     * `TemporaryUploadedFile` w params eventu ulegają korupcji (Synth pliku obsługuje tylko właściwości
     * komponentu, a nie parametry eventów), co objawiało się znikaniem biletów po przejściu na krok 4
     * i błędem „Załącznik musi być poprawnym plikiem” przy zapisie.
     */
    protected function mergeFlatTicketFileUploadsIntoNested(string $n): void
    {
        if (str_starts_with($n, 'fromAirportTicketFiles.')) {
            $eid = (int) substr($n, strlen('fromAirportTicketFiles.'));
            if ($eid < 1) {
                return;
            }
            $up = $this->fromAirportTicketFiles[$eid] ?? $this->fromAirportTicketFiles[(string) $eid] ?? null;
            if ($up === null || $up === '') {
                return;
            }
            $row = $this->fromAirportPublicTicketCostsByEmployee[$eid] ?? [];
            if (! is_array($row)) {
                $row = [];
            }
            $path = $this->persistTicketUploadImmediately($up);
            if ($path !== null) {
                $row['attachment_path'] = $path;
                unset($row['attachment']);
                unset($this->fromAirportTicketFiles[$eid], $this->fromAirportTicketFiles[(string) $eid]);
            } else {
                $row['attachment'] = $up;
            }
            $this->fromAirportPublicTicketCostsByEmployee[$eid] = $row;
        }
        if (str_starts_with($n, 'toAirportTicketFiles.')) {
            $eid = (int) substr($n, strlen('toAirportTicketFiles.'));
            if ($eid < 1) {
                return;
            }
            $up = $this->toAirportTicketFiles[$eid] ?? $this->toAirportTicketFiles[(string) $eid] ?? null;
            if ($up === null || $up === '') {
                return;
            }
            $row = $this->toAirportPublicTicketCostsByEmployee[$eid] ?? [];
            if (! is_array($row)) {
                $row = [];
            }
            $path = $this->persistTicketUploadImmediately($up);
            if ($path !== null) {
                $row['attachment_path'] = $path;
                unset($row['attachment']);
                unset($this->toAirportTicketFiles[$eid], $this->toAirportTicketFiles[(string) $eid]);
            } else {
                $row['attachment'] = $up;
            }
            $this->toAirportPublicTicketCostsByEmployee[$eid] = $row;
        }
    }

    /**
     * Zapisuje TemporaryUploadedFile na dysku publicznym i zwraca ścieżkę, lub null przy nieudanym zapisie.
     */
    protected function persistTicketUploadImmediately(mixed $up): ?string
    {
        try {
            if ($up instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $path = $up->store('transport_costs', 'public');

                return is_string($path) && $path !== '' ? $path : null;
            }
        } catch (\Throwable $e) {
            Log::warning('Step4RoutePlanning: nie udało się zapisać załącznika biletu ziemnego', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function updated($name): void
    {
        $n = (string) $name;
        $this->mergeFlatTicketFileUploadsIntoNested($n);

        if (! $this->isPublicTransport) {
            return;
        }
        $syncSegments = str_starts_with($n, 'toAirportPublicTicketCostsByEmployee')
            || str_starts_with($n, 'fromAirportPublicTicketCostsByEmployee')
            || str_starts_with($n, 'fromAirportTicketFiles')
            || str_starts_with($n, 'toAirportTicketFiles')
            || str_starts_with($n, 'preTransfer')
            || $n === 'transferPickupLocationId';
        if ($syncSegments) {
            // W otwartym modalu „Konfiguruj transfer” nie przebudowuj segmentów przy każdym polu —
            // synchronizacja na Zatwierdź / Anuluj (mniej obciążenia i stabilne okno).
            if ($this->showPreTransferConfigModal
                && $this->transferToAirportLegKind === 'own'
                && (
                    str_starts_with($n, 'preTransfer')
                    || str_starts_with($n, 'toAirportPublicTicketCostsByEmployee')
                )) {
                return;
            }
            if ($this->showPostTransferConfigModal
                && $this->postAirportTransferUserEnabled
                && $this->effectiveTransferFromAirportLegKind === 'own'
                && (
                    str_starts_with($n, 'transferVehicle')
                    || str_starts_with($n, 'transferDriver')
                    || str_starts_with($n, 'fromAirportPublicTicketCostsByEmployee')
                )) {
                return;
            }
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();
        }
    }

    /** Czy brak zapisanego dystansu/czasu trasy (tak samo jak w DeparturePlannerV2::getStep4TabIncompleteProperty). */
    protected function routeMetricsComplete(): bool
    {
        $rd = $this->routeData;
        if (! is_array($rd)) {
            return false;
        }
        $dist = data_get($rd, 'route_distance', data_get($rd, 'distance'));
        $dur = data_get($rd, 'route_duration', data_get($rd, 'duration'));

        return $dist !== null && $dist !== '' && is_numeric($dist) && (float) $dist > 0
            && $dur !== null && $dur !== '' && (int) $dur > 0;
    }

    protected function preRouteMetricsComplete(): bool
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind === null
            || $this->transferToAirportLegKind === 'public') {
            return true;
        }
        if ($this->transferToAirportLegKind !== 'own') {
            return true;
        }
        $rd = $this->preRouteData;
        if (! is_array($rd)) {
            return false;
        }

        return isset($rd['distance'], $rd['duration'])
            && is_numeric($rd['distance']) && (float) $rd['distance'] > 0
            && is_numeric($rd['duration']) && (int) $rd['duration'] > 0;
    }

    public function getEffectiveTransferFromAirportLegKindProperty(): ?string
    {
        if (! $this->postAirportTransferUserEnabled) {
            return null;
        }

        return $this->transferFromAirportLegKind
            ?? (count($this->routeWaypoints) > 0 ? 'own' : 'public');
    }

    public function getRoutePreBlockIncompleteProperty(): bool
    {
        return $this->isPublicTransport && $this->transferToAirportLegKind === 'own'
            && ! $this->preRouteMetricsComplete();
    }

    /**
     * Karta 1 (transfer przed lotem): wymaga uwagi — brak zatwierdzenia lub brak wymaganych pól / km.
     */
    public function getPreTransferCardNeedsAttentionProperty(): bool
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own') {
            return false;
        }
        $compact = ($this->preTransferCarSectionCollapsed && $this->transferToAirportGroundMode === 'car')
            || ($this->preTransferOtherSectionCollapsed && $this->transferToAirportGroundMode === 'other');
        if (! $compact) {
            return true;
        }
        if ($this->transferToAirportGroundMode === 'car') {
            return $this->preTransferVehicleIncomplete || $this->preTransferDriverIncomplete || $this->preTransferBonusIncomplete
                || $this->routePreBlockIncomplete;
        }
        $ps = trim((string) ($this->preTransferPublicStationStart ?? ''));
        $pe = trim((string) ($this->preTransferPublicStationEnd ?? ''));

        return $this->toAirportGroundTicketsIncomplete || $this->routePreBlockIncomplete || $ps === '' || $pe === '';
    }

    /**
     * Karta 3 (transfer z lotniska, własny środek): uwaga — brak zatwierdzenia lub braki w polach / km.
     */
    public function getPostTransferCardNeedsAttentionProperty(): bool
    {
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own') {
            return false;
        }
        $compact = ($this->postTransferCarSectionCollapsed && $this->transferFromAirportGroundMode === 'car')
            || ($this->postTransferOtherSectionCollapsed && $this->transferFromAirportGroundMode === 'other');
        if (! $compact) {
            return true;
        }
        if ($this->transferFromAirportGroundMode === 'car') {
            return $this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete
                || ! $this->routeMetricsComplete();
        }

        return $this->fromAirportGroundTicketsIncomplete || ! $this->routeMetricsComplete();
    }

    public function getRouteBlockIncompleteProperty(): bool
    {
        if ($this->isPublicTransport && ! $this->postAirportTransferUserEnabled) {
            return (bool) $this->routePreBlockIncomplete;
        }

        if (! $this->routeMetricsComplete()) {
            return true;
        }

        return $this->routePreBlockIncomplete;
    }

    public function getPickupIncompleteProperty(): bool
    {
        return false;
    }

    public function getTransferVehicleIncompleteProperty(): bool
    {
        $g = $this->effectivePostTransferGroundModeForUi();
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own'
            || $g !== 'car') {
            return false;
        }

        return empty($this->transferVehicleId);
    }

    public function getTransferDriverIncompleteProperty(): bool
    {
        $g = $this->effectivePostTransferGroundModeForUi();
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own'
            || $g !== 'car') {
            return false;
        }

        return empty($this->transferDriverEmployeeId);
    }

    public function getTransferBonusIncompleteProperty(): bool
    {
        $g = $this->effectivePostTransferGroundModeForUi();
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own'
            || $g !== 'car' || empty($this->transferDriverEmployeeId)) {
            return false;
        }
        $bonus = $this->transferDriverBonusAmount;
        if ($bonus === null || $bonus === '' || ! is_numeric($bonus) || (float) $bonus <= 0) {
            return true;
        }
        $cur = strtoupper(trim((string) ($this->transferDriverBonusCurrency ?? 'PLN')));

        return strlen($cur) !== 3;
    }

    // ─── Route planning ────────────────────────────────────────────────────────

    public function moveUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        [$waypoints[$index - 1], $waypoints[$index]] = [$waypoints[$index], $waypoints[$index - 1]];
        $this->routeWaypoints = array_values($waypoints);
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function moveDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints) - 1) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        [$waypoints[$index], $waypoints[$index + 1]] = [$waypoints[$index + 1], $waypoints[$index]];
        $this->routeWaypoints = array_values($waypoints);
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function updatedTransferPickupLocationId(): void
    {
        // Geocode new pickup location if needed
        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if ($pickup && ! $pickup->hasCoordinates()) {
                $this->geocodingService->geocodeLocation($pickup);
            }
        }
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function updatedTransferVehicleId(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedTransferDriverEmployeeId(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedTransferDriverBonusAmount(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedTransferDriverBonusCurrency(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedManualRouteDistanceKm(): void
    {
        // Do not auto-apply; user must confirm with applyManualRouteDistance()
    }

    public function applyManualRouteDistance(?string $leg = null): void
    {
        $leg = $leg ?? 'post';

        if ($leg === 'pre') {
            $km = $this->preManualRouteDistanceKm;
            if ($km === null || $km === '' || ! is_numeric($km) || (float) $km <= 0) {
                $this->preRouteError = 'Podaj poprawną liczbę kilometrów dla odcinka na lotnisko.';

                return;
            }
            $minutes = $this->preManualRouteDurationMinutes;
            if ($minutes === null || $minutes === '' || ! is_numeric($minutes) || (float) $minutes <= 0) {
                $this->preRouteError = 'Podaj czas przejazdu (min) dla odcinka na lotnisko.';

                return;
            }
            $durationSeconds = (int) round((float) $minutes * 60);
            $this->isManualPreRouteDistance = true;
            $this->manualRouteHint = null;
            $this->preRouteData = [
                'distance' => (float) $km,
                'duration' => $durationSeconds,
            ];
            $this->preRouteError = null;
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();

            return;
        }

        $km = $this->manualRouteDistanceKm;
        if ($km === null || $km === '' || ! is_numeric($km) || (float) $km <= 0) {
            $this->routeError = 'Podaj poprawną liczbę kilometrów (większą od 0), aby ustawić dystans ręcznie.';

            return;
        }

        $minutes = $this->manualRouteDurationMinutes;
        if ($minutes === null || $minutes === '' || ! is_numeric($minutes) || (float) $minutes <= 0) {
            $this->routeError = 'Podaj szacowany czas przejazdu w minutach (większy od 0).';

            return;
        }

        // ORS zwraca duration w sekundach — utrzymujemy ten sam format w DB i transfer_config
        $durationSeconds = (int) round((float) $minutes * 60);

        $this->isManualRouteDistance = true;
        $this->manualRouteHint = null;
        $this->routeData = [
            'distance' => (float) $km,
            'duration' => $durationSeconds,
        ];
        // Keep routeError empty so UI does not look broken after manual fallback
        $this->routeError = null;

        // Dispatch to parent so it can be saved
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    protected function dispatchTransferConfig(): void
    {
        $this->dispatch('transfer-config-updated', [
            'vehicle_id' => $this->transferVehicleId,
            'driver_employee_id' => $this->transferDriverEmployeeId,
            'bonus_amount' => $this->transferDriverBonusAmount,
            'bonus_currency' => $this->transferDriverBonusCurrency,
            'pickup_location_id' => null,
            'route_distance' => $this->routeData['distance'] ?? null,
            'route_duration' => $this->routeData['duration'] ?? null,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->getLocationStopNotesPayload(),
            'end_airport_location_id' => $this->sharedEndAirportLocationId,
            'route_distance_is_manual' => (bool) $this->isManualRouteDistance,
        ]);
    }

    public function planRoute(): void
    {
        if (empty($this->routeWaypoints)) {
            $this->routeData = null;
            $this->routeError = 'Brak przystanków do zaplanowania trasy.';

            return;
        }

        $accIds = $this->getWaypointAccommodationIds();
        $locIds = $this->getWaypointLocationIds();
        $accommodations = Accommodation::whereIn('id', $accIds)->get()->keyBy('id');
        $locations = Location::whereIn('id', $locIds)->get()->keyBy('id');

        $missingCoords = [];
        foreach ($this->routeWaypoints as $key) {
            $parsed = $this->parseWaypointKey($key);
            if ($parsed['type'] === 'acc') {
                $acc = $accommodations->get((int) $parsed['id']);
                if (! $acc || ! $acc->hasCoordinates()) {
                    $missingCoords[] = $acc ? $acc->name : "Dom ID:{$parsed['id']}";
                }
            } elseif ($parsed['type'] === 'loc') {
                $loc = $locations->get((int) $parsed['id']);
                if (! $loc || ! $loc->hasCoordinates()) {
                    $missingCoords[] = $loc ? $loc->name : "Lokacja ID:{$parsed['id']}";
                }
            }
        }
        if (! empty($missingCoords)) {
            $this->routeError = 'Brak współrzędnych dla: '.implode(', ', $missingCoords).'. Edytuj akomodację i użyj wyszukiwania miejsca.';

            return;
        }

        if ($this->isPublicTransport) {
            $this->planTransferRoute($accommodations, $locations);
        } else {
            $this->planCarRoute($accommodations, $locations);
        }
    }

    protected function resolveWaypointObjects($accommodations, $locations): array
    {
        $list = [];
        foreach ($this->routeWaypoints as $key) {
            $parsed = $this->parseWaypointKey($key);
            if ($parsed['type'] === 'acc') {
                $obj = $accommodations->get((int) $parsed['id']);
                if ($obj) {
                    $list[] = $obj;
                }
            } elseif ($parsed['type'] === 'loc') {
                $obj = $locations->get((int) $parsed['id']);
                if ($obj) {
                    $list[] = $obj;
                }
            }
        }

        return $list;
    }

    protected function planCarRoute($accommodations, $locations): void
    {
        if (! $this->baseLocationId) {
            $this->routeError = 'Brak lokalizacji bazy.';

            return;
        }
        $base = Location::find($this->baseLocationId);
        if (! $base || ! $base->hasCoordinates()) {
            $this->routeError = 'Brak współrzędnych dla lokalizacji bazy.';

            return;
        }

        $this->isPlanningRoute = true;
        $this->routeError = null;
        $previousRouteData = $this->routeData;

        try {
            $waypointList = $this->resolveWaypointObjects($accommodations, $locations);

            $lastWaypoint = array_pop($waypointList);
            $intermediateWaypoints = $waypointList;

            $route = $this->routePlanningService->planRouteWithWaypoints($base, $lastWaypoint, $intermediateWaypoints, []);

            if ($route) {
                $this->routeData = ['distance' => $route['distance'], 'duration' => $route['duration']];
                $this->isManualRouteDistance = false;
                $this->syncManualFieldsFromRouteData();
                $this->routeError = null;
                $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
                $this->dispatchTransferConfig();
                $this->dispatch('route-updated',
                    baseLocationData: $this->baseLocationData,
                    waypointAccommodations: $this->waypointStops,
                    routeData: $this->routeData,
                );
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy.';
                $this->routeData = $previousRouteData;
            }
        } catch (\Exception $e) {
            Log::error('Route planning exception (car)', ['message' => $e->getMessage()]);
            $this->routeError = 'Błąd podczas planowania trasy: '.$e->getMessage();
            $this->routeData = $previousRouteData;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    public function getTransferGoogleMapsUrlProperty(): ?string
    {
        if (! $this->isPublicTransport) {
            return null;
        }

        $coordsList = [];

        // Start: pickup if set, otherwise end airport
        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if ($pickup && $pickup->hasCoordinates()) {
                $coordsList[] = $pickup->getCoordinates(); // [lat, lng]
            }
        }

        $endAirport = $this->sharedEndAirportLocationId ? Location::find($this->sharedEndAirportLocationId) : null;
        if ($endAirport && $endAirport->hasCoordinates()) {
            $coordsList[] = $endAirport->getCoordinates();
        }

        // Homes (in current order)
        $accommodations = Accommodation::whereIn('id', $this->routeWaypoints)->get()->keyBy('id');
        foreach ($this->routeWaypoints as $accId) {
            $acc = $accommodations->get($accId);
            if ($acc && $acc->hasCoordinates()) {
                $coordsList[] = $acc->getCoordinates();
            }
        }

        if (count($coordsList) < 2) {
            return null;
        }

        $origin = $coordsList[0];
        $destination = $coordsList[count($coordsList) - 1];
        $waypoints = array_slice($coordsList, 1, -1);

        $originStr = $origin[0].','.$origin[1];
        $destinationStr = $destination[0].','.$destination[1];
        $waypointsStr = implode('|', array_map(fn ($c) => ($c[0].','.$c[1]), $waypoints));

        $params = [
            'api' => '1',
            'travelmode' => 'driving',
            'origin' => $originStr,
            'destination' => $destinationStr,
        ];
        if (! empty($waypointsStr)) {
            $params['waypoints'] = $waypointsStr;
        }

        return 'https://www.google.com/maps/dir/?'.http_build_query($params);
    }

    protected function getPreWaypointLocationIds(): array
    {
        $ids = [];
        foreach ($this->transferToAirportWaypoints as $key) {
            $key = (string) $key;
            if ($key === 'base' || $key === 'sap') {
                continue;
            }
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc' && $p['id'] > 0) {
                $ids[] = (int) $p['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolvePreWaypointObjects($locations): array
    {
        $list = [];
        foreach ($this->transferToAirportWaypoints as $key) {
            $key = (string) $key;
            if ($key === 'base' || $key === 'sap') {
                continue;
            }
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $obj = $locations->get((int) $p['id']);
                if ($obj) {
                    $list[] = $obj;
                }
            }
        }

        return $list;
    }

    /**
     * Kolejność lokalizacji do API (ORS): dokładnie jak tokeny w transferToAirportWaypoints (baza / loc / lotnisko startowe).
     *
     * @return list<Location>
     */
    protected function resolvePreTransferOrderedLocationModels(): array
    {
        $startAirport = $this->sharedStartAirportLocationId ? Location::find($this->sharedStartAirportLocationId) : null;
        if (! $startAirport || ! $startAirport->hasCoordinates()) {
            return [];
        }

        $base = $this->baseLocationId ? Location::find($this->baseLocationId) : null;
        $locIds = $this->getPreWaypointLocationIds();
        $byId = $locIds !== [] ? Location::whereIn('id', $locIds)->get()->keyBy('id') : collect();

        $ordered = [];
        foreach ($this->transferToAirportWaypoints as $key) {
            $key = (string) $key;
            if ($key === 'base') {
                if ($base && $base->hasCoordinates()) {
                    $ordered[] = $base;
                }

                continue;
            }
            if ($key === 'sap') {
                $ordered[] = $startAirport;

                continue;
            }
            $p = $this->parseWaypointKey($key);
            if ($p['type'] !== 'loc') {
                continue;
            }
            $loc = $byId->get((int) $p['id']);
            if ($loc && $loc->hasCoordinates()) {
                $ordered[] = $loc;
            }
        }

        return $ordered;
    }

    public function omitPreTransferBase(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own') {
            return;
        }
        $this->transferToAirportWaypoints = array_values(array_filter(
            $this->transferToAirportWaypoints,
            static fn ($k) => (string) $k !== 'base'
        ));
        $this->ensureSingleSapInPreTransferWaypoints();
        $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        $this->invalidatePreRouteMetricsAndSyncToParent();
    }

    public function restorePreTransferBase(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own') {
            return;
        }
        if (! in_array('base', $this->transferToAirportWaypoints, true)) {
            array_unshift($this->transferToAirportWaypoints, 'base');
            $this->transferToAirportWaypoints = array_values($this->transferToAirportWaypoints);
        }
        $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        $this->invalidatePreRouteMetricsAndSyncToParent();
    }

    public function savePreTransferRouteModal(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own') {
            return;
        }
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function planPreAirportRoute(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind === null) {
            return;
        }

        if ($this->transferToAirportLegKind === 'public' || $this->transferToAirportGroundMode !== 'car') {
            $this->preRouteError = 'Automatyczne liczenie działa w trybie „Samochód”. Wpisz km ręcznie albo przełącz tryb.';

            return;
        }

        if (in_array('base', $this->transferToAirportWaypoints, true)) {
            if (! $this->baseLocationId) {
                $this->preRouteError = 'Brak lokalizacji bazy.';

                return;
            }
            $base = Location::find($this->baseLocationId);
            if (! $base || ! $base->hasCoordinates()) {
                $this->preRouteError = 'Brak współrzędnych dla lokalizacji bazy.';

                return;
            }
        }

        $startAirport = $this->sharedStartAirportLocationId ? Location::find($this->sharedStartAirportLocationId) : null;
        if (! $startAirport || ! $startAirport->hasCoordinates()) {
            $this->preRouteError = 'Wybierz lotnisko startowe w nagłówku i uzupełnij współrzędne.';

            return;
        }

        $locIds = $this->getPreWaypointLocationIds();
        $locations = $locIds !== [] ? Location::whereIn('id', $locIds)->get()->keyBy('id') : collect();

        $missingCoords = [];
        foreach ($this->transferToAirportWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $loc = $locations->get((int) $p['id']);
                if (! $loc || ! $loc->hasCoordinates()) {
                    $missingCoords[] = $loc ? $loc->name : 'Lokacja';
                }
            }
        }
        if (! empty($missingCoords)) {
            $this->preRouteError = 'Brak współrzędnych dla: '.implode(', ', $missingCoords).'.';

            return;
        }

        $orderedChain = $this->resolvePreTransferOrderedLocationModels();
        if (count($orderedChain) < 2) {
            $this->preRouteError = 'Potrzebne są co najmniej dwa punkty z współrzędnymi w ustalonej kolejności (np. przystanek i lotnisko startowe).';

            return;
        }

        $this->isPlanningRoute = true;
        $this->preRouteError = null;
        $previousPre = $this->preRouteData;

        try {
            $route = $this->routePlanningService->planRouteAlongOrderedLocations($orderedChain, []);
            if ($route) {
                $this->isManualPreRouteDistance = false;
                $this->preRouteData = ['distance' => $route['distance'], 'duration' => $route['duration']];
                $this->syncPreManualFieldsFromPreRouteData();
                $this->preRouteError = null;
                $this->rebuildRouteSegmentsFromUiState();
                $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
                $this->dispatchTransferConfig();
            } else {
                $this->preRouteError = 'Nie udało się zaplanować trasy na lotnisko startowe.';
                $this->preRouteData = $previousPre;
            }
        } catch (\Exception $e) {
            Log::error('Route planning exception (pre-airport)', ['message' => $e->getMessage()]);
            $this->preRouteError = 'Błąd planowania trasy na lotnisko: '.$e->getMessage();
            $this->preRouteData = $previousPre;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    public function confirmPreTransferCarSection(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own' || $this->transferToAirportGroundMode !== 'car') {
            return;
        }
        if ($this->preTransferVehicleIncomplete || $this->preTransferDriverIncomplete || $this->preTransferBonusIncomplete) {
            return;
        }
        $this->preTransferCarSectionCollapsed = true;
        $this->showPreTransferConfigModal = false;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function confirmPreTransferOtherSection(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own' || $this->transferToAirportGroundMode !== 'other') {
            return;
        }
        if ($this->toAirportGroundTicketsIncomplete) {
            return;
        }
        $this->preTransferOtherSectionCollapsed = true;
        $this->showPreTransferConfigModal = false;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function confirmPreTransferModal(): void
    {
        if ($this->transferToAirportLegKind === null) {
            return;
        }
        if ($this->transferToAirportLegKind === 'public') {
            if ($this->selectedEmployeesForTickets->isNotEmpty() && $this->toAirportGroundTicketsIncomplete) {
                return;
            }
            $this->preTransferConfigModalGroundMode = null;
            $this->showPreTransferConfigModal = false;
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();

            return;
        }

        $draft = $this->preTransferConfigModalGroundMode;
        if ($draft !== null && in_array($draft, ['car', 'other'], true) && $draft !== $this->transferToAirportGroundMode) {
            $this->transferToAirportGroundMode = $draft;
            $this->applyTransferToAirportGroundModeChanged();
        }
        $this->preTransferConfigModalGroundMode = null;

        if ($this->transferToAirportGroundMode === 'other') {
            $this->confirmPreTransferOtherSection();
        } else {
            $this->confirmPreTransferCarSection();
        }
    }

    public function openPreTransferConfigModal(): void
    {
        if (! $this->isPublicTransport) {
            return;
        }
        if (! in_array($this->transferToAirportLegKind, ['own', 'public'], true)) {
            return;
        }
        $this->showPreTransferGroundModeSwitchModal = false;
        $this->pendingPreTransferModalGroundMode = null;
        $this->pendingPreTransferModalSegment = null;
        $this->preTransferConfigModalGroundMode = $this->transferToAirportLegKind === 'own'
            ? $this->transferToAirportGroundMode
            : null;
        $this->showPreTransferConfigModal = true;
    }

    /** Aktualny wybór w modalu: public | car | other | null (jeszcze nie wybrano). */
    protected function getCurrentPreTransferModalSegment(): ?string
    {
        if ($this->transferToAirportLegKind === null) {
            return null;
        }
        if ($this->transferToAirportLegKind === 'public') {
            return 'public';
        }

        return $this->transferToAirportGroundMode === 'car' ? 'car' : 'other';
    }

    protected function preTransferSegmentSwitchWouldLoseData(?string $from, string $to): bool
    {
        if ($from === null || $from === $to) {
            return false;
        }
        if ($from === 'public' && $to === 'car') {
            return $this->preTransferModalHasOtherData();
        }
        if ($from === 'car' && $to === 'public') {
            return $this->preTransferModalHasCarData();
        }
        if ($from === 'public' && $to === 'other') {
            return false;
        }
        if ($from === 'other' && $to === 'public') {
            return trim((string) ($this->preTransferPublicStationStart ?? '')) !== ''
                || trim((string) ($this->preTransferPublicStationEnd ?? '')) !== '';
        }
        if (($from === 'car' && $to === 'other') || ($from === 'other' && $to === 'car')) {
            return $this->preTransferModalSwitchWouldLoseData($from, $to);
        }

        return false;
    }

    public function selectPreTransferModalSegment(string $target): void
    {
        if (! $this->isPublicTransport || ! $this->showPreTransferConfigModal) {
            return;
        }
        if (! in_array($target, ['public', 'car', 'other'], true)) {
            return;
        }
        $from = $this->getCurrentPreTransferModalSegment();
        if ($from === $target) {
            return;
        }
        if ($this->preTransferSegmentSwitchWouldLoseData($from, $target)) {
            $this->pendingPreTransferModalSegment = $target;
            $this->pendingPreTransferModalGroundMode = null;
            $this->showPreTransferGroundModeSwitchModal = true;

            return;
        }
        $this->applyPreTransferModalSegment($target);
    }

    protected function applyPreTransferModalSegment(string $target): void
    {
        if (! in_array($target, ['public', 'car', 'other'], true)) {
            return;
        }
        $this->pendingPreTransferModalSegment = null;
        $this->showPreTransferGroundModeSwitchModal = false;

        if ($target === 'public') {
            $this->transferToAirportLegKind = 'public';
            $this->transferToAirportGroundMode = 'other';
            $this->preTransferVehicleId = null;
            $this->preTransferDriverEmployeeId = null;
            $this->preTransferDriverBonusAmount = null;
            $this->preTransferCarSectionCollapsed = false;
            $this->preTransferOtherSectionCollapsed = false;
            $this->transferToAirportWaypoints = [];
            $this->transferToAirportLocationStopNotes = [];
            $this->transferToAirportStartsFromBase = false;
            $this->preRouteData = null;
            $this->isManualPreRouteDistance = false;
            $this->preRouteError = null;
            $this->preTransferPublicStationStart = null;
            $this->preTransferPublicStationEnd = null;
            $this->preTransferConfigModalGroundMode = null;
            $this->invalidatePreRouteMetricsAndSyncToParent();
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();

            return;
        }

        $this->transferToAirportLegKind = 'own';
        $mode = $target === 'car' ? 'car' : 'other';
        $this->transferToAirportGroundMode = $mode;
        $this->preTransferConfigModalGroundMode = $mode;
        $this->applyPreTransferModalDraftGroundMode($mode);
        $this->preRouteData = null;
        $this->isManualPreRouteDistance = false;
        $this->preRouteError = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    /**
     * Zmiana roboczego trybu w modalu — przy utracie dany drugiego typu: potwierdzenie (jak przełącznik transportu w planie wyjazdu).
     *
     * @deprecated Używaj {@see selectPreTransferModalSegment}; zostawione dla kompatybilności.
     */
    public function requestPreTransferModalGroundMode(string $mode): void
    {
        if (! in_array($mode, ['car', 'other'], true)) {
            return;
        }
        $this->selectPreTransferModalSegment($mode);
    }

    public function confirmPreTransferGroundModeSwitch(): void
    {
        if ($this->pendingPreTransferModalSegment !== null
            && in_array($this->pendingPreTransferModalSegment, ['public', 'car', 'other'], true)) {
            $seg = $this->pendingPreTransferModalSegment;
            $this->pendingPreTransferModalSegment = null;
            $this->pendingPreTransferModalGroundMode = null;
            $this->showPreTransferGroundModeSwitchModal = false;
            $this->applyPreTransferModalSegment($seg);

            return;
        }
        if ($this->pendingPreTransferModalGroundMode === null
            || ! in_array($this->pendingPreTransferModalGroundMode, ['car', 'other'], true)) {
            $this->showPreTransferGroundModeSwitchModal = false;
            $this->pendingPreTransferModalGroundMode = null;

            return;
        }
        $mode = $this->pendingPreTransferModalGroundMode;
        $this->pendingPreTransferModalGroundMode = null;
        $this->showPreTransferGroundModeSwitchModal = false;
        $this->applyPreTransferModalDraftGroundMode($mode);
    }

    public function cancelPreTransferGroundModeSwitch(): void
    {
        $this->pendingPreTransferModalGroundMode = null;
        $this->pendingPreTransferModalSegment = null;
        $this->showPreTransferGroundModeSwitchModal = false;
    }

    protected function preTransferModalSwitchWouldLoseData(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }
        if ($from === 'car') {
            return $this->preTransferModalHasCarData();
        }

        return $this->preTransferModalHasOtherData();
    }

    protected function preTransferModalHasCarData(): bool
    {
        if (! empty($this->preTransferVehicleId) || ! empty($this->preTransferDriverEmployeeId)) {
            return true;
        }
        $bonus = $this->preTransferDriverBonusAmount;

        if ($bonus !== null && $bonus !== '' && is_numeric($bonus) && (float) $bonus > 0) {
            return true;
        }

        foreach ($this->transferToAirportWaypoints as $k) {
            if (str_starts_with((string) $k, 'loc:')) {
                return true;
            }
        }
        $wps = array_values($this->transferToAirportWaypoints);

        return $wps !== ['base', 'sap'] && $wps !== [];
    }

    protected function preTransferModalHasOtherData(): bool
    {
        if (trim((string) ($this->preTransferPublicStationStart ?? '')) !== ''
            || trim((string) ($this->preTransferPublicStationEnd ?? '')) !== '') {
            return true;
        }
        foreach ($this->toAirportPublicTicketCostsByEmployee as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! empty($row['attachment'] ?? null) || ! empty($row['attachment_path'] ?? null)) {
                return true;
            }
            $amount = $row['amount'] ?? null;
            if ($amount !== null && $amount !== '' && is_numeric($amount) && (float) $amount > 0) {
                return true;
            }
            if (trim((string) ($row['notes'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Tylko stan pól w tym modalu — bez zmiany zapisanego {@see $transferToAirportGroundMode} ani segmentów.
     */
    protected function applyPreTransferModalDraftGroundMode(string $mode): void
    {
        if (! in_array($mode, ['car', 'other'], true)) {
            return;
        }
        $this->preTransferConfigModalGroundMode = $mode;
        if ($mode === 'car') {
            $this->toAirportPublicTicketCostsByEmployee = [];
            $this->toAirportTicketFiles = [];
            $this->preTransferPublicStationStart = null;
            $this->preTransferPublicStationEnd = null;
            $this->transferToAirportWaypoints = ['base', 'sap'];
            $this->ensureSingleSapInPreTransferWaypoints();
            $this->syncTransferToAirportStartsFromBaseFromWaypoints();
        } else {
            $this->preTransferVehicleId = null;
            $this->preTransferDriverEmployeeId = null;
            $this->preTransferDriverBonusAmount = null;
            $this->preTransferDriverBonusCurrency = 'PLN';
            $this->transferToAirportWaypoints = [];
            $this->transferToAirportLocationStopNotes = [];
            $this->transferToAirportStartsFromBase = false;
        }
    }

    public function closePreTransferConfigModal(): void
    {
        if (! $this->showPreTransferConfigModal) {
            return;
        }
        $this->showPreTransferConfigModal = false;
        $this->showPreTransferGroundModeSwitchModal = false;
        $this->pendingPreTransferModalGroundMode = null;
        $this->pendingPreTransferModalSegment = null;
        $this->preTransferConfigModalGroundMode = null;
        if ($this->transferToAirportLegKind === null) {
            return;
        }
        if (in_array($this->transferToAirportLegKind, ['own', 'public'], true)) {
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();
        }
    }

    public function openPreTransferRouteModal(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own') {
            return;
        }
        $preOwnCompact = ($this->preTransferCarSectionCollapsed && $this->transferToAirportGroundMode === 'car')
            || ($this->preTransferOtherSectionCollapsed && $this->transferToAirportGroundMode === 'other');
        if (! $preOwnCompact) {
            return;
        }
        $this->showPreTransferRouteModal = true;
    }

    public function closePreTransferRouteModal(): void
    {
        $this->showPreTransferRouteModal = false;
    }

    public function confirmPreTransferRouteModal(): void
    {
        $this->savePreTransferRouteModal();
        $this->closePreTransferRouteModal();
    }

    public function openPostTransferRouteModal(): void
    {
        if (! $this->isPublicTransport || ! $this->postAirportTransferUserEnabled) {
            return;
        }
        if ($this->effectiveTransferFromAirportLegKind === null) {
            return;
        }
        $this->showPostTransferConfigModal = false;
        $this->showPostTransferGroundModeSwitchModal = false;
        $this->pendingPostTransferModalGroundMode = null;
        $this->showPostTransferRouteModal = true;
    }

    public function closePostTransferRouteModal(): void
    {
        $this->showPostTransferRouteModal = false;
    }

    public function savePostTransferRouteModal(): void
    {
        if (! $this->isPublicTransport || ! $this->postAirportTransferUserEnabled) {
            return;
        }
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function confirmPostTransferRouteModal(): void
    {
        $this->savePostTransferRouteModal();
        $this->closePostTransferRouteModal();
    }

    public function expandPreTransferOwnCollapsedSection(): void
    {
        $this->openPreTransferConfigModal();
    }

    protected function applyTransferToAirportGroundModeChanged(): void
    {
        $this->preTransferCarSectionCollapsed = false;
        $this->preTransferOtherSectionCollapsed = false;
        if ($this->transferToAirportGroundMode === 'other') {
            $this->preTransferVehicleId = null;
            $this->preTransferDriverEmployeeId = null;
            $this->preTransferDriverBonusAmount = null;
            $this->transferToAirportWaypoints = [];
            $this->transferToAirportLocationStopNotes = [];
            $this->transferToAirportStartsFromBase = false;
            $this->invalidatePreRouteMetricsAndSyncToParent();
        } else {
            $this->toAirportPublicTicketCostsByEmployee = [];
            $this->toAirportTicketFiles = [];
            $this->preTransferPublicStationStart = null;
            $this->preTransferPublicStationEnd = null;
            $this->transferToAirportWaypoints = ['base', 'sap'];
            $this->ensureSingleSapInPreTransferWaypoints();
            $this->syncTransferToAirportStartsFromBaseFromWaypoints();
            $this->invalidatePreRouteMetricsAndSyncToParent();
        }
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function updatedTransferToAirportGroundMode(): void
    {
        if (! $this->isPublicTransport || $this->transferToAirportLegKind !== 'own') {
            return;
        }
        $this->applyTransferToAirportGroundModeChanged();
    }

    protected function applyTransferFromAirportGroundModeSideEffects(): void
    {
        if (! $this->isPublicTransport || ! $this->postAirportTransferUserEnabled) {
            return;
        }
        if ($this->transferFromAirportGroundMode === 'other') {
            $this->transferVehicleId = null;
            $this->transferDriverEmployeeId = null;
            $this->transferDriverBonusAmount = null;
            $this->invalidateRouteMetricsAndSyncToParent();
        } else {
            $this->fromAirportPublicTicketCostsByEmployee = [];
            $this->fromAirportTicketFiles = [];
            $this->invalidateRouteMetricsAndSyncToParent();
        }
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function updatedTransferFromAirportGroundMode(): void
    {
        if (! $this->isPublicTransport || ! $this->postAirportTransferUserEnabled) {
            return;
        }
        $this->applyTransferFromAirportGroundModeSideEffects();
    }

    public function confirmPostTransferModal(): void
    {
        $draft = $this->postTransferConfigModalGroundMode;
        if ($draft !== null && in_array($draft, ['car', 'other'], true) && $draft !== $this->transferFromAirportGroundMode) {
            $this->transferFromAirportGroundMode = $draft;
            $this->applyTransferFromAirportGroundModeSideEffects();
        }
        $this->postTransferConfigModalGroundMode = null;

        if ($this->transferFromAirportGroundMode === 'other') {
            $this->confirmPostTransferOtherSection();
        } else {
            $this->confirmPostTransferCarSection();
        }
    }

    public function confirmPostTransferCarSection(): void
    {
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own' || $this->transferFromAirportGroundMode !== 'car') {
            return;
        }
        if ($this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete) {
            return;
        }
        $this->postTransferCarSectionCollapsed = true;
        $this->showPostTransferConfigModal = false;
        $this->postTransferConfigModalGroundMode = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function confirmPostTransferOtherSection(): void
    {
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own' || $this->transferFromAirportGroundMode !== 'other') {
            return;
        }
        if ($this->fromAirportGroundTicketsIncomplete) {
            return;
        }
        $this->postTransferOtherSectionCollapsed = true;
        $this->showPostTransferConfigModal = false;
        $this->postTransferConfigModalGroundMode = null;
        $this->rebuildRouteSegmentsFromUiState();
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    public function openPostTransferConfigModal(): void
    {
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own') {
            return;
        }
        $this->showPostTransferRouteModal = false;
        $this->showPostTransferGroundModeSwitchModal = false;
        $this->pendingPostTransferModalGroundMode = null;
        $this->postTransferConfigModalGroundMode = $this->transferFromAirportGroundMode;
        $this->showPostTransferConfigModal = true;
    }

    public function closePostTransferConfigModal(): void
    {
        if (! $this->showPostTransferConfigModal) {
            return;
        }
        $this->showPostTransferConfigModal = false;
        $this->showPostTransferGroundModeSwitchModal = false;
        $this->pendingPostTransferModalGroundMode = null;
        $this->postTransferConfigModalGroundMode = null;
        if ($this->postAirportTransferUserEnabled && $this->effectiveTransferFromAirportLegKind === 'own') {
            $this->rebuildRouteSegmentsFromUiState();
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
            $this->dispatchTransferConfig();
        }
    }

    public function requestPostTransferModalGroundMode(string $mode): void
    {
        if (! $this->isPublicTransport || $this->effectiveTransferFromAirportLegKind !== 'own' || ! $this->showPostTransferConfigModal) {
            return;
        }
        if (! in_array($mode, ['car', 'other'], true)) {
            return;
        }
        $current = $this->postTransferConfigModalGroundMode ?? $this->transferFromAirportGroundMode;
        if ($mode === $current) {
            return;
        }
        if ($this->postTransferModalSwitchWouldLoseData($current, $mode)) {
            $this->pendingPostTransferModalGroundMode = $mode;
            $this->showPostTransferGroundModeSwitchModal = true;

            return;
        }
        $this->applyPostTransferModalDraftGroundMode($mode);
    }

    public function confirmPostTransferGroundModeSwitch(): void
    {
        if ($this->pendingPostTransferModalGroundMode === null
            || ! in_array($this->pendingPostTransferModalGroundMode, ['car', 'other'], true)) {
            $this->showPostTransferGroundModeSwitchModal = false;
            $this->pendingPostTransferModalGroundMode = null;

            return;
        }
        $mode = $this->pendingPostTransferModalGroundMode;
        $this->pendingPostTransferModalGroundMode = null;
        $this->showPostTransferGroundModeSwitchModal = false;
        $this->applyPostTransferModalDraftGroundMode($mode);
    }

    public function cancelPostTransferGroundModeSwitch(): void
    {
        $this->pendingPostTransferModalGroundMode = null;
        $this->showPostTransferGroundModeSwitchModal = false;
    }

    protected function postTransferModalSwitchWouldLoseData(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }
        if ($from === 'car') {
            return $this->postTransferModalHasCarData();
        }

        return $this->postTransferModalHasOtherData();
    }

    protected function postTransferModalHasCarData(): bool
    {
        if (! empty($this->transferVehicleId) || ! empty($this->transferDriverEmployeeId)) {
            return true;
        }
        $bonus = $this->transferDriverBonusAmount;
        if ($bonus !== null && $bonus !== '' && is_numeric($bonus) && (float) $bonus > 0) {
            return true;
        }

        return count($this->routeWaypoints) > 0;
    }

    protected function postTransferModalHasOtherData(): bool
    {
        foreach ($this->fromAirportPublicTicketCostsByEmployee as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! empty($row['attachment'] ?? null) || ! empty($row['attachment_path'] ?? null)) {
                return true;
            }
            $amount = $row['amount'] ?? null;
            if ($amount !== null && $amount !== '' && is_numeric($amount) && (float) $amount > 0) {
                return true;
            }
            if (trim((string) ($row['notes'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function applyPostTransferModalDraftGroundMode(string $mode): void
    {
        if (! in_array($mode, ['car', 'other'], true)) {
            return;
        }
        $this->postTransferConfigModalGroundMode = $mode;
        if ($mode === 'car') {
            $this->fromAirportPublicTicketCostsByEmployee = [];
            $this->fromAirportTicketFiles = [];
            $this->invalidateRouteMetricsAndSyncToParent();
        } else {
            $this->transferVehicleId = null;
            $this->transferDriverEmployeeId = null;
            $this->transferDriverBonusAmount = null;
            $this->invalidateRouteMetricsAndSyncToParent();
        }
    }

    protected function planTransferRoute($accommodations, $locations): void
    {
        // Transfer route: [optional pickup] → end airport → accommodations
        $endAirport = $this->sharedEndAirportLocationId
            ? Location::find($this->sharedEndAirportLocationId)
            : null;

        if (! $endAirport) {
            $this->routeError = 'Wybierz lotnisko docelowe, aby zaplanować trasę transferu.';

            return;
        }

        if (! $endAirport->hasCoordinates()) {
            $this->routeError = 'Brak współrzędnych dla lotniska docelowego: '.$endAirport->name.'. Edytuj lokalizację.';

            return;
        }

        if ($this->effectiveTransferFromAirportLegKind === 'public' || $this->transferFromAirportGroundMode === 'other') {
            $this->routeError = 'Odcinek „z lotniska” jest bez automatycznego liczenia km — wpisz dystans ręcznie albo wybierz transfer własny (firma) z trybem „Samochód”.';

            return;
        }

        // Lotnisko przylotu → przystanki → ostatnie mieszkanie
        $startPoint = $endAirport;
        $intermediateWaypoints = [];

        // Pozostałe przystanki: wszystkie poza ostatnim jako pośrednie
        $waypointList = $this->resolveWaypointObjects($accommodations, $locations);
        $lastWaypoint = array_pop($waypointList);
        if (! $lastWaypoint) {
            $this->routeError = 'Brak domów do zaplanowania transferu. Wróć do kroku 2 i przypisz mieszkania.';

            return;
        }
        foreach ($waypointList as $w) {
            $intermediateWaypoints[] = $w;
        }

        $this->isPlanningRoute = true;
        $this->routeError = null;
        $previousRouteData = $this->routeData;

        try {
            // Build a debug map of coordinate index -> point (matches RoutePlanningService coordinate order)
            $debugPoints = [];
            $debugPoints[] = [
                'label' => 'Start',
                'type' => get_class($startPoint),
                'id' => $startPoint->id ?? null,
                'name' => $startPoint->name ?? '—',
                'coords' => $startPoint->getCoordinates(),
            ];
            foreach ($intermediateWaypoints as $wp) {
                $debugPoints[] = [
                    'label' => 'Waypoint',
                    'type' => get_class($wp),
                    'id' => $wp->id ?? null,
                    'name' => $wp->name ?? '—',
                    'coords' => $wp->getCoordinates(),
                ];
            }
            $debugPoints[] = [
                'label' => 'End',
                'type' => get_class($lastWaypoint),
                'id' => $lastWaypoint->id ?? null,
                'name' => $lastWaypoint->name ?? '—',
                'coords' => $lastWaypoint->getCoordinates(),
            ];

            $route = $this->routePlanningService->planRouteWithWaypoints($startPoint, $lastWaypoint, $intermediateWaypoints, []);

            if ($route) {
                $this->isManualRouteDistance = false;
                $this->manualRouteHint = null;
                $this->routeData = ['distance' => $route['distance'], 'duration' => $route['duration']];
                $this->syncManualFieldsFromRouteData();
                $this->routeError = null;
                // Also dispatch to parent
                $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
                $this->dispatchTransferConfig();
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy transferu. Sprawdź lotnisko docelowe oraz domy / przystanki.';
                $this->routeData = $previousRouteData;
            }
        } catch (\Exception $e) {
            $extraHint = null;
            if (preg_match('/specified coordinate\\s+(\\d+):\\s+([0-9.\\-]+)\\s+([0-9.\\-]+)/i', $e->getMessage(), $m)) {
                $coordIndex = (int) $m[1];
                $failed = $debugPoints[$coordIndex] ?? null;
                if ($failed) {
                    $coords = $failed['coords'];
                    $coordText = is_array($coords) ? ($coords[0].', '.$coords[1]) : '—';
                    $extraHint = ' Problem dotyczy punktu #'.$coordIndex.': '.($failed['name'] ?? '—').' ('.($failed['label'] ?? 'punkt').', '.$coordText.').';
                    $this->manualRouteHint = ($failed['name'] ?? null) ?: null;
                } else {
                    $extraHint = ' Problem dotyczy punktu #'.$coordIndex.' (nie udało się go zmapować na nazwę).';
                    $this->manualRouteHint = null;
                }
            }

            Log::error('Route planning exception (transfer)', [
                'message' => $e->getMessage(),
                'end_airport_location_id' => $this->sharedEndAirportLocationId,
                'route_waypoints' => $this->routeWaypoints,
                'debug_points' => $debugPoints ?? null,
            ]);
            $this->routeError = 'Błąd podczas planowania trasy transferu: '.$e->getMessage().($extraHint ?? '');
            $this->routeData = $previousRouteData;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    // ─── Trip plan ─────────────────────────────────────────────────────────────

    public function getTripPlanProperty(): array
    {
        $plan = [];
        $isPublicTransport = $this->isPublicTransport;

        $employeeIds = array_keys($this->accommodationAssignments);
        $accommodationRows = array_values(array_filter($this->accommodationAssignments, 'is_array'));
        $rangeRows = array_values(array_filter($this->assignmentRanges, 'is_array'));
        $vehicleRows = array_values(array_filter($this->vehicleAssignments, 'is_array'));
        $accommodationIds = array_unique(array_filter(array_column($accommodationRows, 'accommodation_id')));
        $projectIds = array_unique(array_filter(array_column($rangeRows, 'project_id')));
        $vehicleIds = array_unique(array_filter(array_column($vehicleRows, 'vehicle_id')));

        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        $accommodations = Accommodation::whereIn('id', $accommodationIds)->get()->keyBy('id');
        $projects = \App\Models\Project::with('location')->whereIn('id', $projectIds)->get()->keyBy('id');
        $vehicles = Vehicle::whereIn('id', $vehicleIds)->get()->keyBy('id');

        $airportNames = collect();
        if ($isPublicTransport) {
            $airportIds = collect([$this->sharedStartAirportLocationId, $this->sharedEndAirportLocationId])
                ->filter()
                ->map('intval')
                ->unique();
            if ($airportIds->isNotEmpty()) {
                $airportNames = Location::whereIn('id', $airportIds)->pluck('name', 'id');
            }
        }

        $employeeToProject = [];
        foreach ($this->assignmentRanges as $range) {
            if (! is_array($range)) {
                continue;
            }
            $employeeId = (int) ($range['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }
            $employeeToProject[$employeeId] = $range['project_id'] ?? null;
        }

        foreach ($this->accommodationAssignments as $employeeId => $accommodationAssignment) {
            if (! is_array($accommodationAssignment)) {
                continue;
            }
            $accommodationId = $accommodationAssignment['accommodation_id'] ?? null;
            if (! $accommodationId) {
                continue;
            }

            $employee = $employees->get($employeeId);
            $accommodation = $accommodations->get($accommodationId);
            if (! $employee || ! $accommodation) {
                continue;
            }

            $projectId = $employeeToProject[$employeeId] ?? null;
            $project = $projectId ? $projects->get($projectId) : null;
            $projectName = $project ? $project->name : null;

            $vehicleId = data_get($this->vehicleAssignments, $employeeId.'.vehicle_id');
            $vehicle = $vehicleId ? $vehicles->get($vehicleId) : null;
            $vehicleName = $vehicle ? ($vehicle->registration_number.' - '.$vehicle->brand.' '.$vehicle->model) : null;

            $distance = null;
            if (! $isPublicTransport && $project && $project->location && $accommodation->hasCoordinates() && $project->location->hasCoordinates()) {
                $distance = $this->getCachedDistance($accommodation, $project->location);
            }

            $ticket = null;
            if ($isPublicTransport) {
                $ticketData = $this->ticketCostsByEmployee[$employeeId] ?? [];
                $amount = $ticketData['amount'] ?? null;
                $currency = $ticketData['currency'] ?? null;
                if ($amount !== null || $currency !== null) {
                    $ticket = [
                        'amount' => $amount,
                        'currency' => $currency,
                        'start_airport_name' => $airportNames[$this->sharedStartAirportLocationId] ?? null,
                        'end_airport_name' => $airportNames[$this->sharedEndAirportLocationId] ?? null,
                    ];
                }
            }

            if (! isset($plan[$accommodationId])) {
                $plan[$accommodationId] = [
                    'accommodation' => [
                        'id' => $accommodation->id,
                        'name' => $accommodation->name,
                        'address' => $accommodation->getFullAddress(),
                    ],
                    'employees' => [],
                ];
            }

            $plan[$accommodationId]['employees'][] = [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'project_name' => $projectName,
                'distance' => $distance,
                'vehicle_name' => $vehicleName,
                'ticket' => $ticket,
            ];
        }

        // Sort plan by routeWaypoints order, using parsed IDs (waypoints are 'acc:ID' strings)
        $sortedPlan = [];
        foreach ($this->routeWaypoints as $routeIdx => $waypointKey) {
            $parsed = $this->parseWaypointKey($waypointKey);
            if ($parsed['type'] === 'acc' && isset($plan[$parsed['id']])) {
                $stop = $plan[$parsed['id']];
                $stop['route_index'] = $routeIdx; // actual index in routeWaypoints for moveUp/moveDown
                $sortedPlan[] = $stop;
            }
        }

        return $sortedPlan;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function getEmployeesForAccommodation($accommodationId): Collection
    {
        $employeeIds = [];
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            if (! is_array($assignment)) {
                continue;
            }
            if (isset($assignment['accommodation_id']) && (int) $assignment['accommodation_id'] === (int) $accommodationId) {
                $employeeIds[] = $employeeId;
            }
        }
        if (empty($employeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $employeeIds)->get();
    }

    protected function getCachedDistance($accommodation, $location): ?float
    {
        try {
            $route = $this->routePlanningService->planRouteWithWaypoints($accommodation, $location, []);

            return $route['distance'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toJSON(): array
    {
        return [];
    }

    public function render()
    {
        $waypointStops = $this->waypointStops;

        return view('livewire.steps.step4-route-planning', [
            'baseLocationData' => $this->baseLocationData,
            'startAirportData' => $this->startAirportData,
            'endAirportData' => $this->endAirportData,
            'pickupLocationData' => $this->pickupLocationData,
            'waypointAccommodations' => $this->waypointAccommodations,
            'waypointStops' => $waypointStops,
            'preTransferRouteTiles' => $this->preTransferRouteTiles,
            'routeWaypoints' => $this->routeWaypoints,
            'extraStopLocationId' => $this->extraStopLocationId,
            'tripPlan' => $this->tripPlan,
            'isPublicTransport' => $this->isPublicTransport,
            'currencyCases' => $this->currencyCases,
            'availableVehicles' => $this->availableVehicles,
            'availableEmployees' => $this->availableEmployees,
            'availableLocations' => $this->availableLocations,
            'transferToAirportLegKind' => $this->transferToAirportLegKind,
            'transferToAirportGroundMode' => $this->transferToAirportGroundMode,
            'airportHubLegKind' => $this->airportHubLegKind,
            'transferFromAirportLegKind' => $this->transferFromAirportLegKind,
            'transferFromAirportGroundMode' => $this->transferFromAirportGroundMode,
            'airportHubModePickerOpen' => $this->airportHubModePickerOpen,
            'transferFromAirportModePickerOpen' => $this->transferFromAirportModePickerOpen,
            'postAirportTransferUserEnabled' => $this->postAirportTransferUserEnabled,
            'effectiveTransferFromAirportLegKind' => $this->effectiveTransferFromAirportLegKind,
            'selectedEmployeesForTickets' => $this->selectedEmployeesForTickets,
            'publicTransportHubKind' => $this->publicTransportHubKind,
        ]);
    }
}
