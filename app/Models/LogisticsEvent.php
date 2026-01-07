<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;

/**
 * LogisticsEvent - fakt biznesowy (co, kiedy, kto, gdzie)
 * 
 * IMPORTANT: Model = tylko fakty, zero logiki biznesowej.
 * Wszystka logika w serwisach (ReturnTripService, DepartureService).
 */
class LogisticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'event_date',
        'has_transport',
        'vehicle_id',
        'transport_id',
        'from_location_id',
        'to_location_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'has_transport' => 'boolean',
        'type' => LogisticsEventType::class,
        'status' => LogisticsEventStatus::class,
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
}
