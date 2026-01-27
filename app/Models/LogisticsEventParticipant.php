<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * LogisticsEventParticipant - uczestnik zdarzenia logistycznego
 * 
 * Uses polymorphic relationship to assignment models.
 * 
 * IMPORTANT: assignment_type + assignment_id MUST point to assignment models.
 * Custom morph map enforces this.
 */
class LogisticsEventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'logistics_event_id',
        'employee_id',
        'assignment_type',
        'assignment_id',
        'status',
        'original_end_date',
    ];

    protected $casts = [
        'status' => 'string', // pending, in_transit, completed
        'original_end_date' => 'date',
    ];

    /**
     * Get the logistics event for this participant.
     */
    public function logisticsEvent(): BelongsTo
    {
        return $this->belongsTo(LogisticsEvent::class);
    }

    /**
     * Get the employee for this participant.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the assignment (polymorphic).
     * 
     * Returns ProjectAssignment, VehicleAssignment, or AccommodationAssignment.
     */
    public function assignment(): MorphTo
    {
        return $this->morphTo('assignment');
    }
}
