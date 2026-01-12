<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
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
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($timeLog) {
            $timeLog->validateDateWithinAssignment();
        });

        static::updating(function ($timeLog) {
            $timeLog->validateDateWithinAssignment();
        });
    }

    /**
     * Validate that the time log date is within the assignment period.
     * 
     * @throws ValidationException
     */
    protected function validateDateWithinAssignment(): void
    {
        if (!$this->projectAssignment) {
            return; // Skip validation if assignment is not loaded
        }

        $workDate = Carbon::parse($this->start_time);
        $assignment = $this->projectAssignment;
        $startDate = Carbon::parse($assignment->start_date);
        $endDate = $assignment->end_date ? Carbon::parse($assignment->end_date) : null;

        if ($workDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'start_time' => 'Data pracy nie może być wcześniejsza niż data rozpoczęcia przypisania (' . $startDate->format('Y-m-d') . ').'
            ]);
        }

        if ($endDate && $workDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'start_time' => 'Data pracy nie może być późniejsza niż data zakończenia przypisania (' . $endDate->format('Y-m-d') . ').'
            ]);
        }
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
