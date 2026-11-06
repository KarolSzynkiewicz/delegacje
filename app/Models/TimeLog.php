<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_assignment_id',
        'start_time',
        'end_time',
        'hours_worked',
        'client_hours',
        'settled_hours',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hours_worked' => 'decimal:2',
        'client_hours' => 'decimal:2',
        'settled_hours' => 'decimal:2',
    ];

    /**
     * Czy warstwa klienta różni się od wewnętrznej (po skopiowaniu / nadpisaniu).
     */
    public function hasClientMismatch(float $epsilon = 0.001): bool
    {
        if ($this->client_hours === null) {
            return false;
        }

        return abs((float) $this->client_hours - (float) ($this->hours_worked ?? 0)) > $epsilon;
    }

    /**
     * Czy konsensus jest już ustawiony.
     */
    public function hasSettledHours(): bool
    {
        return $this->settled_hours !== null;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Walidacja daty w przypisaniu została usunięta - niepotrzebna,
        // ponieważ time log jest przypisany do project assignment
    }

    /**
     * Get the project assignment for the time log.
     */
    public function projectAssignment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssignment::class);
    }

    /**
     * Get the employee through the project assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->projectAssignment->employee();
    }

    /**
     * Get the project through the project assignment.
     */
    public function project(): BelongsTo
    {
        return $this->projectAssignment->project();
    }
}
