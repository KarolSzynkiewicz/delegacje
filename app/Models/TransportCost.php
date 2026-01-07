<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'logistics_event_id',
        'vehicle_id',
        'transport_id',
        'cost_type',
        'amount',
        'currency',
        'cost_date',
        'description',
        'receipt_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cost_date' => 'date',
    ];

    /**
     * Get the logistics event for this cost.
     */
    public function logisticsEvent(): BelongsTo
    {
        return $this->belongsTo(LogisticsEvent::class);
    }

    /**
     * Get the vehicle for this cost (if company vehicle).
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the transport for this cost (if public transport).
     */
    public function transport(): BelongsTo
    {
        return $this->belongsTo(Transport::class);
    }

    /**
     * Get the user who created this cost.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
