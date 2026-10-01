<?php

namespace App\Livewire;

use App\Enums\LocationPurposeType;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\ProjectStatus;
use App\Models\Accommodation;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Services\RoutePlanningService;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * Bezinwazyjna edycja przebiegu trasy (własny transport) dla wyjazdu lub transferu:
 * waypointy, notatki przystanków, dystans/czas — ten sam panel co Step4 / GroundTransferSlot.
 */
class DepartureRouteEditor extends Component
{
    public int $departureId;

    public bool $showModal = false;

    public string $triggerLabel = 'Edytuj trasę';

    public string $triggerVariant = 'outline';

    /** departure|transfer — for UI copy without extra queries */
    public string $eventKind = 'departure';

    /** @var list<string> */
    public array $routeWaypoints = [];

    /** @var array<string, string> */
    public array $locationStopNotes = [];

    public ?float $routeDistanceKm = null;

    public ?int $routeDurationSeconds = null;

    public $manualDistanceKm = null;

    public $manualDurationMin = null;

    public ?int $pendingWaypointLocationId = null;

    public ?string $orsError = null;

    public ?string $saveError = null;

    public function mount(LogisticsEvent $departure): void
    {
        if (! in_array($departure->type, [LogisticsEventType::DEPARTURE, LogisticsEventType::TRANSFER], true)) {
            abort(404);
        }

        $this->departureId = (int) $departure->id;
        $this->eventKind = $departure->type === LogisticsEventType::TRANSFER ? 'transfer' : 'departure';
        $this->hydrateFromDeparture($departure);
    }

    public function openModal(): void
    {
        $departure = $this->departure();
        if (! $this->canEdit($departure)) {
            return;
        }

        $this->hydrateFromDeparture($departure);
        $this->orsError = null;
        $this->saveError = null;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->orsError = null;
        $this->saveError = null;
    }

    public function addWaypoint(): void
    {
        $id = $this->pendingWaypointLocationId;
        if (! $id) {
            return;
        }

        $key = 'loc:'.(int) $id;
        if (! in_array($key, $this->routeWaypoints, true)) {
            $this->routeWaypoints[] = $key;
            $this->routeWaypoints = array_values($this->routeWaypoints);
            $this->locationStopNotes[(string) (int) $id] ??= '';
            $this->invalidateMetrics();
        }

        $this->pendingWaypointLocationId = null;
    }

    public function removeWaypoint(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        array_splice($this->routeWaypoints, $index, 1);
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->pruneNotes();
        $this->invalidateMetrics();
    }

    public function moveWaypointUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        [$this->routeWaypoints[$index - 1], $this->routeWaypoints[$index]] =
            [$this->routeWaypoints[$index], $this->routeWaypoints[$index - 1]];
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->invalidateMetrics();
    }

    public function moveWaypointDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints) - 1) {
            return;
        }
        [$this->routeWaypoints[$index], $this->routeWaypoints[$index + 1]] =
            [$this->routeWaypoints[$index + 1], $this->routeWaypoints[$index]];
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->invalidateMetrics();
    }

    public function recalculateRouteWithOrs(): void
    {
        $this->orsError = null;
        $chain = $this->resolveLocationChainForOrs();
        if (count($chain) < 2) {
            $this->orsError = 'Potrzeba co najmniej dwóch punktów z współrzędnymi — lub wpisz dystans i czas ręcznie.';

            return;
        }

        $route = app(RoutePlanningService::class)->planRouteAlongOrderedLocations($chain);
        if ($route === null) {
            $this->orsError = 'Nie udało się wyliczyć trasy (ORS). Wpisz dystans i czas ręcznie.';

            return;
        }

        $this->routeDistanceKm = round((float) $route['distance'], 1);
        $this->routeDurationSeconds = (int) $route['duration'];
        $this->manualDistanceKm = $this->routeDistanceKm;
        $this->manualDurationMin = (int) round($this->routeDurationSeconds / 60);
    }

    public function applyManualMetrics(): void
    {
        if ($this->manualDistanceKm !== null && $this->manualDistanceKm !== '') {
            $this->routeDistanceKm = round((float) $this->manualDistanceKm, 1);
        }
        if ($this->manualDurationMin !== null && $this->manualDurationMin !== '') {
            $this->routeDurationSeconds = (int) round((float) $this->manualDurationMin * 60);
        }
    }

    public function save(): void
    {
        $this->saveError = null;
        $this->applyManualMetrics();

        $departure = $this->departure();
        if (! $this->canEdit($departure)) {
            $this->saveError = $departure->type === LogisticsEventType::TRANSFER
                ? 'Tego transferu nie można edytować.'
                : 'Tego wyjazdu nie można edytować.';

            return;
        }

        $normalized = LogisticsEvent::normalizeRouteWaypointsFromPayload($this->routeWaypoints);
        if ($normalized === []) {
            $this->saveError = 'Dodaj przynajmniej jeden przystanek trasy.';

            return;
        }

        $notes = LogisticsEvent::sanitizeLocationStopNotes($this->locationStopNotes, $normalized);

        $attributes = [
            'route_waypoints' => $normalized,
            'route_distance' => $this->routeDistanceKm,
            'route_duration' => $this->routeDurationSeconds,
        ];

        if (Schema::hasColumn('logistics_events', 'location_stop_notes')) {
            $attributes['location_stop_notes'] = $notes;
        }

        $toLocationId = $this->resolveDestinationLocationId($normalized);
        if ($toLocationId) {
            $attributes['to_location_id'] = $toLocationId;
        }

        if ($departure->type === LogisticsEventType::TRANSFER) {
            $fromLocationId = $this->resolveOriginLocationId($normalized);
            if ($fromLocationId) {
                $attributes['from_location_id'] = $fromLocationId;
            }
        }

        $departure->update($attributes);

        $this->showModal = false;
        $redirect = $departure->type === LogisticsEventType::TRANSFER
            ? route('transfers.show', $departure)
            : route('departures.show', $departure);
        $this->redirect($redirect, navigate: true);
    }

    public function getRouteTilesProperty(): array
    {
        $locIds = [];
        $accIds = [];
        foreach ($this->routeWaypoints as $key) {
            $p = LogisticsEvent::parseRouteWaypointKey($key);
            if (! $p) {
                continue;
            }
            if ($p['type'] === 'loc') {
                $locIds[] = $p['id'];
            } elseif ($p['type'] === 'acc') {
                $accIds[] = $p['id'];
            }
        }

        $locations = $locIds
            ? Location::whereIn('id', array_unique($locIds))->withCount('accommodations')->get()->keyBy('id')
            : collect();
        $accommodations = $accIds
            ? Accommodation::whereIn('id', array_unique($accIds))->get()->keyBy('id')
            : collect();

        $n = count($this->routeWaypoints);
        $tiles = [];
        $base = Location::getBase();

        foreach ($this->routeWaypoints as $index => $key) {
            $p = LogisticsEvent::parseRouteWaypointKey($key);
            if (! $p) {
                continue;
            }

            if ($p['type'] === 'loc') {
                $loc = $locations->get($p['id']);
                if (! $loc) {
                    continue;
                }
                $typeLabel = 'Lokalizacja';
                if ($loc->is_base || ($base && (int) $loc->id === (int) $base->id)) {
                    $typeLabel = 'Baza';
                } elseif ($loc->hasPurpose(LocationPurposeType::AIRPORT)) {
                    $typeLabel = 'Lotnisko';
                } elseif ($loc->hasPurpose(LocationPurposeType::STATION)) {
                    $typeLabel = 'Dworzec';
                } elseif ((int) ($loc->accommodations_count ?? 0) > 0) {
                    $typeLabel = 'Dom';
                } else {
                    $projectNames = Project::query()
                        ->where('location_id', $loc->id)
                        ->where('status', ProjectStatus::ACTIVE)
                        ->orderBy('name')
                        ->pluck('name')
                        ->unique()
                        ->values();
                    if ($projectNames->isNotEmpty()) {
                        $typeLabel = $projectNames->count() === 1
                            ? 'Projekt: '.$projectNames->first()
                            : 'Projekty: '.$projectNames->join(', ');
                    }
                }

                $tiles[] = [
                    'index' => $index,
                    'key' => (string) $key,
                    'id' => (string) $p['id'],
                    'type_label' => $typeLabel,
                    'name' => $loc->name,
                    'city' => $loc->city,
                    'address' => $loc->address,
                    'can_move_up' => $index > 0,
                    'can_move_down' => $index < $n - 1,
                    'can_remove' => true,
                ];
            } elseif ($p['type'] === 'acc') {
                $acc = $accommodations->get($p['id']);
                if (! $acc) {
                    continue;
                }
                $tiles[] = [
                    'index' => $index,
                    'key' => (string) $key,
                    'id' => 'acc_'.$p['id'],
                    'type_label' => 'Mieszkanie',
                    'name' => $acc->name,
                    'city' => $acc->city,
                    'address' => $acc->address,
                    'can_move_up' => $index > 0,
                    'can_move_down' => $index < $n - 1,
                    'can_remove' => true,
                ];
            }
        }

        return $tiles;
    }

    public function getAvailableLocationsProperty()
    {
        return Location::query()->orderBy('name')->withCount('accommodations')->get();
    }

    public function getCanEditProperty(): bool
    {
        return $this->canEdit($this->departure());
    }

    public function render()
    {
        return view('livewire.departure-route-editor');
    }

    protected function departure(): LogisticsEvent
    {
        return LogisticsEvent::query()
            ->whereKey($this->departureId)
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::TRANSFER])
            ->firstOrFail();
    }

    protected function canEdit(LogisticsEvent $departure): bool
    {
        return $departure->vehicle_id
            && in_array($departure->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true);
    }

    public function getEventKindLabelProperty(): string
    {
        return $this->eventKind === 'transfer' ? 'transfer' : 'wyjazd';
    }

    protected function hydrateFromDeparture(LogisticsEvent $departure): void
    {
        $this->routeWaypoints = array_values(
            LogisticsEvent::normalizeRouteWaypointsFromPayload(
                is_array($departure->route_waypoints) ? $departure->route_waypoints : []
            )
        );
        $this->locationStopNotes = is_array($departure->location_stop_notes)
            ? $departure->location_stop_notes
            : [];
        $this->routeDistanceKm = $departure->route_distance !== null ? (float) $departure->route_distance : null;
        $this->routeDurationSeconds = $departure->route_duration !== null ? (int) $departure->route_duration : null;
        $this->manualDistanceKm = $this->routeDistanceKm;
        $this->manualDurationMin = $this->routeDurationSeconds !== null
            ? (int) round($this->routeDurationSeconds / 60)
            : null;
        $this->pendingWaypointLocationId = null;
    }

    protected function invalidateMetrics(): void
    {
        $this->routeDistanceKm = null;
        $this->routeDurationSeconds = null;
        $this->manualDistanceKm = null;
        $this->manualDurationMin = null;
        $this->orsError = null;
    }

    protected function pruneNotes(): void
    {
        $allowed = [];
        foreach ($this->routeWaypoints as $key) {
            $p = LogisticsEvent::parseRouteWaypointKey($key);
            if ($p && $p['type'] === 'loc') {
                $allowed[(string) $p['id']] = true;
            }
        }
        $this->locationStopNotes = array_intersect_key($this->locationStopNotes, $allowed);
    }

    /**
     * @return list<Location>
     */
    protected function resolveLocationChainForOrs(): array
    {
        $chain = [];
        foreach ($this->routeWaypoints as $key) {
            $p = LogisticsEvent::parseRouteWaypointKey($key);
            if (! $p) {
                continue;
            }

            $loc = null;
            if ($p['type'] === 'loc') {
                $loc = Location::find($p['id']);
            } elseif ($p['type'] === 'acc') {
                $acc = Accommodation::find($p['id']);
                if ($acc) {
                    $loc = Location::query()
                        ->where('address', $acc->address)
                        ->where('city', $acc->city)
                        ->first();
                    if (! $loc && $acc->latitude && $acc->longitude) {
                        $loc = new Location([
                            'name' => $acc->name,
                            'address' => $acc->address,
                            'city' => $acc->city,
                            'latitude' => $acc->latitude,
                            'longitude' => $acc->longitude,
                        ]);
                    }
                }
            }

            if (! $loc instanceof Location || ! $loc->hasCoordinates()) {
                continue;
            }
            if ($chain !== [] && (int) ($chain[count($chain) - 1]->id ?? 0) === (int) ($loc->id ?? -1) && $loc->id) {
                continue;
            }
            $chain[] = $loc;
        }

        return $chain;
    }

    /**
     * @param  list<string>  $normalized
     */
    protected function resolveDestinationLocationId(array $normalized): ?int
    {
        if ($normalized === []) {
            return null;
        }

        return $this->resolveLocationIdFromWaypointKey((string) end($normalized));
    }

    /**
     * @param  list<string>  $normalized
     */
    protected function resolveOriginLocationId(array $normalized): ?int
    {
        if ($normalized === []) {
            return null;
        }

        return $this->resolveLocationIdFromWaypointKey((string) $normalized[0]);
    }

    protected function resolveLocationIdFromWaypointKey(string $key): ?int
    {
        $parsed = LogisticsEvent::parseRouteWaypointKey($key);
        if (! $parsed) {
            return null;
        }

        if ($parsed['type'] === 'loc') {
            return $parsed['id'] > 0 ? $parsed['id'] : null;
        }

        if ($parsed['type'] === 'acc') {
            $acc = Accommodation::find($parsed['id']);
            if (! $acc) {
                return null;
            }

            $existing = Location::query()
                ->where('address', $acc->address)
                ->where('city', $acc->city)
                ->where('postal_code', $acc->postal_code)
                ->where('country', $acc->country)
                ->first();

            if ($existing) {
                return (int) $existing->id;
            }

            $created = Location::create([
                'name' => $acc->name,
                'address' => $acc->address,
                'city' => $acc->city,
                'postal_code' => $acc->postal_code,
                'country' => $acc->country,
                'latitude' => $acc->latitude,
                'longitude' => $acc->longitude,
                'is_base' => false,
            ]);

            return (int) $created->id;
        }

        return null;
    }
}
