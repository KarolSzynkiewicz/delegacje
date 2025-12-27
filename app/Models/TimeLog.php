<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
