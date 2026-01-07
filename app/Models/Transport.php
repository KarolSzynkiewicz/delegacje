<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\TransportMode;

/**
 * Transport - szczegóły transportu publicznego
 */
class Transport extends Model
{
    use HasFactory;

    protected $fillable = [
        'logistics_event_id',
        'mode',
        'carrier',
        'ticket_number',
        'departure_datetime',
        'arrival_datetime',
        'departure_location',
        'arrival_location',
        'cost',
        'notes',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'arrival_datetime' => 'datetime',
        'cost' => 'decimal:2',
        'mode' => TransportMode::class,
    ];

    /**
     * Get the logistics event for this transport.
     */
    public function logisticsEvent(): BelongsTo
    {
        return $this->belongsTo(LogisticsEvent::class);
    }
}
