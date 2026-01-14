<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Contracts\HasEmployee;
use App\Contracts\HasDateRange;

/**
 * LogisticsEventParticipant - uczestnik zdarzenia logistycznego
 * 
 * Uses polymorphic relationship to HasEmployee & HasDateRange implementations.
 * 
 * IMPORTANT: assignment_type + assignment_id MUST point to models implementing HasEmployee & HasDateRange.
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
    ];

    protected $casts = [
        'status' => 'string', // pending, in_transit, completed
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
     * Returns HasEmployee & HasDateRange implementation.
     */
    public function assignment(): MorphTo
    {
        return $this->morphTo('assignment');
    }
}
