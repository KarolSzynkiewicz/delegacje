<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasDateRange;

class ProjectDemand extends Model
{
    use HasFactory, HasDateRange;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'role_id',
        'required_count',
        'start_date',
        'end_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'required_count' => 'integer',
    ];

    /**
     * Get the project that owns the demand.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the role required for this demand.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if the demand is active (not ended yet).
     * 
     * @return bool True if demand is active, false if completed
     */
    public function isActive(): bool
    {
        if ($this->end_date === null) {
            return true; // Open-ended demands are always active
        }

        $today = \Carbon\Carbon::today();
        $endDate = \App\Services\DateRangeService::normalizeDate($this->end_date);
        
        return $endDate->gte($today);
    }

    /**
     * Check if the demand is completed (ended).
     * 
     * @return bool True if demand is completed, false if active
     */
    public function isCompleted(): bool
    {
        return !$this->isActive();
    }
}
