<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Traits\HasComments;
use App\Models\Accommodation;

/**
 * LogisticsEvent - fakt biznesowy (co, kiedy, kto, gdzie)
 * 
 * IMPORTANT: Model = tylko fakty, zero logiki biznesowej.
 * Wszystka logika w serwisach (ReturnTripService, DepartureService).
 */
class LogisticsEvent extends Model
{
    use HasFactory, HasComments;

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
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'end_date' => 'datetime',
        'has_transport' => 'boolean',
        'type' => LogisticsEventType::class,
        'status' => LogisticsEventStatus::class,
        'route_distance' => 'decimal:2',
        'route_duration' => 'integer',
        'route_waypoints' => 'array',
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
        return $this->belongsTo(User::class, 'created_by');
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
     * Get the duration of the trip in days.
     */
    public function getDurationInDays(): int
    {
        if (!$this->end_date) {
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
     * Scope: Get events where employee is in transit on given date.
     * 
     * In transit = between event_date (inclusive) and end_date (exclusive)
     * On arrival date (end_date), employee is already at destination.
     */
    public function scopeInTransitOn($query, Employee $employee, \Carbon\Carbon $date)
    {
        $dateNormalized = $date->copy()->startOfDay();
        
        return $query->where(function($q) {
                $q->where('type', LogisticsEventType::DEPARTURE)
                  ->orWhere('type', LogisticsEventType::RETURN);
            })
            ->whereHas('participants', fn($q) => $q->where('employee_id', $employee->id))
            ->where('event_date', '<=', $dateNormalized)
            ->where('end_date', '>', $dateNormalized)
            ->whereIn('status', [
                LogisticsEventStatus::PLANNED,
                LogisticsEventStatus::COMPLETED
            ]);
    }

    /**
     * Check if employee is in transit on given date.
     */
    public static function isEmployeeInTransit(Employee $employee, \Carbon\Carbon $date): bool
    {
        return static::inTransitOn($employee, $date)->exists();
    }

    /**
     * Scope: Get PLANNED departures for employee to specific location.
     */
    public function scopePlannedDeparturesTo($query, Employee $employee, int $locationId)
    {
        return $query->where('type', LogisticsEventType::DEPARTURE)
            ->where('to_location_id', $locationId)
            ->where('status', LogisticsEventStatus::PLANNED)
            ->whereHas('participants', fn($q) => $q->where('employee_id', $employee->id));
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
        return !is_null($this->route_distance) && !is_null($this->route_duration);
    }

    /**
     * Get route distance formatted (km).
     */
    public function getFormattedDistance(): ?string
    {
        if (!$this->hasRouteData()) {
            return null;
        }

        return number_format($this->route_distance, 1) . ' km';
    }

    /**
     * Get route duration formatted (hours and minutes).
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->hasRouteData()) {
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
     * Get waypoint accommodations (in order).
     */
    public function getWaypointAccommodations(): \Illuminate\Support\Collection
    {
        if (empty($this->route_waypoints)) {
            return collect();
        }

        // Get accommodations in the order specified by route_waypoints
        $accommodations = Accommodation::whereIn('id', $this->route_waypoints)->get()->keyBy('id');
        
        // Return in the correct order
        $ordered = collect();
        foreach ($this->route_waypoints as $accommodationId) {
            if ($accommodations->has($accommodationId)) {
                $ordered->push($accommodations->get($accommodationId));
            }
        }
        
        return $ordered;
    }
}
