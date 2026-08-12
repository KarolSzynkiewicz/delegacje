<?php

namespace App\Models;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * LogisticsEvent - fakt biznesowy (co, kiedy, kto, gdzie)
 *
 * IMPORTANT: Model = tylko fakty, zero logiki biznesowej.
 * Wszystka logika w serwisach (ReturnTripService, DepartureService).
 */
class LogisticsEvent extends Model
{
    use HasComments, HasFactory;

    protected $fillable = [
        'type',
        'event_date',
        'end_date',
        'has_transport',
        'vehicle_id',
        'transport_id',
        'from_location_id',
        'to_location_id',
        'status',
        'notes',
        'created_by',
        'route_distance',
        'route_duration',
        'route_waypoints',
        'location_stop_notes',
        'route_segments',
        'destination_stop_location',
        'has_reassignment',
        'related_departure_id',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'end_date' => 'datetime',
        'has_transport' => 'boolean',
        'has_reassignment' => 'boolean',
        'type' => LogisticsEventType::class,
        'status' => LogisticsEventStatus::class,
        'route_distance' => 'decimal:2',
        'route_duration' => 'integer',
        'route_waypoints' => 'array',
        'location_stop_notes' => 'array',
        'route_segments' => 'array',
    ];

    /**
     * Get the vehicle for this event (if company vehicle).
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the transport for this event (if public transport).
     */
    public function transport(): BelongsTo
    {
        return $this->belongsTo(Transport::class);
    }

    /**
     * Get the from location.
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * Get the to location.
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * Get the user who created this event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => '—',
        ]);
    }

    /**
     * Get all participants in this event.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(LogisticsEventParticipant::class);
    }

    /**
     * Get project assignments created from this logistics event.
     */
    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    /**
     * Get vehicle assignments created from this logistics event.
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * Get accommodation assignments created from this logistics event.
     */
    public function accommodationAssignments(): HasMany
    {
        return $this->hasMany(AccommodationAssignment::class);
    }

    /**
     * Get transport costs related to this logistics event.
     */
    public function transportCosts(): HasMany
    {
        return $this->hasMany(TransportCost::class);
    }

    /**
     * Get driver payment adjustments linked to this transfer.
     */
    public function driverAdjustments(): HasMany
    {
        return $this->hasMany(Adjustment::class);
    }

    /**
     * Wyjazd (DEPARTURE), z którego utworzono ten transfer (np. lotnisko → baza).
     */
    public function relatedDeparture(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_departure_id');
    }

    /**
     * Transfery powiązane z tym wyjazdem (np. transfer z lotniska przy transporcie zbiorowym).
     */
    public function transfersLinkedFromThisDeparture(): HasMany
    {
        return $this->hasMany(self::class, 'related_departure_id');
    }

    /**
     * Get the duration of the trip in days.
     */
    public function getDurationInDays(): int
    {
        if (! $this->end_date) {
            return 0;
        }

        return $this->event_date->diffInDays($this->end_date);
    }

    /**
     * Get visual status based on dates (for display purposes).
     *
     * Returns: 'oczekuje', 'w trakcie', 'zakończone', 'anulowany'
     */
    public function getVisualStatus(): string
    {
        if ($this->status === LogisticsEventStatus::CANCELLED) {
            return 'anulowany';
        }

        $now = now()->startOfDay(); // Compare dates only, not time
        $eventDate = $this->event_date->startOfDay();
        $endDate = $this->end_date ? $this->end_date->startOfDay() : $eventDate;

        // If trip hasn't started yet (event_date is in the future)
        if ($eventDate->gt($now)) {
            return 'oczekuje';
        }

        // If trip has ended (end_date is in the past)
        if ($endDate->lt($now)) {
            return 'zakończone';
        }

        // Trip is happening now (event_date <= now <= end_date)
        return 'w trakcie';
    }

    /**
     * Get the effective end date (use end_date if available, otherwise event_date).
     */
    public function getEffectiveEndDate(): \Carbon\Carbon
    {
        return $this->end_date ?? $this->event_date;
    }

    /**
     * Transfery bez zmiany przypisań nie wpływają na śledzenie lokalizacji (stan pracownika / pojazdu).
     */
    public function scopeForLocationTracking($query)
    {
        return $query->where(function ($q) {
            $q->where('type', '!=', LogisticsEventType::TRANSFER)
                ->orWhere('has_reassignment', true);
        });
    }

    /**
     * Scope: Get events where employee is in transit on given date.
     *
     * In transit = between event_date (inclusive) and end_date (exclusive)
     * On arrival date (end_date), employee is already at destination.
     */
    public function scopeInTransitOn($query, Employee $employee, \Carbon\Carbon $date)
    {
        $dateNormalized = $date->copy()->startOfDay();

        return $query->forLocationTracking()
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN, LogisticsEventType::TRANSFER])
            ->whereHas('participants', fn ($q) => $q->where('employee_id', $employee->id))
            ->where('event_date', '<=', $dateNormalized)
            ->where('end_date', '>', $dateNormalized)
            ->whereIn('status', [
                LogisticsEventStatus::PLANNED,
                LogisticsEventStatus::COMPLETED,
            ]);
    }

    /**
     * Check if employee is in transit on given date.
     */
    public static function isEmployeeInTransit(Employee $employee, \Carbon\Carbon $date): bool
    {
        return static::query()->inTransitOn($employee, $date)->exists();
    }

    /**
     * Scope: Get PLANNED departures for employee to specific location.
     */
    public function scopePlannedDeparturesTo($query, Employee $employee, int $locationId)
    {
        return $query->where('type', LogisticsEventType::DEPARTURE)
            ->where('to_location_id', $locationId)
            ->where('status', LogisticsEventStatus::PLANNED)
            ->whereHas('participants', fn ($q) => $q->where('employee_id', $employee->id));
    }

    /**
     * Get count of participants with project assignments from this logistics event.
     */
    public function getAssignedParticipantsCount(): int
    {
        return $this->participants()
            ->whereHas('employee.projectAssignments', function ($query) {
                $query->where('logistics_event_id', $this->id);
            })
            ->count();
    }

    /**
     * Get total participants count.
     */
    public function getTotalParticipantsCount(): int
    {
        return $this->participants()->count();
    }

    /**
     * Check if all participants are assigned to projects.
     */
    public function allParticipantsAssigned(): bool
    {
        return $this->getAssignedParticipantsCount() === $this->getTotalParticipantsCount();
    }

    /**
     * Update completion status based on participant assignments.
     */
    public function updateCompletionStatus(): void
    {
        if ($this->allParticipantsAssigned() && $this->status === LogisticsEventStatus::PLANNED) {
            $this->update(['status' => LogisticsEventStatus::COMPLETED]);
        }
    }

    /**
     * Check if route data is available.
     */
    public function hasRouteData(): bool
    {
        return ! is_null($this->route_distance) && ! is_null($this->route_duration);
    }

    /**
     * Get route distance formatted (km).
     *
     * Prosty transfer (TransferCreateBoard + GroundTransferSlot) zapisuje route_distance w metrach (jak ORS × 1000).
     * Wyjazdy i transfery utworzone z planera wyjazdu trzymają dystans w kilometrach.
     */
    public function getFormattedDistance(): ?string
    {
        if (! $this->hasRouteData()) {
            return null;
        }

        $raw = (float) $this->route_distance;
        $isTransferInMeters = $this->type === LogisticsEventType::TRANSFER
            && $this->related_departure_id === null;
        $km = $isTransferInMeters ? $raw / 1000 : $raw;

        $rounded = round($km, 1);
        $decimals = abs($rounded - (int) $rounded) < 0.001 ? 0 : 1;

        return number_format($rounded, $decimals, ',', ' ').' km';
    }

    /**
     * Get route duration formatted (hours and minutes).
     */
    public function getFormattedDuration(): ?string
    {
        if (! $this->hasRouteData()) {
            return null;
        }

        $hours = floor($this->route_duration / 3600);
        $minutes = floor(($this->route_duration % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%d h %d min', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    /**
     * Rozpoznaje pojedynczy wpis trasy z planera (acc: / loc:) lub legacy sam identyfikator akomodacji.
     *
     * @return array{type: string, id: int}|null
     */
    public static function parseRouteWaypointKey(mixed $waypoint): ?array
    {
        if ($waypoint === null || $waypoint === '') {
            return null;
        }
        if (is_int($waypoint) || (is_string($waypoint) && ctype_digit((string) $waypoint))) {
            return ['type' => 'acc', 'id' => (int) $waypoint];
        }
        $s = strtolower(trim((string) $waypoint));
        if ($s === 'base') {
            return ['type' => 'base', 'id' => 0];
        }
        if ($s === 'sap') {
            return ['type' => 'sap', 'id' => 0];
        }
        if (! str_contains($s, ':')) {
            return null;
        }
        [$type, $rest] = explode(':', $s, 2);
        $id = (int) $rest;
        if ($id <= 0) {
            return null;
        }
        if ($type === 'loc') {
            return ['type' => 'loc', 'id' => $id];
        }
        if ($type === 'acc') {
            return ['type' => 'acc', 'id' => $id];
        }

        return null;
    }

    /**
     * @param  array<int, mixed>|null  $waypoints
     * @return list<string>
     */
    public static function normalizeRouteWaypointsFromPayload(?array $waypoints): array
    {
        if (empty($waypoints)) {
            return [];
        }
        $out = [];
        foreach ($waypoints as $w) {
            $p = self::parseRouteWaypointKey($w);
            if ($p === null) {
                continue;
            }
            if (($p['type'] ?? '') === 'base') {
                $out[] = 'base';
            } elseif (($p['type'] ?? '') === 'sap') {
                $out[] = 'sap';
            } else {
                $out[] = $p['type'].':'.$p['id'];
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @param  list<string>  $normalizedWaypointKeys
     */
    public static function sanitizeLocationStopNotes(?array $raw, array $normalizedWaypointKeys): ?array
    {
        if (empty($raw) || ! is_array($raw)) {
            return null;
        }
        $allowedLocIds = [];
        foreach ($normalizedWaypointKeys as $key) {
            $p = self::parseRouteWaypointKey($key);
            if (($p['type'] ?? '') === 'loc') {
                $allowedLocIds[] = (string) $p['id'];
            }
        }
        $out = [];
        foreach ($allowedLocIds as $lid) {
            if (! array_key_exists($lid, $raw)) {
                continue;
            }
            $text = mb_substr(trim((string) $raw[$lid]), 0, 5000);
            if ($text !== '') {
                $out[$lid] = $text;
            }
        }

        return empty($out) ? null : $out;
    }

    /**
     * Get waypoint accommodations (in order), tylko przystanki typu mieszkanie (acc).
     */
    public function getWaypointAccommodations(): Collection
    {
        $ids = [];
        foreach ($this->route_waypoints ?? [] as $w) {
            $p = self::parseRouteWaypointKey($w);
            if (($p['type'] ?? '') === 'acc') {
                $ids[] = $p['id'];
            }
        }
        if ($ids === []) {
            return collect();
        }

        $accommodations = Accommodation::whereIn('id', $ids)->get()->keyBy('id');
        $ordered = collect();
        foreach ($ids as $id) {
            if ($accommodations->has($id)) {
                $ordered->push($accommodations->get($id));
            }
        }

        return $ordered;
    }

    /**
     * Przystanki pośrednie w kolejności do widoku szczegółów (mieszkania + lokalizacje dodane ręcznie).
     * Pomija start/cel zdarzenia (from/to) — te są renderowane osobno jako „Start” / „Cel podróży”.
     *
     * @return Collection<int, array{position: int, kind: string, model_id: int, name: string, address_line: string, employees_label: ?string, purpose: ?string}>
     */
    public function getRouteStopsForDetailView(): Collection
    {
        $notes = $this->location_stop_notes ?? [];
        $items = collect();
        $pos = 0;
        $fromId = $this->from_location_id ? (int) $this->from_location_id : null;
        $toId = $this->to_location_id ? (int) $this->to_location_id : null;
        $toLocation = $toId ? ($this->relationLoaded('toLocation') ? $this->toLocation : Location::find($toId)) : null;

        $raw = array_values($this->route_waypoints ?? []);
        $lastIndex = count($raw) - 1;

        foreach ($raw as $index => $w) {
            $p = self::parseRouteWaypointKey($w);
            if ($p === null) {
                continue;
            }
            if (($p['type'] ?? '') === 'base' || ($p['type'] ?? '') === 'sap') {
                continue;
            }

            if ($p['type'] === 'loc') {
                $locId = (int) $p['id'];
                // Duplikat startu/celu: w planie często jest loc:baza i loc:cel, a widok i tak rysuje from/to.
                if ($fromId && $locId === $fromId) {
                    continue;
                }
                if ($toId && $locId === $toId) {
                    continue;
                }

                $loc = Location::find($locId);
                if (! $loc) {
                    continue;
                }
                $pos++;
                $noteKey = (string) $locId;
                $purpose = isset($notes[$noteKey]) && trim((string) $notes[$noteKey]) !== ''
                    ? trim((string) $notes[$noteKey])
                    : null;
                $addressLine = trim(implode(', ', array_filter([$loc->address, $loc->city ?? null])));
                $items->push([
                    'position' => $pos,
                    'kind' => 'extra_location',
                    'model_id' => $loc->id,
                    'name' => $loc->name,
                    'address_line' => $addressLine,
                    'employees_label' => null,
                    'purpose' => $purpose,
                ]);

                continue;
            }

            if ($p['type'] === 'acc') {
                $acc = Accommodation::find($p['id']);
                if (! $acc) {
                    continue;
                }

                // Ostatnie mieszkanie często = cel (to_location utworzone z tego adresu) — nie dubluj z kartą „Cel podróży”.
                if ($index === $lastIndex && $toLocation && $this->accommodationMatchesLocation($acc, $toLocation)) {
                    continue;
                }

                $pos++;
                $emps = $this->relationLoaded('accommodationAssignments')
                    ? $this->accommodationAssignments->where('accommodation_id', $acc->id)->map(fn ($a) => $a->employee?->full_name)->filter()
                    : collect();
                $addressLine = trim(implode(', ', array_filter([$acc->address, $acc->city ?? null])));
                $items->push([
                    'position' => $pos,
                    'kind' => 'accommodation',
                    'model_id' => $acc->id,
                    'name' => $acc->name,
                    'address_line' => $addressLine,
                    'employees_label' => $emps->isNotEmpty() ? $emps->join(', ') : null,
                    'purpose' => null,
                ]);
            }
        }

        return $items;
    }

    /**
     * Czy mieszkanie odpowiada lokalizacji docelowej (ten sam adres / miasto).
     */
    protected function accommodationMatchesLocation(Accommodation $acc, Location $location): bool
    {
        $norm = static fn (?string $v): string => mb_strtolower(trim((string) $v));

        if ($norm($acc->address) !== '' && $norm($acc->address) === $norm($location->address)
            && $norm($acc->city) === $norm($location->city)) {
            return true;
        }

        if ($norm($acc->name) !== '' && $norm($acc->name) === $norm($location->name)
            && $norm($acc->city) === $norm($location->city)) {
            return true;
        }

        return false;
    }
}
